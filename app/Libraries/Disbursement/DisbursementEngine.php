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
        $caps      = $this->caps();

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
            if ($caps['per_txn'] > 0 && $amount > $caps['per_txn']) {
                $skipped[] = ['employee_id' => $eid, 'reason' => 'exceeds per-transaction cap'];
                continue;
            }
            $method = $pm->primaryVerifiedFor($eid);
            if (! $method) {
                $skipped[] = ['employee_id' => $eid, 'reason' => 'no verified payout method'];
                continue;
            }
            // Tenant confinement: a batch funds ONE company's wallet, so every
            // employee's method must belong to that company. Prevents paying (or
            // enumerating) another tenant's employees from this company's float. [SECURITY]
            if (! empty($opts['company_id']) && (int) ($method['company_id'] ?? 0) !== (int) $opts['company_id']) {
                $skipped[] = ['employee_id' => $eid, 'reason' => 'employee not in this company'];
                continue;
            }
            $lines[] = [
                'employee_id' => $eid,
                'company_id'  => $opts['company_id'] ?? ($method['company_id'] ?? null),
                'method_id'   => (int) $method['method_id'],
                'type'        => $method['type'],
                'amount'      => $amount,
                'fee'         => service('wallet')->feeFor($amount),
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
     * Prepare a batch from a payroll month's net pay. Reads ci_payslips for the
     * period, skips payslips already covered by a non-failed disbursement for
     * that period (no double-pay), then delegates to buildBatch.
     *
     * @return array{ok:bool,batch_id?:int,count?:int,total?:float,skipped?:array,reason?:string}
     */
    public function buildFromPayroll(string $salaryMonth, ?int $companyId = null, array $opts = []): array
    {
        $q = $this->db->table('ci_payslips')
            ->select('staff_id, net_salary')
            ->where('salary_month', $salaryMonth)
            ->where('net_salary >', 0);
        if ($companyId !== null) {
            $q->where('company_id', $companyId);
        }
        $payslips = $q->get()->getResultArray();

        // Employees already paid (or in flight) for this exact period.
        $already = array_column($this->db->table(self::D_TABLE)->select('employee_id')
            ->join(self::B_TABLE, self::B_TABLE . '.batch_id = ' . self::D_TABLE . '.batch_id')
            ->where(self::B_TABLE . '.reference_period', $salaryMonth)
            ->whereIn(self::D_TABLE . '.status', ['created', 'pending', 'successful'])
            ->get()->getResultArray(), 'employee_id');
        $already = array_flip(array_map('intval', $already));

        $items = [];
        foreach ($payslips as $p) {
            if (isset($already[(int) $p['staff_id']])) {
                continue;
            }
            $items[] = ['employee_id' => (int) $p['staff_id'], 'amount' => (float) $p['net_salary']];
        }

        if ($items === []) {
            return ['ok' => false, 'reason' => 'no unpaid payslips for ' . $salaryMonth];
        }

        return $this->buildBatch($items, array_merge($opts, [
            'source'     => 'payroll',
            'period'     => $salaryMonth,
            'company_id' => $companyId,
        ]));
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

        // Serialize concurrent process() runs on the SAME batch so a payout line
        // can never be dispatched twice by two overlapping runs (double-pay guard,
        // in addition to the provider idempotency key). [SECURITY]
        $lock = $this->db->query('SELECT pg_try_advisory_lock(4201, ?) AS ok', [$batchId])->getRow();
        if (! $lock || ! $lock->ok) {
            return ['ok' => false, 'reason' => 'batch is already being processed'];
        }

        try {
        // Safety caps (0 = unlimited) — refuse BEFORE any money moves.
        $caps = $this->caps();
        if ($caps['per_run'] > 0 && (float) $batch['total_amount'] > $caps['per_run']) {
            return ['ok' => false, 'reason' => 'batch total exceeds per-run cap'];
        }
        if ($caps['per_day'] > 0) {
            // Exclude THIS batch's own lines so a partial reprocess isn't
            // double-counted, and scope to the funding company. [FIX]
            $q = $this->db->table(self::D_TABLE)
                ->selectSum('amount')
                ->whereIn('status', ['pending', 'successful'])
                ->where('batch_id !=', $batchId)
                ->where('created_at >=', date('Y-m-d 00:00:00'));
            if (! empty($batch['company_id'])) {
                $q->where('company_id', (int) $batch['company_id']);
            }
            $todaySent = (float) ($q->get()->getRow()->amount ?? 0);
            if ($todaySent + (float) $batch['total_amount'] > $caps['per_day']) {
                return ['ok' => false, 'reason' => 'would exceed per-day cap'];
            }
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

            // No live gateway ⇒ fail the line WITHOUT reserving. Otherwise the
            // Null driver returns 'pending', the hold is taken but never settled
            // (status() stays 'unknown' forever) and the batch wedges. [FIX]
            $provider = service('disbursement')->for($line['type']);
            if (! $provider->isConfigured()) {
                $this->markTerminal($line['reference'], 'failed', null, null, 'payout gateway not configured');
                $failed++;
                continue;
            }

            // Reserve principal + fee against the company wallet BEFORE the
            // transfer. Insufficient funds ⇒ the line fails without a provider
            // call, so the float can never be overdrawn.
            $companyId = (int) ($line['company_id'] ?? $batch['company_id'] ?? 0);
            if ($companyId <= 0) {
                $this->markTerminal($line['reference'], 'failed', null, null, 'no funding company on line');
                $failed++;
                continue;
            }
            $fee       = (float) ($line['fee'] ?? 0);
            $reserve   = service('wallet')->reserve($companyId, (float) $line['amount'] + $fee, [
                'reference'       => $line['reference'],
                'disbursement_id' => (int) $line['disbursement_id'],
                'description'     => 'Payout hold',
            ]);
            if (! ($reserve['ok'] ?? false)) {
                $this->markTerminal($line['reference'], 'failed', null, null, $reserve['reason'] ?? 'insufficient funds');
                $failed++;
                continue;
            }

            $result = $provider->transfer([
                'reference' => $line['reference'],   // idempotency key, already persisted
                'type'      => $line['type'],        // lets the aggregator pick the rail
                'account'   => $payable['account'],
                'bank_code' => $payable['bank_code'] ?? null,
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
                // Transfer rejected after we reserved — return the hold.
                service('wallet')->release($companyId, (float) $line['amount'] + $fee, [
                    'reference'       => $line['reference'],
                    'disbursement_id' => (int) $line['disbursement_id'],
                    'description'     => 'Payout rejected — hold released',
                ]);
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
        } finally {
            $this->db->query('SELECT pg_advisory_unlock(4201, ?)', [$batchId]);
        }
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

        // Settle/release the wallet hold once — gated on an OPEN reserve for this
        // reference, not the literal 'pending' string. An async webhook can beat
        // the created→pending flip; keying on the reserve (settle/release are
        // idempotent) closes that race and prevents a leaked hold. [FIX]
        $companyId = (int) ($line['company_id'] ?? 0);
        $wallet    = service('wallet');
        if ($companyId > 0 && $wallet->hasOpenReserve($reference)) {
            $amount = (float) $line['amount'];
            $fee    = (float) ($line['fee'] ?? 0);
            $opts   = ['reference' => $reference, 'disbursement_id' => (int) $line['disbursement_id']];
            if ($status === 'successful') {
                $wallet->settle($companyId, $amount, $fee, $opts + ['description' => 'Payout settled']);
            } else {
                $wallet->release($companyId, $amount + $fee, $opts + ['description' => 'Payout failed — hold released']);
            }
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
        $total     = (int) $this->db->table(self::D_TABLE)->where('batch_id', $batchId)->countAllResults();
        $failed    = (int) $this->db->table(self::D_TABLE)->where('batch_id', $batchId)->where('status', 'failed')->countAllResults();
        // 'failed' only when EVERY line failed; a partial failure is 'completed'
        // (per-line failures stay visible in the line status).
        $this->db->table(self::B_TABLE)->where('batch_id', $batchId)->update([
            'status'       => ($total > 0 && $failed === $total) ? 'failed' : 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function batch(int $batchId): ?array
    {
        return $this->db->table(self::B_TABLE)->where('batch_id', $batchId)->get()->getRowArray() ?: null;
    }

    /**
     * Payout caps from system settings (0/unset = unlimited). A safety net so a
     * fat-fingered amount or a runaway batch can't drain the float.
     *
     * @return array{per_txn:float,per_run:float,per_day:float}
     */
    private function caps(): array
    {
        helper('main');
        return [
            'per_txn' => (float) (system_setting('disbursement_max_per_txn') ?: 0),
            'per_run' => (float) (system_setting('disbursement_max_per_run') ?: 0),
            'per_day' => (float) (system_setting('disbursement_max_per_day') ?: 0),
        ];
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
