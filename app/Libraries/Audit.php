<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Audit — append-only, tamper-evident compliance trail (ROADMAP F1).
 *
 * Resolve via service('audit'). Recording is fail-safe: an audit failure is
 * logged but never propagated, so it cannot break the business action it is
 * recording. Every row is hash-chained to the previous one, so a later edit or
 * deletion of any row is detectable via verifyChain().
 *
 *   service('audit')->record('disbursement.approved', [
 *       'entity_type' => 'disbursement_batch',
 *       'entity_id'   => $batchId,
 *       'summary'     => "Approved batch #{$batchId} (UGX {$total})",
 *       'before'      => ['status' => 'draft'],
 *       'after'       => ['status' => 'approved'],
 *   ]);
 */
class Audit
{
    /** Postgres advisory-lock key that serialises hash-chain writers. */
    private const LOCK_KEY = 917283;

    private BaseConnection $db;

    /** Per-request actor cache: user_id => ['type'=>..,'company_id'=>..]. */
    private array $actorCache = [];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Record a sensitive action. Never throws.
     *
     * @param string $action Dotted slug, e.g. 'company.created', 'auth.login'.
     * @param array  $opts   entity_type, entity_id, summary, before, after,
     *                       actor_user_id, actor_type, company_id, ip, user_agent.
     */
    public function record(string $action, array $opts = []): void
    {
        try {
            [$actorId, $actorType, $companyId] = $this->resolveActor();
            $request = service('request');

            $row = [
                'actor_user_id' => $opts['actor_user_id'] ?? $actorId,
                'actor_type'    => $opts['actor_type']    ?? $actorType,
                'company_id'    => $opts['company_id']     ?? $companyId,
                'action'        => $action,
                'entity_type'   => $opts['entity_type'] ?? null,
                'entity_id'     => isset($opts['entity_id']) ? (string) $opts['entity_id'] : null,
                'summary'       => isset($opts['summary']) ? mb_substr((string) $opts['summary'], 0, 255) : null,
                'before_json'   => array_key_exists('before', $opts) ? $this->encode($opts['before']) : null,
                'after_json'    => array_key_exists('after', $opts) ? $this->encode($opts['after']) : null,
                'ip'            => $opts['ip'] ?? (is_cli() ? null : $request->getIPAddress()),
                'user_agent'    => $opts['user_agent'] ?? $this->userAgent($request),
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            $this->insertChained($row);
        } catch (\Throwable $e) {
            log_message('error', '[Audit] failed to record "{a}": {m}', ['a' => $action, 'm' => $e->getMessage()]);
        }
    }

    /**
     * Verify the hash chain end-to-end.
     *
     * @return array{ok: bool, checked: int, broken_at: int|null}
     */
    public function verifyChain(): array
    {
        $rows = $this->db->table('ci_audit_log')
            ->orderBy('audit_id', 'ASC')
            ->get()
            ->getResultArray();

        $prevHash = null;
        $checked  = 0;
        foreach ($rows as $row) {
            if (($row['prev_hash'] ?? null) !== $prevHash) {
                return ['ok' => false, 'checked' => $checked, 'broken_at' => (int) $row['audit_id']];
            }
            if ($this->hash($row, $prevHash) !== $row['row_hash']) {
                return ['ok' => false, 'checked' => $checked, 'broken_at' => (int) $row['audit_id']];
            }
            $prevHash = $row['row_hash'];
            $checked++;
        }

        return ['ok' => true, 'checked' => $checked, 'broken_at' => null];
    }

    // ------------------------------------------------------------------

    private function insertChained(array $row): void
    {
        $this->db->transStart();
        // Serialise chain writers so concurrent requests can't branch the chain.
        $this->db->query('SELECT pg_advisory_xact_lock(?)', [self::LOCK_KEY]);

        $prev = $this->db->table('ci_audit_log')
            ->select('row_hash')
            ->orderBy('audit_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        $prevHash          = $prev->row_hash ?? null;
        $row['prev_hash']  = $prevHash;
        $row['row_hash']   = $this->hash($row, $prevHash);

        $this->db->table('ci_audit_log')->insert($row);
        $this->db->transComplete();
    }

    /**
     * Deterministic row fingerprint. Field order is fixed and includes the
     * previous row's hash, so the chain is order-sensitive and tamper-evident.
     */
    private function hash(array $row, ?string $prevHash): string
    {
        $canonical = json_encode([
            $row['actor_user_id'] !== null ? (int) $row['actor_user_id'] : null,
            $row['actor_type'],
            $row['company_id'] !== null ? (int) $row['company_id'] : null,
            $row['action'],
            $row['entity_type'],
            $row['entity_id'],
            $row['summary'],
            $row['before_json'],
            $row['after_json'],
            $row['ip'],
            $row['user_agent'],
            $row['created_at'],
            $prevHash,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', (string) $canonical);
    }

    private function encode($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? null : $json;
    }

    /** @return array{0: int|null, 1: string, 2: int|null} [actorId, actorType, companyId] */
    private function resolveActor(): array
    {
        if (is_cli()) {
            return [null, 'system', null];
        }

        $sup = session()->get('sup_username');
        if (! is_array($sup) || empty($sup['sup_user_id'])) {
            return [null, 'system', null];
        }

        $uid = (int) $sup['sup_user_id'];
        if (! isset($this->actorCache[$uid])) {
            $u = $this->db->table('ci_erp_users')
                ->select('user_type, company_id')
                ->where('user_id', $uid)
                ->get()
                ->getRowArray();
            $this->actorCache[$uid] = [
                'type'       => $u['user_type'] ?? 'user',
                'company_id' => isset($u['company_id']) ? (int) $u['company_id'] : null,
            ];
        }

        return [$uid, $this->actorCache[$uid]['type'], $this->actorCache[$uid]['company_id']];
    }

    private function userAgent($request): ?string
    {
        if (is_cli()) {
            return 'cli';
        }
        $ua = (string) $request->getUserAgent()->getAgentString();

        return $ua === '' ? null : mb_substr($ua, 0, 255);
    }
}
