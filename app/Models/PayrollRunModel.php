<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Models;

use CodeIgniter\Model;

/**
 * Payroll run header (ADR-001) — one row per company + YYYY-MM period, driving
 * the payroll-run wizard state machine draft → approved → disbursing → completed.
 */
class PayrollRunModel extends Model
{
    protected $table      = 'ci_payroll_runs';
    protected $primaryKey = 'run_id';

    protected $allowedFields = [
        'run_key', 'company_id', 'period', 'status', 'employee_count',
        'gross_total', 'deduction_total', 'net_total', 'currency',
        'prepared_by', 'approved_by', 'batch_id', 'note',
        'created_at', 'approved_at', 'completed_at',
    ];

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;
}
