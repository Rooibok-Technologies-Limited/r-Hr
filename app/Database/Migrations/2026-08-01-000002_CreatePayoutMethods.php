<?php
/**
 * Migration: ci_employee_payout_methods — verified payout destinations (ROADMAP
 * F2, ADR-001). One employee may hold several methods; only a verified one is
 * ever payable, and one is flagged primary.
 *
 * The full destination (MSISDN / account number) is stored ENCRYPTED
 * (account_enc); account_last4 is kept in clear only for display/logs (masked).
 * Verification state lives here: a name looked up from the provider, plus a
 * hashed one-time code with expiry/attempt tracking.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayoutMethods extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'method_id'    => ['type' => 'BIGSERIAL', 'auto_increment' => true],
            'employee_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'company_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            // momo | airtel | bank
            'type'         => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'provider'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'default' => null],
            'account_enc'  => ['type' => 'TEXT', 'null' => false],             // encrypted MSISDN / account no
            'account_last4'=> ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true, 'default' => null],
            'account_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null],
            'bank_name'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null],
            'is_primary'   => ['type' => 'SMALLINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'is_active'    => ['type' => 'SMALLINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            'verified_at'  => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
            'verification_ref' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null],
            'otp_hash'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'otp_expires'  => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
            'otp_attempts' => ['type' => 'SMALLINT', 'constraint' => 2, 'null' => false, 'default' => 0],
            'created_at'   => ['type' => 'TIMESTAMP', 'null' => false],
            'updated_at'   => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
        ]);

        $this->forge->addPrimaryKey('method_id');
        $this->forge->addKey('employee_id');
        $this->forge->createTable('ci_employee_payout_methods', true);

        // One primary per employee (partial unique index).
        $this->db->query(
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_payout_primary_per_employee '
            . 'ON ci_employee_payout_methods (employee_id) WHERE is_primary = 1'
        );
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS idx_payout_primary_per_employee');
        $this->forge->dropTable('ci_employee_payout_methods', true);
    }
}
