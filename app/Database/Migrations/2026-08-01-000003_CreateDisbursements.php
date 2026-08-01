<?php
/**
 * Migration: disbursement batches + line items (ROADMAP F2 phase 2, ADR-001).
 *
 * A batch is prepared from a source (payroll/expense/loan), approved by a
 * DIFFERENT user than the preparer (maker-checker), then processed. Each line
 * carries a caller-generated UUID `reference` written BEFORE any provider call
 * (idempotency key, unique) and moves through the state machine
 * created → pending → (successful | failed). Terminal states are write-once.
 * Rows are never deleted.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDisbursements extends Migration
{
    public function up()
    {
        // --- batches ---------------------------------------------------
        $this->forge->addField([
            'batch_id'        => ['type' => 'BIGSERIAL', 'auto_increment' => true],
            'company_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            'source'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'manual'],
            'reference_period'=> ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null],
            'currency'        => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => false, 'default' => 'UGX'],
            // draft | approved | processing | completed | failed
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'draft'],
            'total_amount'    => ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => false, 'default' => 0],
            'total_count'     => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'prepared_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            'approved_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            'note'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'created_at'      => ['type' => 'TIMESTAMP', 'null' => false],
            'approved_at'     => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
            'completed_at'    => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('batch_id');
        $this->forge->createTable('ci_disbursement_batches', true);

        // --- line items ------------------------------------------------
        $this->forge->addField([
            'disbursement_id' => ['type' => 'BIGSERIAL', 'auto_increment' => true],
            'batch_id'        => ['type' => 'BIGINT', 'null' => false],
            'employee_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'method_id'       => ['type' => 'BIGINT', 'null' => true, 'default' => null],
            'type'            => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'amount'          => ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => false],
            'currency'        => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => false, 'default' => 'UGX'],
            'reference'       => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false], // our UUID / idempotency key
            'provider'        => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'default' => null],
            'provider_txn_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null],
            // created | pending | successful | failed
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'created'],
            'attempts'        => ['type' => 'SMALLINT', 'constraint' => 3, 'null' => false, 'default' => 0],
            'failure_reason'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'raw_response'    => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_at'      => ['type' => 'TIMESTAMP', 'null' => false],
            'updated_at'      => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
            'settled_at'      => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('disbursement_id');
        $this->forge->addKey('batch_id');
        $this->forge->createTable('ci_disbursements', true);

        // reference is our idempotency key — must be globally unique.
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS idx_disbursement_reference ON ci_disbursements (reference)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_disbursement_status ON ci_disbursements (status)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS idx_disbursement_reference');
        $this->db->query('DROP INDEX IF EXISTS idx_disbursement_status');
        $this->forge->dropTable('ci_disbursements', true);
        $this->forge->dropTable('ci_disbursement_batches', true);
    }
}
