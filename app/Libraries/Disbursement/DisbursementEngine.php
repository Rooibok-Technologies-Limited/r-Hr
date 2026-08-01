<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Disbursement;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * DisbursementEngine — prepare, approve, process and reconcile batch payouts
 * (ROADMAP F2 phase 2, ADR-001).
 *
 * Discipline (payments golden rules):
 *  - Amount and payee are server-authoritative; a caller passes employee ids and
 *    amounts, never a raw destination.
 *  - Each line gets a UUID `reference` written BEFORE any provider call; it is
 *    the idempotency key.
 *  - Maker-checker: a batch is approved by a DIFFERENT user than its preparer.
 *  - The state machine created → pending → (successful|failed) has write-once
 *    terminal states; a repeated webhook/poll is a no-op.
 *  - Nothing is paid to an unverified method (PayoutMethods::payable guards it).
 * Every transition is written to the audit trail.
 */
class DisbursementEngine
{
    private const B_TABLE = 'ci_disbursement_batches';
    private const D_TABLE = 'ci_disbursements';

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Prepare a DRAFT batch. Employees without a verified payable method are
     * skipped and reported (never silently dropped).
     *
     * @param array<int,array{employee_id:int,amount:int|float}> $items
     * @param array{source?:string,period?:string,currency?:string,note?:string,company_id?:int,prepared_by?:int} $opts
     * @return array{ok:bool,batch_id?:int,count?:int,total?:float,skipped?:array,reason?:string}
     */
    public function buildBatch(array $items, array $opts = []): array
    {
        $currency  = $opts['currency'] ?? 'UGX';
        $pm        = service('payoutMethods');
        $now       = date('Y-m-d H:i:s');

        $lines   = [];
        $skipped = [];
        $total   = 0.0;
        foreach ($items as $it) {
            $eid    = (int) ($it['employee_id'] ?? 0);
            $amount = round((float) ($it['amount'] ?? 0), 2);
            if ($eid <= 0 || $amount <= 0) {
                $skipped[] = ['employee_id' => $eid, 'reason' => 'invalid employee or amount'];
                continue;
            }
            $method = $pm->primaryVerifiedFor($eid);
            if (! $method) {
                $skipped[] = ['employee_id' => $eid, 'reason' => 'no verified payout method'];
                continue;
            }
            $lines[] = [
                'employee_id' => $eid,
                'method_id'   => (int) $method['method_id'],
                'type'        => $method['type'],
                'amount'      => $amount,
                'currency'    => $currency,
                'reference'   => $this->uuid(),
                'provider'    => $method['provider'],
                'status'      => 'created',
                'attempts'    => 0,
                'created_at'  => $now,
            ];
            $total += $amount;
        }

        if ($lines === []) {
            return ['ok' => false, 'reason' => 'no payable lines', 'skipped' => $skipped];
        }

        $this->db->transStart();
        $this->db->table(self::B_TABLE)->insert([
            'company_id'       => $opts['company_id'] ?? null,
            'source'           => $opts['source'] ?? 'manual',
            'reference_period' => $opts['period'] ?? null,
            'currency'         => $currency,
            'status'           => 'draft',
            'total_amount'     => $total,
            'total_count'      => count($lines),
            'prepared_by'      => $opts['prepared_by'] ?? $this->currentUser(),
            'note'             => $opts['note'] ?? null,
            'created_at'       => $now,
        ]);
        $batchId = $this->db->insertID();

        foreach ($lines as &$l) {
            $l['batch_id'] = $batchId;
        }
        unset($l);
        $this->db->table(self::D_TABLE)->insertBatch($lines);
        $this->db->transComplete();

        service('audit')->record('disbursement.batch_created', [
            'entity_type' => 'disbursement_batch',
            'entity_id'   => $batchId,
            'summary'     => 'Prepared batch of ' . count($lines) . " ({$currency} " . number_format($total, 2) . ')',
            'after'       => ['count' => count($lines), 'total' => $total, 'skipped' => count($skipped)],
        ]);

        return ['ok' => true, 'batch_id' => (int) $batchId, 'count' => count($lines), 'total' => $total, 'skipped' => $skipped];
    }

    /**
     * Approve a draft batch. Maker-checker: the approver must differ from the
     * preparer.
     *
     * @return array{ok:bool,reason?:string}
     */
    public function approve(int $batchId, ?int $approverId = null): array
    {
        $approverId = $approverId ?? $this->currentUser();
        $batch = $this->batch($batchId);
        if (! $batch) {
            return ['ok' => false, 'reason' => 'batch not found'];
        }
        if ($batch['status'] !== 'draft') {
            return ['ok' => false, 'reason' => 'batch is not in draft'];
        }
        if ($approverId !== null && (int) $batch['prepared_by'] === (int) $approverId) {
            return ['ok' => false, 'reason' => 'approver must differ from preparer (maker-checker)'];
        }

        $this->db->table(self::B_TABLE)->where('batch_id', $batchId)->update([
            'status'      => 'approved',
            'approved_by' => $approverId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        service('audit')->record('disbursement.batch_approved', [
            'entity_type' => 'disbursement_batch',
            'entity_id'   => $batchId,
            'summary'     => "Approved batch #{$batchId}",
            'before'      => ['status' => 'draft'],
            'after'       => ['status' => 'approved'],
        ]);
        return ['ok' => true];
    }

    /**
     * Send an approved batch to the providers. Idempotent: only `created` lines
     * are dispatched; a re-run skips lines already pending/terminal.
     *
     * @return array{ok:bool,dispatched?:int,failed?:int,reason?:string}
     */
    public function process(int $batchId): array
    {
        $batch = $this->batch($batchId);
        if (! $batch) {
            return ['ok' => false, 'reason' => 'batch not found'];
        }
        if (! in_array($batch['status'], ['approved', 'processing'], true)) {
            return ['ok' => false, 'reason' => 'batch must be approved before processing'];
        }
        $this->db->table(self::B_TABLE)->where('batch_id', $batchId)->update(['status' => 'processing']);

        $pm    = service('payoutMethods');
        $lines = $this->db->table(self::D_TABLE)->where('batch_id', $batchId)->where('status', 'created')->get()->getResultArray();

        $dispatched = 0;
        $failed     = 0;
        foreach ($lines as $line) {
            $payable = $pm->payable((int) $line['method_id']);
            if (! $payable) {
                $this->markTerminal($line['reference'], 'failed', null, null, 'method no longer payable');
                $failed++;
                continue;
            }

            $result = service('disbursement')->for($line['type'])->transfer([
                'reference' => $line['reference'],   // idempotency key, already persisted
                'account'   => $payable['account'],
                'amount'    => $line['amount'],
                'currency'  => $line['currency'],
                'note'      => 'Payout ' . ($batch['reference_period'] ?? ''),
            ]);

            $this->db->table(self::D_TABLE)->where('reference', $line['reference'])->update([
                'attempts'        => (int) $line['attempts'] + 1,
                'provider_txn_id' => $result['provider_txn_id'] ?? null,
                'raw_response'    => is_string($result['raw'] ?? null) ? $result['raw'] : json_encode($result['raw'] ?? null),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            if (($result['status'] ?? 'failed') === 'pending' || ($result['ok'] ?? false)) {
                $this->db->table(self::D_TABLE)->where('reference', $line['reference'])->update(['status' => 'pending']);
                $dispatched++;
            } else {
                $this->markTerminal($line['reference'], 'failed', $result['provider_txn_id'] ?? null, $result['raw'] ?? null, $result['reason'] ?? 'transfer rejected');
                $failed++;
            }
        }

        service('audit')->record('disbursement.batch_processed', [
            'entity_type' => 'disbursement_batch',
            'entity_id'   => $batchId,
            'summary'     => "Processed batch #{$batchId}: {$dispatched} sent, {$failed} failed",
        ]);
        $this->refreshBatchStatus($batchId);

        return ['ok' => true, 'dispatched' => $dispatched, 'failed' => $failed];
    }

    /**
     * Apply an authoritative terminal outcome for one line (from a webhook or a
     * status poll). Write-once: a second terminal application is a no-op.
     *
     * @return array{ok:bool,applied:bool,reason?:string}
     */
    public function applyTerminal(string $reference, string $status, ?string $providerTxnId = null, $raw = null): array
    {
        $status = strtolower($status);
        if (! in_array($status, ['successful', 'failed'], true)) {
            return ['ok' => false, 'applied' => false, 'reason' => 'not a terminal status'];
        }
        $line = $this->db->table(self::D_TABLE)->where('reference', $reference)->get()->getRowArray();
        if (! $line) {
            return ['ok' => false, 'applied' => false, 'reason' => 'unknown reference'];
        }
        if (in_array($line['status'], ['successful', 'failed'], true)) {
            log_message('info', '[Disbursement] terminal no-op for {r} (already {s})', ['r' => $reference, 's' => $line['status']]);
            return ['ok' => true, 'applied' => false, 'reason' => 'already terminal'];
        }

        $this->markTerminal($reference, $status, $providerTxnId, $raw, $status === 'failed' ? 'reported failed' : null);
        service('audit')->record('disbursement.settled', [
            'entity_type' => 'disbursement',
            'entity_id'   => (int) $line['disbursement_id'],
            'summary'     => "Disbursement {$reference} → {$status}",
            'before'      => ['status' => $line['status']],
            'after'       => ['status' => $status],
        ]);
        $this->refreshBatchStatus((int) $line['batch_id']);
        return ['ok' => true, 'applied' => true];
    }

    /**
     * Reconciliation backstop: poll provider status for every pending line
     * (optionally within one batch) and apply any terminal outcome.
     *
     * @return array{checked:int,settled:int}
     */
    public function reconcile(?int $batchId = null): array
    {
        $q = $this->db->table(self::D_TABLE)->where('status', 'pending');
        if ($batchId !== null) {
            $q->where('batch_id', $batchId);
        }
        $lines   = $q->get()->getResultArray();
        $settled = 0;
        foreach ($lines as $line) {
            $status = service('disbursement')->for($line['type'])->status($line['reference']);
            if (in_array($status['status'] ?? 'unknown', ['successful', 'failed'], true)) {
                $r = $this->applyTerminal($line['reference'], $status['status'], $line['provider_txn_id'] ?? null, $status['raw'] ?? null);
                if ($r['applied'] ?? false) {
                    $settled++;
                }
            }
        }
        return ['checked' => count($lines), 'settled' => $settled];
    }

    /** Provider callback entry point (called from Api/V1/Webhooks after signature check). */
    public function handleCallback(string $reference, string $status, ?string $providerTxnId = null, $raw = null): array
    {
        return $this->applyTerminal($reference, $status, $providerTxnId, $raw);
    }

    // ------------------------------------------------------------------

    private function markTerminal(string $reference, string $status, ?string $txn, $raw, ?string $reason): void
    {
        $this->db->table(self::D_TABLE)->where('reference', $reference)->update([
            'status'          => $status,
            'provider_txn_id' => $txn,
            'failure_reason'  => $status === 'failed' ? mb_substr((string) $reason, 0, 255) : null,
            'raw_response'    => $raw === null ? null : (is_string($raw) ? $raw : json_encode($raw)),
            'settled_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /** Close a batch once no line is still in flight. */
    private function refreshBatchStatus(int $batchId): void
    {
        $open = (int) $this->db->table(self::D_TABLE)
            ->where('batch_id', $batchId)
            ->whereIn('status', ['created', 'pending'])
            ->countAllResults();
        if ($open > 0) {
            return;
        }
        $failed = (int) $this->db->table(self::D_TABLE)->where('batch_id', $batchId)->where('status', 'failed')->countAllResults();
        $this->db->table(self::B_TABLE)->where('batch_id', $batchId)->update([
            'status'       => $failed > 0 ? 'completed' : 'completed', // completed even with partial failures; failures visible per-line
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function batch(int $batchId): ?array
    {
        return $this->db->table(self::B_TABLE)->where('batch_id', $batchId)->get()->getRowArray() ?: null;
    }

    private function currentUser(): ?int
    {
        if (is_cli()) {
            return null;
        }
        $sup = session()->get('sup_username');
        return is_array($sup) && ! empty($sup['sup_user_id']) ? (int) $sup['sup_user_id'] : null;
    }

    private function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
