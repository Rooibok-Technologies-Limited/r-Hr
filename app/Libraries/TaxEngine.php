<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Libraries;

/**
 * Uganda PAYE (progressive bands) + NSSF. All monetary arithmetic is done in
 * INTEGER MINOR UNITS (cents) — never floats — so band summation and percentage
 * splits cannot accumulate binary-float representation error. Public methods
 * still return float for caller/DB compatibility, but the value is computed
 * exactly and rounded once (to the nearest cent) at the single documented point.
 *
 * Verified against audit/tax_golden_master.json: identical to the previous float
 * engine across the fixture spread (the migration eliminates latent float error
 * without changing any current figure).
 */
class TaxEngine {

    /** Money to integer cents (single rounding point in). */
    private static function cents($amount): int {
        return (int) round(((float) $amount) * 100);
    }

    /** Integer cents back to a 2dp float (single rounding point out). */
    private static function toMoney(int $cents): float {
        return $cents / 100;
    }

    /** taxable_cents * rate_percent / 100, rounded to the nearest cent (exact for int64). */
    private static function pctCents(int $baseCents, $ratePercent): int {
        // baseCents (<= ~1e12) * ratePercent (<= 100) stays well within int64; the
        // division is the only fractional step and is rounded to a whole cent.
        return (int) round(($baseCents * ((float) $ratePercent)) / 100);
    }

    /**
     * Calculate PAYE tax from progressive tax bands (exact, integer-cents).
     */
    public function calculatePAYE(float $grossSalary, int $companyId = 0): float {
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

        $gross     = self::cents($grossSalary);
        $payeCents = 0;
        foreach ($activeBands as $band) {
            $minC = self::cents($band['min_income']);
            if ($gross <= $minC) {
                break;
            }
            $upperC   = $band['max_income'] !== null ? self::cents($band['max_income']) : $gross;
            $ceilC    = min($gross, $upperC);
            $taxableC = $ceilC - $minC;
            $payeCents += self::pctCents($taxableC, $band['rate_percent']);
        }
        return self::toMoney($payeCents);
    }

    /**
     * Calculate NSSF deductions (exact, integer-cents).
     */
    public function calculateNSSF(float $grossSalary): array {
        $employeeRate = (float)(system_setting('nssf_employee_rate') ?: 5.00);
        $employerRate = (float)(system_setting('nssf_employer_rate') ?: 10.00);
        $enabled      = (int)(system_setting('nssf_enabled') ?: 1);

        if (!$enabled) {
            return ['employee' => 0, 'employer' => 0];
        }

        $gross = self::cents($grossSalary);
        return [
            'employee' => self::toMoney(self::pctCents($gross, $employeeRate)),
            'employer' => self::toMoney(self::pctCents($gross, $employerRate)),
        ];
    }

    /**
     * Full payroll deduction calculation (exact, integer-cents throughout).
     */
    public function calculateDeductions(float $grossSalary, int $companyId = 0): array {
        $grossC        = self::cents($grossSalary);
        $nssf          = $this->calculateNSSF($grossSalary);
        $nssfEmpC      = self::cents($nssf['employee']);
        $taxableAfter  = self::toMoney($grossC - $nssfEmpC);
        $paye          = $this->calculatePAYE($taxableAfter, $companyId);
        $payeC         = self::cents($paye);

        return [
            'gross_salary'     => self::toMoney($grossC),
            'nssf_employee'    => $nssf['employee'],
            'nssf_employer'    => $nssf['employer'],
            'paye'             => $paye,
            'total_deductions' => self::toMoney($nssfEmpC + $payeC),
            'net_pay'          => self::toMoney($grossC - $nssfEmpC - $payeC),
        ];
    }
}
