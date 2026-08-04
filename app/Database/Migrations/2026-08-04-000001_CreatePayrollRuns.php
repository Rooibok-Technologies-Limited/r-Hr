<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Migration: payroll runs (ROADMAP F2, ADR-001) — the header entity for the
 * payroll-run wizard (period → preview → approve → disburse).
 *
 * A run groups the payslips generated for one company + one YYYY-MM period, with
 * its own maker-checker state machine draft → approved → disbursing → completed
 * (cancelled is terminal). One run per (company_id, period) — the unique index
 * blocks a duplicate run for the same month. `batch_id` links to the
 * `ci_disbursement_batches` row once the run is handed to the money engine.
 * Payslips point back via `ci_payslips.payroll_run_id`. Rows are never deleted.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayrollRuns extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'run_id'          => ['type' => 'BIGSERIAL', 'auto_increment' => true],
            'run_key'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'company_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'period'          => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => false], // YYYY-MM
            // draft | approved | disbursing | completed | cancelled
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'draft'],
            'employee_count'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'gross_total'     => ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => false, 'default' => 0],
            'deduction_total' => ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => false, 'default' => 0],
            'net_total'       => ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => false, 'default' => 0],
            'currency'        => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => false, 'default' => 'UGX'],
            'prepared_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            'approved_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            'batch_id'        => ['type' => 'BIGINT', 'null' => true, 'default' => null],
            'note'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'created_at'      => ['type' => 'TIMESTAMP', 'null' => false],
            'approved_at'     => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
            'completed_at'    => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('run_id');
        $this->forge->createTable('ci_payroll_runs', true);

        // One run per company per period; fast lookup by run_key (URL token) + status.
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS idx_payroll_run_company_period ON ci_payroll_runs (company_id, period)');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS idx_payroll_run_key ON ci_payroll_runs (run_key)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_payroll_run_status ON ci_payroll_runs (company_id, status)');

        // Link payslips back to their run (nullable — legacy payslips have none).
        if (! $this->db->fieldExists('payroll_run_id', 'ci_payslips')) {
            $this->forge->addColumn('ci_payslips', [
                'payroll_run_id' => ['type' => 'BIGINT', 'null' => true, 'default' => null],
            ]);
            $this->db->query('CREATE INDEX IF NOT EXISTS idx_payslip_run ON ci_payslips (payroll_run_id)');
        }
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS idx_payslip_run');
        if ($this->db->fieldExists('payroll_run_id', 'ci_payslips')) {
            $this->forge->dropColumn('ci_payslips', 'payroll_run_id');
        }
        $this->db->query('DROP INDEX IF EXISTS idx_payroll_run_company_period');
        $this->db->query('DROP INDEX IF EXISTS idx_payroll_run_key');
        $this->db->query('DROP INDEX IF EXISTS idx_payroll_run_status');
        $this->forge->dropTable('ci_payroll_runs', true);
    }
}
