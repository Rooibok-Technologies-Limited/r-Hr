<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Libraries;

use App\Models\ContractModel;
use App\Models\AdvancesalaryModel;
use App\Models\StaffdetailsModel;
use App\Models\MainModel;

/**
 * PayrollCalculator (ADR-001) — the single authoritative payslip computation,
 * extracted verbatim from Payroll::add_pay_monthly so the run wizard's PREVIEW,
 * the bulk COMMIT, and the legacy single-employee generator all agree.
 *
 * Pay components come from ci_contract_options (allowances / commissions /
 * other_payments / statutory) summed per employee; basic salary from
 * ci_erp_users_details; PAYE + NSSF from TaxEngine (Uganda). Advance-salary and
 * loan repayments are computed for display + payslip fields.
 *
 * FIDELITY NOTE: matching the existing generator, net_salary =
 *   basic + allowances + commissions + other − statutory − nssf_employee − paye
 * i.e. advance/loan repayments are NOT subtracted from take-home here (they only
 * advance the loan's total_paid). Preserved deliberately; revisit as a separate
 * payroll-correctness decision, not silently inside this refactor.
 *
 * compute() is pure UNLESS $persist=true, in which case it advances the
 * advance/loan `total_paid` (the real side effect of committing a payslip).
 * Preview MUST call with $persist=false.
 */
class PayrollCalculator
{
    /**
     * @return array{
     *   staff_id:int, wages_type:mixed, basic_salary:float,
     *   allowances:array, commissions:array, other_payments:array, statutory:array,
     *   total_allowances:float, total_commissions:float, total_other_payments:float,
     *   total_statutory_deductions:float, gross_for_tax:float,
     *   nssf_employee:float, nssf_employer:float, paye_tax:float,
     *   advance_amount:float, is_advance_salary_deduct:int,
     *   loan_amount:float, is_loan_deduct:int, net_salary:float
     * }
     */
    public function compute(int $staffId, string $period, int $companyId, bool $persist = false): array
    {
        $contract = new ContractModel();

        // Recurring pay components (ci_contract_options), summed per type.
        $allowances   = $contract->where('user_id', $staffId)->where('salay_type', 'allowances')->findAll();
        $commissions  = $contract->where('user_id', $staffId)->where('salay_type', 'commissions')->findAll();
        $otherPays    = $contract->where('user_id', $staffId)->where('salay_type', 'other_payments')->findAll();
        $statutory    = $contract->where('user_id', $staffId)->where('salay_type', 'statutory')->findAll();

        $sum = static fn (array $rows): float => array_reduce(
            $rows,
            static fn (float $c, array $r): float => $c + (float) $r['contract_amount'],
            0.0
        );
        $allowanceAmount    = $sum($allowances);
        $commissionsAmount  = $sum($commissions);
        $otherPaymentsAmount = $sum($otherPays);
        $statutoryAmount    = $sum($statutory);

        // Advance-salary + loan repayment for this run.
        [$advanceAmount, $isAdvanceDeducted] = $this->repayment($staffId, 'advance', $persist);
        [$loanAmount, $isLoanDeducted]       = $this->repayment($staffId, 'loan', $persist);

        // Basic salary + Uganda statutory (PAYE/NSSF).
        $staffDetails = new StaffdetailsModel();
        $detail       = $staffDetails->where('user_id', $staffId)->first();
        $basicSalary  = (float) ($detail['basic_salary'] ?? 0);
        $wagesType    = $detail['salay_type'] ?? null;

        // Gross + net summed in INTEGER MINOR UNITS (cents) so the reconciliation
        // (net == gross − statutory − nssf − paye) is exact, never float-drifted.
        $c = static fn ($v): int => (int) round(((float) $v) * 100);
        $grossForTaxC = $c($basicSalary) + $c($allowanceAmount) + $c($commissionsAmount) + $c($otherPaymentsAmount);
        $grossForTax  = $grossForTaxC / 100;
        $tax          = (new TaxEngine())->calculateDeductions($grossForTax, $companyId);
        $nssfEmployee = (float) $tax['nssf_employee'];
        $nssfEmployer = (float) $tax['nssf_employer'];
        $paye         = (float) $tax['paye'];

        // Net — identical formula to the legacy generator (see FIDELITY NOTE), in cents.
        $netC = $grossForTaxC - $c($statutoryAmount) - $c($nssfEmployee) - $c($paye);
        $net  = $netC / 100;

        return [
            'staff_id'                   => $staffId,
            'wages_type'                 => $wagesType,
            'basic_salary'               => $basicSalary,
            'allowances'                 => $allowances,
            'commissions'                => $commissions,
            'other_payments'             => $otherPays,
            'statutory'                  => $statutory,
            'total_allowances'           => $allowanceAmount,
            'total_commissions'          => $commissionsAmount,
            'total_other_payments'       => $otherPaymentsAmount,
            'total_statutory_deductions' => $statutoryAmount,
            'gross_for_tax'              => $grossForTax,
            'nssf_employee'              => $nssfEmployee,
            'nssf_employer'              => $nssfEmployer,
            'paye_tax'                   => $paye,
            'advance_amount'             => $advanceAmount,
            'is_advance_salary_deduct'   => $isAdvanceDeducted,
            'loan_amount'                => $loanAmount,
            'is_loan_deduct'             => $isLoanDeducted,
            'net_salary'                 => $net,
        ];
    }

    /**
     * Compute the advance/loan repayment for this run. Mirrors the legacy logic:
     * one_time_deduct clears the balance; otherwise an installment (capped at the
     * remaining balance). Returns [newTotalPaid, isDeductedFlag] — matching the
     * legacy fields exactly. Persists the advanced total_paid only when $persist.
     */
    private function repayment(int $staffId, string $type, bool $persist): array
    {
        $model = new AdvancesalaryModel();
        $rec = $model->where('employee_id', $staffId)->where('status', 1)
                     ->where('salary_type', $type)->first();

        if (! $rec || (float) $rec['advance_amount'] === (float) $rec['total_paid']) {
            return [0.0, 0];
        }

        $advance     = (float) $rec['advance_amount'];
        $totalPaid   = (float) $rec['total_paid'];
        $installment = (float) $rec['monthly_installment'];
        $remaining   = $advance - $totalPaid;

        if ((int) $rec['one_time_deduct'] === 1) {
            $newTotalPaid = $advance;                       // clear the balance
        } else {
            $step         = ($installment > $remaining) ? $remaining : $installment;
            $newTotalPaid = $totalPaid + $step;
        }

        if ($persist) {
            (new MainModel())->update_advance_salary_record(
                ['total_paid' => $newTotalPaid], $staffId, $type
            );
        }

        return [$newTotalPaid, 1];
    }
}
