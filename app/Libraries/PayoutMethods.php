<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * PayoutMethods — capture and verify employee payout destinations (ROADMAP F2
 * phase 1, ADR-001).
 *
 * Destinations are stored encrypted; only the last 4 digits are kept in clear.
 * Verification for mobile money is two-factor: a provider account-holder lookup
 * (the wallet exists) plus a one-time code delivered to the MSISDN (the payee
 * controls the number). No money is moved to verify. A method is payable only
 * once verified_at is set. Every mutation is written to the audit trail.
 */
class PayoutMethods
{
    private const TABLE       = 'ci_employee_payout_methods';
    private const OTP_TTL_MIN  = 10;
    private const OTP_MAX_TRY   = 5;
    private const TYPES        = ['momo', 'airtel', 'bank'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Register a new (unverified) payout method.
     *
     * @return array{ok: bool, method_id?: int, masked?: string, reason?: string}
     */
    public function addMethod(int $employeeId, string $type, string $account, array $extra = []): array
    {
        $type    = strtolower(trim($type));
        $account = trim($account);
        if (! in_array($type, self::TYPES, true)) {
            return ['ok' => false, 'reason' => 'invalid type'];
        }
        $digits = preg_replace('/\D+/', '', $account);
        if ($type !== 'bank' && strlen($digits) < 9) {
            return ['ok' => false, 'reason' => 'invalid mobile number'];
        }
        if ($account === '') {
            return ['ok' => false, 'reason' => 'account required'];
        }

        $now  = date('Y-m-d H:i:s');
        $row  = [
            'employee_id'  => $employeeId,
            'company_id'   => $this->companyOf($employeeId),
            'type'         => $type,
            'provider'     => $extra['provider'] ?? ($type === 'bank' ? null : $type),
            'account_enc'  => $this->encrypt($account),
            'account_last4'=> substr($type === 'bank' ? preg_replace('/\s+/', '', $account) : $digits, -4),
            'account_name' => $extra['account_name'] ?? null,
            'bank_name'    => $extra['bank_name'] ?? null,
            'is_primary'   => 0,
            'is_active'    => 1,
            'created_at'   => $now,
        ];
        $this->db->table(self::TABLE)->insert($row);
        $id = $this->db->insertID();

        service('audit')->record('payout_method.added', [
            'entity_type' => 'payout_method',
            'entity_id'   => $id,
            'summary'     => "Added {$type} payout ending {$row['account_last4']} for employee #{$employeeId}",
        ]);

        return ['ok' => true, 'method_id' => (int) $id, 'masked' => $this->mask($row['account_last4'])];
    }

    /**
     * Begin verification: look up the account holder, then generate a one-time
     * code. Returns the plaintext code for the CALLER to deliver (via SMS to the
     * MSISDN) — it is never persisted in clear. Bank methods return a manual
     * outcome (verified by an approver, not by code).
     *
     * @return array{ok: bool, channel?: string, code?: string, account?: string, name?: string, masked?: string, reason?: string}
     */
    public function startVerification(int $methodId): array
    {
        $m = $this->find($methodId);
        if (! $m) {
            return ['ok' => false, 'reason' => 'not found'];
        }
        if ($m['type'] === 'bank') {
            service('audit')->record('payout_method.verify_manual', ['entity_type' => 'payout_method', 'entity_id' => $methodId, 'summary' => 'Bank method flagged for manual verification']);
            return ['ok' => true, 'channel' => 'manual', 'masked' => $this->mask($m['account_last4'])];
        }

        $account = $this->decrypt($m['account_enc']);

        // Name lookup (best-effort; stored when the provider returns one).
        $lookup = service('disbursement')->for($m['type'])->validateAccountHolder($account);
        $name   = $lookup['name'] ?? $m['account_name'];

        // One-time control code to the MSISDN.
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->db->table(self::TABLE)->where('method_id', $methodId)->update([
            'account_name' => $name,
            'otp_hash'     => password_hash($code, PASSWORD_DEFAULT),
            'otp_expires'  => date('Y-m-d H:i:s', time() + self::OTP_TTL_MIN * 60),
            'otp_attempts' => 0,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        service('audit')->record('payout_method.verify_started', [
            'entity_type' => 'payout_method',
            'entity_id'   => $methodId,
            'summary'     => "Verification code issued for {$m['type']} ending {$m['account_last4']}",
        ]);

        return [
            'ok'      => true,
            'channel' => 'sms',
            'code'    => $code,          // caller sends this via SMS; not returned to browser
            'account' => $account,
            'name'    => $name,
            'masked'  => $this->mask($m['account_last4']),
        ];
    }

    /**
     * Complete verification with the code the employee received.
     *
     * @return array{ok: bool, reason?: string}
     */
    public function confirmVerification(int $methodId, string $code): array
    {
        $m = $this->find($methodId);
        if (! $m) {
            return ['ok' => false, 'reason' => 'not found'];
        }
        if (! empty($m['verified_at'])) {
            return ['ok' => true]; // already verified — idempotent
        }
        if (empty($m['otp_hash']) || empty($m['otp_expires']) || strtotime($m['otp_expires']) < time()) {
            return ['ok' => false, 'reason' => 'code expired — request a new one'];
        }
        if ((int) $m['otp_attempts'] >= self::OTP_MAX_TRY) {
            return ['ok' => false, 'reason' => 'too many attempts — request a new code'];
        }

        if (! password_verify(trim($code), $m['otp_hash'])) {
            $this->db->table(self::TABLE)->where('method_id', $methodId)
                ->set('otp_attempts', 'otp_attempts + 1', false)->update();
            service('audit')->record('payout_method.verify_failed', ['entity_type' => 'payout_method', 'entity_id' => $methodId, 'summary' => 'Wrong verification code']);
            return ['ok' => false, 'reason' => 'incorrect code'];
        }

        $this->db->table(self::TABLE)->where('method_id', $methodId)->update([
            'verified_at'      => date('Y-m-d H:i:s'),
            'verification_ref' => $this->uuid(),
            'otp_hash'         => null,
            'otp_expires'      => null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        service('audit')->record('payout_method.verified', [
            'entity_type' => 'payout_method',
            'entity_id'   => $methodId,
            'summary'     => "Verified {$m['type']} payout ending {$m['account_last4']}",
        ]);

        return ['ok' => true];
    }

    /**
     * Manually verify a BANK method (ADR-001 maker-checker: no penny-drop, so an
     * admin confirms the destination against evidence). Bank-only — mobile money
     * must go through the SMS-code path. Idempotent; records the approver +
     * evidence in the audit trail.
     *
     * @return array{ok: bool, reason?: string}
     */
    public function manualVerify(int $methodId, int $approverId, string $evidence = ''): array
    {
        $m = $this->find($methodId);
        if (! $m) {
            return ['ok' => false, 'reason' => 'not found'];
        }
        if ($m['type'] !== 'bank') {
            return ['ok' => false, 'reason' => 'manual verification is for bank methods only'];
        }
        if (! empty($m['verified_at'])) {
            return ['ok' => true]; // already verified — idempotent
        }

        $this->db->table(self::TABLE)->where('method_id', $methodId)->update([
            'verified_at'      => date('Y-m-d H:i:s'),
            'verification_ref' => 'manual:' . $this->uuid(),
            'otp_hash'         => null,
            'otp_expires'      => null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        service('audit')->record('payout_method.verified_manual', [
            'entity_type' => 'payout_method',
            'entity_id'   => $methodId,
            'summary'     => "Bank payout ending {$m['account_last4']} manually verified by user #{$approverId}"
                . ($evidence !== '' ? ' — evidence: ' . $evidence : ''),
        ]);

        return ['ok' => true];
    }

    /** Make a verified method the employee's primary (one primary per employee). */
    public function setPrimary(int $methodId): array
    {
        $m = $this->find($methodId);
        if (! $m) {
            return ['ok' => false, 'reason' => 'not found'];
        }
        if (empty($m['verified_at'])) {
            return ['ok' => false, 'reason' => 'method must be verified first'];
        }

        $this->db->transStart();
        $this->db->table(self::TABLE)->where('employee_id', $m['employee_id'])->update(['is_primary' => 0]);
        $this->db->table(self::TABLE)->where('method_id', $methodId)->update(['is_primary' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->db->transComplete();

        service('audit')->record('payout_method.set_primary', ['entity_type' => 'payout_method', 'entity_id' => $methodId, 'summary' => "Primary payout set for employee #{$m['employee_id']}"]);
        return ['ok' => true];
    }

    /** Masked list for display (never returns the full destination). */
    public function listFor(int $employeeId): array
    {
        $rows = $this->db->table(self::TABLE)
            ->where('employee_id', $employeeId)
            ->where('is_active', 1)
            ->orderBy('is_primary', 'DESC')->orderBy('method_id', 'DESC')
            ->get()->getResultArray();

        return array_map(fn ($r) => [
            'method_id'  => (int) $r['method_id'],
            'type'       => $r['type'],
            'provider'   => $r['provider'],
            'masked'     => $this->mask($r['account_last4']),
            'name'       => $r['account_name'],
            'bank_name'  => $r['bank_name'],
            'is_primary' => (int) $r['is_primary'] === 1,
            'verified'   => ! empty($r['verified_at']),
        ], $rows);
    }

    /**
     * The employee's payable method: verified + active, primary preferred.
     * Returns the raw row (or null) — used by the disbursement engine.
     */
    public function primaryVerifiedFor(int $employeeId): ?array
    {
        return $this->db->table(self::TABLE)
            ->where('employee_id', $employeeId)
            ->where('is_active', 1)
            ->where('verified_at IS NOT NULL', null, false)
            ->orderBy('is_primary', 'DESC')->orderBy('method_id', 'ASC')
            ->get()->getRowArray() ?: null;
    }

    /**
     * Decrypted destination for a method that is safe to pay (verified+active).
     * Returns null when the method is missing, inactive, or unverified — so the
     * engine can never pay an unverified destination.
     *
     * @return array{account:string, type:string, provider:?string, name:?string}|null
     */
    public function payable(int $methodId): ?array
    {
        $m = $this->find($methodId);
        if (! $m || (int) $m['is_active'] !== 1 || empty($m['verified_at'])) {
            return null;
        }
        return [
            'account'  => $this->decrypt($m['account_enc']),
            'type'     => $m['type'],
            'provider' => $m['provider'],
            'name'     => $m['account_name'],
        ];
    }

    /**
     * Owner context for an authorization check: the method's employee_id +
     * company_id, or null if the method doesn't exist. Callers use this to
     * confine an actor to their own tenant/employee before acting. [SECURITY]
     *
     * @return array{employee_id:int, company_id:?int}|null
     */
    public function ownerOf(int $methodId): ?array
    {
        $m = $this->find($methodId);
        if (! $m) {
            return null;
        }
        return [
            'employee_id' => (int) $m['employee_id'],
            'company_id'  => isset($m['company_id']) ? (int) $m['company_id'] : null,
        ];
    }

    /** Public: the company a given employee belongs to (tenant scoping). */
    public function companyForEmployee(int $employeeId): ?int
    {
        return $this->companyOf($employeeId);
    }

    // ------------------------------------------------------------------

    private function find(int $methodId): ?array
    {
        return $this->db->table(self::TABLE)->where('method_id', $methodId)->get()->getRowArray() ?: null;
    }

    private function companyOf(int $employeeId): ?int
    {
        $u = $this->db->table('ci_erp_users')->select('company_id')->where('user_id', $employeeId)->get()->getRowArray();
        return isset($u['company_id']) ? (int) $u['company_id'] : null;
    }

    private function encrypt(string $plain): string
    {
        return base64_encode(\Config\Services::encrypter()->encrypt($plain));
    }

    private function decrypt(string $stored): string
    {
        try {
            return \Config\Services::encrypter()->decrypt(base64_decode($stored));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function mask(?string $last4): string
    {
        return '•••• ' . ($last4 ?? '????');
    }

    private function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
