<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\SystemModel;
use App\Models\PayrollModel;
use App\Models\PayrollRunModel;
use App\Models\PayallowancesModel;
use App\Models\PaycommissionsModel;
use App\Models\PayotherpaymentsModel;
use App\Models\PaystatutorydeductionsModel;
use App\Libraries\PayrollCalculator;

/**
 * PayrollRun (ADR-001) — the payroll-run wizard: period → preview → approve →
 * disburse. A run is one ci_payroll_runs row per company + YYYY-MM.
 *
 * Flow:
 *   preview()   read-only bulk compute (PayrollCalculator, persist=false)
 *   generate()  create run (draft) + persist payslips + child rows + rollup
 *   approve()   draft → approved (payroll sign-off gate)
 *   disburse()  approved → disbursing; hands the period to
 *               DisbursementEngine::buildFromPayroll, then to the disbursement
 *               dashboard where the MONEY maker-checker (approve/process) lives.
 *
 * The enforced dual-control on money stays on the disbursement batch (ADR-001);
 * the payroll approve is the "computed figures are correct" sign-off.
 */
class PayrollRun extends BaseController
{
    /** GET — render the wizard shell with recent runs. */
    public function wizard()
    {
        $guard = $this->guard('pay1');
        if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $guard;
        }
        [$companyId, $userInfo] = $guard;

        $xin = (new SystemModel())->where('setting_id', 1)->first();
        $data['title']       = lang('Dashboard.left_payroll') . ' | ' . ($xin['application_name'] ?? 'Payroll');
        $data['path_url']    = 'payroll-run';
        $data['breadcrumbs'] = lang('Dashboard.left_payroll');
        $data['default_period'] = date('Y-m');
        $data['currency']    = erp_currency();
        $data['subview']     = view('erp/payroll/run_wizard', $data);
        return view('erp/layout/layout_main', $data);
    }

    /** POST period=YYYY-MM — read-only bulk preview. No writes. */
    public function preview()
    {
        $guard = $this->guard('pay1', true);
        if (! is_array($guard)) {
            return $guard;
        }
        [$companyId] = $guard;

        $period = $this->validPeriod();
        if ($period === null) {
            return $this->json(['error' => 'Invalid period. Use YYYY-MM.']);
        }

        $existing = (new PayrollRunModel())->where('company_id', $companyId)->where('period', $period)->first();
        $calc  = new PayrollCalculator();
        $rows  = [];
        $g = $d = $n = 0.0;
        foreach ($this->companyStaff($companyId) as $s) {
            $c = $calc->compute((int) $s['user_id'], $period, $companyId, false);
            if ($c['gross_for_tax'] <= 0) {
                continue; // nothing to pay
            }
            $already = (new PayrollModel())->where('company_id', $companyId)
                ->where('staff_id', $s['user_id'])->where('salary_month', $period)->countAllResults();
            $deductions = $c['total_statutory_deductions'] + $c['nssf_employee'] + $c['paye_tax'];
            $rows[] = [
                'staff_id'   => (int) $s['user_id'],
                'name'       => trim($s['first_name'] . ' ' . $s['last_name']),
                'email'      => $s['email'],
                'basic'      => $c['basic_salary'],
                'allowances' => $c['total_allowances'] + $c['total_commissions'] + $c['total_other_payments'],
                'paye'       => $c['paye_tax'],
                'nssf'       => $c['nssf_employee'],
                'statutory'  => $c['total_statutory_deductions'],
                'net'        => $c['net_salary'],
                'already'    => $already > 0,
            ];
            $g += $c['gross_for_tax'];
            $d += $deductions;
            $n += $c['net_salary'];
        }

        return $this->json([
            'period'  => $period,
            'rows'    => $rows,
            'totals'  => ['employees' => count($rows), 'gross' => $g, 'deductions' => $d, 'net' => $n],
            'run'     => $existing ? ['run_key' => $existing['run_key'], 'status' => $existing['status']] : null,
        ]);
    }

    /** POST period — create the run (draft) and persist payslips + child rows. */
    public function generate()
    {
        $guard = $this->guard('pay2', true);
        if (! is_array($guard)) {
            return $guard;
        }
        [$companyId, $userInfo] = $guard;

        $period = $this->validPeriod();
        if ($period === null) {
            return $this->json(['error' => 'Invalid period. Use YYYY-MM.']);
        }

        $runModel = new PayrollRunModel();
        if ($runModel->where('company_id', $companyId)->where('period', $period)->first()) {
            return $this->json(['error' => 'A payroll run for ' . $period . ' already exists.']);
        }

        $runKey = bin2hex(random_bytes(16));
        $runModel->insert([
            'run_key'     => $runKey,
            'company_id'  => $companyId,
            'period'      => $period,
            'status'      => 'draft',
            'currency'    => erp_currency(),
            'prepared_by' => (int) $userInfo['user_id'],
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $runId = $runModel->insertID();

        $calc = new PayrollCalculator();
        $payroll = new PayrollModel();
        $count = 0; $g = $d = $n = 0.0;

        foreach ($this->companyStaff($companyId) as $s) {
            $staffId = (int) $s['user_id'];
            // Skip anyone already paid for this period (no double payslip).
            if ($payroll->where('company_id', $companyId)->where('staff_id', $staffId)
                ->where('salary_month', $period)->countAllResults() > 0) {
                continue;
            }
            $c = $calc->compute($staffId, $period, $companyId, true); // persist advance/loan progress
            if ($c['gross_for_tax'] <= 0) {
                continue;
            }

            $ok = $payroll->insert([
                'payslip_key'                => bin2hex(random_bytes(12)),
                'company_id'                 => $companyId,
                'staff_id'                   => $staffId,
                'salary_month'               => $period,
                'wages_type'                 => $c['wages_type'],
                'payslip_type'               => 'full_monthly',
                'year_to_date'               => date('d-m-Y'),
                'basic_salary'               => $c['basic_salary'],
                'daily_wages'                => 0,
                'hours_worked'               => 0,
                'total_allowances'           => $c['total_allowances'],
                'total_commissions'          => $c['total_commissions'],
                'total_statutory_deductions' => $c['total_statutory_deductions'],
                'total_other_payments'       => $c['total_other_payments'],
                'net_salary'                 => $c['net_salary'],
                'paye_tax'                   => $c['paye_tax'],
                'nssf_employee'              => $c['nssf_employee'],
                'nssf_employer'              => $c['nssf_employer'],
                'payment_method'             => 1,
                'pay_comments'               => 'Auto-generated by payroll run ' . $period,
                'is_payment'                 => 1,
                'is_advance_salary_deduct'   => $c['is_advance_salary_deduct'],
                'advance_salary_amount'      => $c['advance_amount'],
                'is_loan_deduct'             => $c['is_loan_deduct'],
                'loan_amount'                => $c['loan_amount'],
                'status'                     => 0,
                'payroll_run_id'             => $runId,
                'created_at'                 => date('d-m-Y'),
            ]);
            if (! $ok) {
                continue; // insert rejected — do not count toward the run total
            }
            $this->insertChildRows($payroll->insertID(), $staffId, $period, $c);

            $count++;
            $g += $c['gross_for_tax'];
            $d += $c['total_statutory_deductions'] + $c['nssf_employee'] + $c['paye_tax'];
            $n += $c['net_salary'];
        }

        $runModel->update($runId, [
            'employee_count'  => $count,
            'gross_total'     => $g,
            'deduction_total' => $d,
            'net_total'       => $n,
        ]);

        $this->audit('payroll.run_generated', $companyId, $runId,
            "Generated payroll run $period: $count payslips, net " . number_format($n, 2));

        return $this->json(['run_key' => $runKey, 'count' => $count, 'net' => $n]);
    }

    /** POST run_key — payroll sign-off: draft → approved. */
    public function approve()
    {
        $guard = $this->guard('pay2', true);
        if (! is_array($guard)) {
            return $guard;
        }
        [$companyId, $userInfo] = $guard;

        $run = $this->loadRun($companyId);
        if (! $run) {
            return $this->json(['error' => 'Run not found.']);
        }
        if ($run['status'] !== 'draft') {
            return $this->json(['error' => 'Only a draft run can be approved.']);
        }

        (new PayrollRunModel())->update($run['run_id'], [
            'status'      => 'approved',
            'approved_by' => (int) $userInfo['user_id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
        $this->audit('payroll.run_approved', $companyId, (int) $run['run_id'],
            "Approved payroll run {$run['period']}");

        return $this->json(['status' => 'approved']);
    }

    /** POST run_key — approved → disbursing; build the disbursement batch. */
    public function disburse()
    {
        $guard = $this->guard('pay2', true);
        if (! is_array($guard)) {
            return $guard;
        }
        [$companyId, $userInfo] = $guard;

        $run = $this->loadRun($companyId);
        if (! $run) {
            return $this->json(['error' => 'Run not found.']);
        }
        if ($run['status'] !== 'approved') {
            return $this->json(['error' => 'Approve the run before disbursing.']);
        }

        try {
            $batch = service('disbursementEngine')->buildFromPayroll(
                $run['period'], $companyId, ['prepared_by' => (int) $userInfo['user_id']]
            );
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Could not build disbursement: ' . $e->getMessage()]);
        }

        if (empty($batch['ok'])) {
            // e.g. no employee has a verified payout method yet — run stays approved.
            return $this->json(['error' => 'Disbursement not built: ' . ($batch['reason'] ?? 'unknown error')]);
        }

        $batchId = $batch['batch_id'] ?? null;
        (new PayrollRunModel())->update($run['run_id'], [
            'status'   => 'disbursing',
            'batch_id' => $batchId,
        ]);
        $this->audit('payroll.run_disbursing', $companyId, (int) $run['run_id'],
            "Payroll run {$run['period']} sent to disbursement batch " . ($batchId ?? '?'));

        // Hand off to the disbursement dashboard for the money maker-checker.
        return $this->json([
            'status'   => 'disbursing',
            'batch_id' => $batchId,
            'redirect' => site_url('erp/disbursements'),
        ]);
    }

    /** POST run_key — cancel a draft run. */
    public function cancel()
    {
        $guard = $this->guard('pay2', true);
        if (! is_array($guard)) {
            return $guard;
        }
        [$companyId] = $guard;

        $run = $this->loadRun($companyId);
        if (! $run) {
            return $this->json(['error' => 'Run not found.']);
        }
        if ($run['status'] !== 'draft') {
            return $this->json(['error' => 'Only a draft run can be cancelled.']);
        }
        (new PayrollRunModel())->update($run['run_id'], ['status' => 'cancelled']);
        return $this->json(['status' => 'cancelled']);
    }

    /** GET — recent runs for this company (wizard history panel). */
    public function list_runs()
    {
        $guard = $this->guard('pay1', true);
        if (! is_array($guard)) {
            return $guard;
        }
        [$companyId] = $guard;

        $runs = (new PayrollRunModel())->where('company_id', $companyId)
            ->orderBy('created_at', 'DESC')->findAll(50);
        return $this->json(['runs' => $runs]);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /** Active staff of a company (user_type=staff). */
    private function companyStaff(int $companyId): array
    {
        return (new UsersModel())->where('company_id', $companyId)
            ->where('user_type', 'staff')->orderBy('user_id', 'ASC')->findAll();
    }

    private function insertChildRows(int $payslipId, int $staffId, string $period, array $c): void
    {
        foreach ($c['allowances'] as $a) {
            (new PayallowancesModel())->insert([
                'payslip_id' => $payslipId, 'staff_id' => $staffId, 'salary_month' => $period,
                'pay_title' => $a['option_title'], 'pay_amount' => $a['contract_amount'],
                'is_taxable' => $a['contract_tax_option'], 'is_fixed' => $a['is_fixed'],
                'created_at' => date('d-m-Y h:i:s'),
            ]);
        }
        foreach ($c['commissions'] as $a) {
            (new PaycommissionsModel())->insert([
                'payslip_id' => $payslipId, 'staff_id' => $staffId, 'salary_month' => $period,
                'pay_title' => $a['option_title'], 'pay_amount' => $a['contract_amount'],
                'is_taxable' => $a['contract_tax_option'], 'is_fixed' => $a['is_fixed'],
                'created_at' => date('d-m-Y h:i:s'),
            ]);
        }
        foreach ($c['other_payments'] as $a) {
            (new PayotherpaymentsModel())->insert([
                'payslip_id' => $payslipId, 'staff_id' => $staffId, 'salary_month' => $period,
                'pay_title' => $a['option_title'], 'pay_amount' => $a['contract_amount'],
                'is_taxable' => $a['contract_tax_option'], 'is_fixed' => $a['is_fixed'],
                'created_at' => date('d-m-Y h:i:s'),
            ]);
        }
        foreach ($c['statutory'] as $a) {
            (new PaystatutorydeductionsModel())->insert([
                'payslip_id' => $payslipId, 'staff_id' => $staffId, 'salary_month' => $period,
                'pay_title' => $a['option_title'], 'pay_amount' => $a['contract_amount'],
                'is_fixed' => $a['is_fixed'], 'created_at' => date('d-m-Y h:i:s'),
            ]);
        }
    }

    private function validPeriod(): ?string
    {
        $p = strip_tags(trim((string) $this->request->getPost('period')));
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $p) ? $p : null;
    }

    private function loadRun(int $companyId): ?array
    {
        $key = strip_tags(trim((string) $this->request->getPost('run_key')));
        if ($key === '') {
            return null;
        }
        return (new PayrollRunModel())->where('company_id', $companyId)->where('run_key', $key)->first();
    }

    /**
     * Resolve (company_id, user_info) and enforce the payroll role resource.
     * $ajax=true returns a JSON ResponseInterface on failure; otherwise a redirect.
     *
     * @return array{0:int,1:array}|\CodeIgniter\HTTP\ResponseInterface
     */
    private function guard(string $resource, bool $ajax = false)
    {
        $session  = \Config\Services::session();
        $usession = $session->get('sup_username');
        if (! is_array($usession) || empty($usession['sup_user_id'])) {
            return $ajax ? $this->json(['error' => 'Not authenticated.']) : redirect()->to(site_url('erp/login'));
        }
        $userInfo = (new UsersModel())->where('user_id', $usession['sup_user_id'])->first();
        if (empty($userInfo) || ($userInfo['user_type'] !== 'company' && $userInfo['user_type'] !== 'staff')) {
            return $ajax ? $this->json(['error' => 'Unauthorized.']) : redirect()->to(site_url('erp/desk'));
        }
        if ($userInfo['user_type'] !== 'company' && ! in_array($resource, staff_role_resource(), true)) {
            return $ajax ? $this->json(['error' => 'Unauthorized.']) : redirect()->to(site_url('erp/desk'));
        }
        $companyId = $userInfo['user_type'] === 'staff'
            ? (int) $userInfo['company_id']
            : (int) $userInfo['user_id'];
        return [$companyId, $userInfo];
    }

    private function audit(string $action, int $companyId, int $entityId, string $summary): void
    {
        try {
            service('audit')->record($action, [
                'entity_type' => 'payroll_run', 'entity_id' => $entityId,
                'company_id'  => $companyId, 'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            // auditing is best-effort
        }
    }

    private function json(array $payload): \CodeIgniter\HTTP\ResponseInterface
    {
        $payload['csrf_hash'] = csrf_hash();
        return $this->response->setJSON($payload);
    }
}
