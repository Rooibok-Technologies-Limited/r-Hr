<?php
/**
 * Migration: company wallets + ledger (ROADMAP F2, ADR-002).
 *
 * ci_company_wallets — one virtual wallet per company: `balance` is available
 * funds, `reserved` is held for in-flight payouts. ci_wallet_transactions is the
 * append-only, signed-amount ledger (credits +, debits −) with a running
 * balance_after; it is the authoritative source of the balance and is reconciled
 * against the aggregator's master float.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompanyWallets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'wallet_id'  => ['type' => 'BIGSERIAL', 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'currency'   => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => false, 'default' => 'UGX'],
            'balance'    => ['type' => 'NUMERIC', 'constraint' => '16,2', 'null' => false, 'default' => 0],
            'reserved'   => ['type' => 'NUMERIC', 'constraint' => '16,2', 'null' => false, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'active'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => false],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('wallet_id');
        $this->forge->createTable('ci_company_wallets', true);
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS idx_wallet_company ON ci_company_wallets (company_id)');

        $this->forge->addField([
            'txn_id'          => ['type' => 'BIGSERIAL', 'auto_increment' => true],
            'wallet_id'       => ['type' => 'BIGINT', 'null' => false],
            'company_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            // topup | payout | fee | reserve | release | reversal | adjustment
            'type'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'amount'          => ['type' => 'NUMERIC', 'constraint' => '16,2', 'null' => false], // signed
            'balance_after'   => ['type' => 'NUMERIC', 'constraint' => '16,2', 'null' => false],
            'reserved_after'  => ['type' => 'NUMERIC', 'constraint' => '16,2', 'null' => false, 'default' => 0],
            'reference'       => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            'disbursement_id' => ['type' => 'BIGINT', 'null' => true, 'default' => null],
            'description'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'created_at'      => ['type' => 'TIMESTAMP', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('txn_id');
        $this->forge->addKey('wallet_id');
        $this->forge->createTable('ci_wallet_transactions', true);
        // Idempotency for top-ups / provider-referenced credits.
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS idx_wallet_txn_ref ON ci_wallet_transactions (type, reference) WHERE reference IS NOT NULL');

        // Disbursement lines need the funding company + per-line fee so the
        // wallet can reserve/settle exactly what was earmarked.
        $existing = $this->db->getFieldNames('ci_disbursements');
        $add = [];
        if (! in_array('company_id', $existing, true)) {
            $add['company_id'] = ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null];
        }
        if (! in_array('fee', $existing, true)) {
            $add['fee'] = ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => false, 'default' => 0];
        }
        if ($add !== []) {
            $this->forge->addColumn('ci_disbursements', $add);
        }
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS idx_wallet_txn_ref');
        $this->db->query('DROP INDEX IF EXISTS idx_wallet_company');
        $this->forge->dropTable('ci_wallet_transactions', true);
        $this->forge->dropTable('ci_company_wallets', true);
    }
}
