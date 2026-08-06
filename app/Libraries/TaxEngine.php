<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Libraries;

/**
 * Uganda PAYE (progressive bands) + NSSF. All monetary arithmetic is done in
 * INTEGER MINOR UNITS at the COMPANY'S CURRENCY PRECISION — never floats — so
 * band summation and percentage splits cannot accumulate binary-float error, and
 * the rounding granularity matches the real currency: UGX (and other zero-decimal
 * currencies) round to WHOLE SHILLINGS (statutorily correct — UGX has no subunit),
 * USD/GBP/EUR to 2dp. The factor (10^decimals) is resolved from the company's
 * default_currency (worker-safe DB read). Public methods return float for caller/
 * DB compatibility; the value is computed exactly and rounded once at minor()/
 * toMoney(). Golden master: audit/tax_golden_master.json.
 */
class TaxEngine {

    /** Minor-unit factor for the active precision (10^decimals): 1 for UGX, 100 for USD. */
    private int $factor = 100;

    /** Money to integer minor units at the currency's real precision. */
    private function minor($amount): int {
        return (int) round(((float) $amount) * $this->factor);
    }

    /** Integer minor units back to a money float at the currency's precision. */
    private function toMoney(int $units): float {
        return $this->factor === 1 ? (float) $units : $units / $this->factor;
    }

    /** base * rate_percent / 100, rounded to the nearest minor unit (exact for int64). */
    private function pct(int $base, $ratePercent): int {
        return (int) round(($base * ((float) $ratePercent)) / 100);
    }

    /** Resolve the minor-unit factor from the company's currency (UGX=1, USD=100). */
    private function resolveFactor(int $companyId): void {
        $decimals = function_exists('company_currency_decimals')
            ? company_currency_decimals($companyId)
            : 2;
        $this->factor = (int) (10 ** $decimals);
    }

    /**
     * Calculate PAYE tax from progressive tax bands (exact, integer-cents).
     */
    public function calculatePAYE(float $grossSalary, int $companyId = 0): float {
        $this->resolveFactor($companyId);
        $db = \Config\Database::connect();

        $bands = $db->table('ci_paye_bands')
            ->where('is_active', 1)
            ->groupStart()
                ->where('company_id', $companyId)
                ->orWhere('company_id', 0)
            ->groupEnd()
            ->orderBy('company_id', 'DESC')
            ->orderBy('min_income', 'ASC')
            ->get()->getResultArray();

        $companyBands = array_filter($bands, fn($b) => $b['company_id'] == $companyId);
        $activeBands  = !empty($companyBands)
            ? array_values($companyBands)
            : array_values(array_filter($bands, fn($b) => $b['company_id'] == 0));

        $gross     = $this->minor($grossSalary);
        $payeCents = 0;
        foreach ($activeBands as $band) {
            $minC = $this->minor($band['min_income']);
            if ($gross <= $minC) {
                break;
            }
            $upperC   = $band['max_income'] !== null ? $this->minor($band['max_income']) : $gross;
            $ceilC    = min($gross, $upperC);
            $taxableC = $ceilC - $minC;
            $payeCents += $this->pct($taxableC, $band['rate_percent']);
        }
        return $this->toMoney($payeCents);
    }

    /**
     * Calculate NSSF deductions (exact, integer-cents).
     */
    public function calculateNSSF(float $grossSalary, int $companyId = 0): array {
        $this->resolveFactor($companyId);
        $employeeRate = (float)(system_setting('nssf_employee_rate') ?: 5.00);
        $employerRate = (float)(system_setting('nssf_employer_rate') ?: 10.00);
        $enabled      = (int)(system_setting('nssf_enabled') ?: 1);

        if (!$enabled) {
            return ['employee' => 0, 'employer' => 0];
        }

        $gross = $this->minor($grossSalary);
        return [
            'employee' => $this->toMoney($this->pct($gross, $employeeRate)),
            'employer' => $this->toMoney($this->pct($gross, $employerRate)),
        ];
    }

    /**
     * Full payroll deduction calculation (exact, integer-cents throughout).
     */
    public function calculateDeductions(float $grossSalary, int $companyId = 0): array {
        $this->resolveFactor($companyId);
        $grossC        = $this->minor($grossSalary);
        $nssf          = $this->calculateNSSF($grossSalary, $companyId);
        $nssfEmpC      = $this->minor($nssf['employee']);
        $taxableAfter  = $this->toMoney($grossC - $nssfEmpC);
        $paye          = $this->calculatePAYE($taxableAfter, $companyId);
        $payeC         = $this->minor($paye);

        return [
            'gross_salary'     => $this->toMoney($grossC),
            'nssf_employee'    => $nssf['employee'],
            'nssf_employer'    => $nssf['employer'],
            'paye'             => $paye,
            'total_deductions' => $this->toMoney($nssfEmpC + $payeC),
            'net_pay'          => $this->toMoney($grossC - $nssfEmpC - $payeC),
        ];
    }
}
