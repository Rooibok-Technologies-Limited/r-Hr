<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

/**
 * Migration: non-negative CHECK constraints on ci_company_wallets (ROADMAP F2,
 * ADR-002). A wallet must never go negative in either pool; with the
 * rollback-safe WalletService::tx(), a violated constraint becomes a hard
 * ok:false (no money moves) instead of a silently clamped, leaking balance.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWalletCheckConstraints extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE ci_company_wallets ADD CONSTRAINT chk_wallet_balance_nonneg CHECK (balance >= 0)');
        $this->db->query('ALTER TABLE ci_company_wallets ADD CONSTRAINT chk_wallet_reserved_nonneg CHECK (reserved >= 0)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE ci_company_wallets DROP CONSTRAINT IF EXISTS chk_wallet_balance_nonneg');
        $this->db->query('ALTER TABLE ci_company_wallets DROP CONSTRAINT IF EXISTS chk_wallet_reserved_nonneg');
    }
}
