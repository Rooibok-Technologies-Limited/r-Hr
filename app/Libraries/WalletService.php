<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * WalletService — per-company virtual wallet over an aggregator-held float
 * (ROADMAP F2, ADR-002).
 *
 * Two pools: `balance` (available) and `reserved` (held for in-flight payouts).
 * Total company funds = balance + reserved. Money that actually enters/leaves the
 * float is recorded in the append-only ledger (topup, payout, fee, reversal);
 * reserve/release move funds between the two pools and are traced too. Every
 * mutation is serialised per wallet with a Postgres advisory lock, so concurrent
 * batches can never overspend or double-spend.
 */
class WalletService
{
    private const W_TABLE = 'ci_company_wallets';
    private const L_TABLE = 'ci_wallet_transactions';
    private const LOCK_NS  = 4711;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /** @return array{balance:float,reserved:float,available:float,currency:string} */
    public function balance(int $companyId): array
    {
        $w = $this->getOrCreate($companyId);
        return [
            'balance'   => (float) $w['balance'],
            'reserved'  => (float) $w['reserved'],
            'available' => (float) $w['balance'],
            'currency'  => $w['currency'],
        ];
    }

    /**
     * Credit the wallet (a top-up or reversal). Idempotent on (type, reference)
     * so a replayed collection webhook credits once.
     *
     * @return array{ok:bool,applied:bool,balance?:float,reason?:string}
     */
    public function credit(int $companyId, float $amount, string $type = 'topup', array $opts = []): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'applied' => false, 'reason' => 'amount must be positive'];
        }
        return $this->tx($companyId, function (array $w) use ($amount, $type, $opts) {
            $ref = $opts['reference'] ?? null;
            if ($ref !== null && $this->refExists($type, $ref)) {
                return ['ok' => true, 'applied' => false, 'reason' => 'duplicate reference'];
            }
            $balance = (float) $w['balance'] + $amount;
            $this->setPools($w['wallet_id'], $balance, (float) $w['reserved']);
            $this->ledger($w, $type, $amount, $balance, (float) $w['reserved'], $opts);
            if ($type === 'topup') {
                service('audit')->record('wallet.topup', ['entity_type' => 'company_wallet', 'entity_id' => (int) $w['company_id'], 'summary' => "Top-up {$w['currency']} " . number_format($amount, 2), 'after' => ['balance' => $balance]]);
            }
            return ['ok' => true, 'applied' => true, 'balance' => $balance];
        });
    }

    /**
     * Hold funds for an in-flight payout: available → reserved. Fails (without
     * mutating) when the available balance can't cover it.
     *
     * @return array{ok:bool,reason?:string}
     */
    public function reserve(int $companyId, float $amount, array $opts = []): array
    {
        if ($amount <= 0) {
            return ['ok' => true];
        }
        return $this->tx($companyId, function (array $w) use ($amount, $opts) {
            // Idempotent on reference: a re-processed line whose first reserve
            // succeeded (but whose transfer threw) already holds the funds.
            $ref = $opts['reference'] ?? null;
            if ($ref !== null && $this->refExists('reserve', $ref)) {
                return ['ok' => true, 'reason' => 'already reserved'];
            }
            if ((float) $w['balance'] + 1e-9 < $amount) {
                return ['ok' => false, 'reason' => 'insufficient funds'];
            }
            $balance  = (float) $w['balance'] - $amount;
            $reserved = (float) $w['reserved'] + $amount;
            $this->setPools($w['wallet_id'], $balance, $reserved);
            $this->ledger($w, 'reserve', -$amount, $balance, $reserved, $opts);
            return ['ok' => true];
        });
    }

    /** Return a hold to available (payout failed): reserved → balance. */
    public function release(int $companyId, float $amount, array $opts = []): array
    {
        if ($amount <= 0) {
            return ['ok' => true];
        }
        return $this->tx($companyId, function (array $w) use ($amount, $opts) {
            $take     = min($amount, (float) $w['reserved']);
            $balance  = (float) $w['balance'] + $take;
            $reserved = (float) $w['reserved'] - $take;
            $this->setPools($w['wallet_id'], $balance, $reserved);
            $this->ledger($w, 'release', $take, $balance, $reserved, $opts);
            return ['ok' => true];
        });
    }

    /**
     * Consume a hold because the payout succeeded: reserved drops by
     * principal + fee (the money has left the float). Posts payout + fee ledger
     * entries.
     */
    public function settle(int $companyId, float $principal, float $fee = 0.0, array $opts = []): array
    {
        $total = $principal + $fee;
        if ($total <= 0) {
            return ['ok' => true];
        }
        return $this->tx($companyId, function (array $w) use ($principal, $fee, $opts) {
            $reserved = max(0.0, (float) $w['reserved'] - ($principal + $fee));
            $balance  = (float) $w['balance'];
            $this->setPools($w['wallet_id'], $balance, $reserved);
            $this->ledger($w, 'payout', -$principal, $balance, $reserved, $opts);
            if ($fee > 0) {
                $this->ledger($w, 'fee', -$fee, $balance, $reserved, $opts);
            }
            return ['ok' => true];
        });
    }

    /** Per-payout fee from settings: flat + percentage. */
    public function feeFor(float $amount): float
    {
        helper('main');
        $flat    = (float) (system_setting('disbursement_fee_flat') ?: 0);
        $percent = (float) (system_setting('disbursement_fee_percent') ?: 0);
        return round($flat + ($amount * $percent / 100), 2);
    }

    public function getOrCreate(int $companyId, string $currency = 'UGX'): array
    {
        $w = $this->db->table(self::W_TABLE)->where('company_id', $companyId)->get()->getRowArray();
        if ($w) {
            return $w;
        }
        $this->db->table(self::W_TABLE)->insert([
            'company_id' => $companyId,
            'currency'   => $currency,
            'balance'    => 0,
            'reserved'   => 0,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->table(self::W_TABLE)->where('company_id', $companyId)->get()->getRowArray();
    }

    /** Ledger for a company (newest first), for the wallet statement UI. */
    public function transactions(int $companyId, int $limit = 200): array
    {
        return $this->db->table(self::L_TABLE)
            ->where('company_id', $companyId)
            ->orderBy('txn_id', 'DESC')->limit($limit)
            ->get()->getResultArray();
    }

    // ------------------------------------------------------------------

    /** Run $fn inside a transaction holding this wallet's advisory lock. */
    private function tx(int $companyId, callable $fn): array
    {
        $this->db->transStart();
        $w = $this->getOrCreate($companyId);
        $this->db->query('SELECT pg_advisory_xact_lock(?, ?)', [self::LOCK_NS, (int) $w['wallet_id']]);
        // Re-read under lock for a consistent view.
        $w   = $this->db->table(self::W_TABLE)->where('wallet_id', $w['wallet_id'])->get()->getRowArray();
        $res = $fn($w);
        $this->db->transComplete();
        return $res;
    }

    private function setPools(int $walletId, float $balance, float $reserved): void
    {
        $this->db->table(self::W_TABLE)->where('wallet_id', $walletId)->update([
            'balance'    => $balance,
            'reserved'   => $reserved,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function ledger(array $w, string $type, float $amount, float $balanceAfter, float $reservedAfter, array $opts): void
    {
        $this->db->table(self::L_TABLE)->insert([
            'wallet_id'       => $w['wallet_id'],
            'company_id'      => $w['company_id'],
            'type'            => $type,
            'amount'          => $amount,
            'balance_after'   => $balanceAfter,
            'reserved_after'  => $reservedAfter,
            'reference'       => $opts['reference'] ?? null,
            'disbursement_id' => $opts['disbursement_id'] ?? null,
            'description'     => isset($opts['description']) ? mb_substr((string) $opts['description'], 0, 255) : null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    private function refExists(string $type, string $reference): bool
    {
        return (bool) $this->db->table(self::L_TABLE)->where('type', $type)->where('reference', $reference)->countAllResults();
    }
}
