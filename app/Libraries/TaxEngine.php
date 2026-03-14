<?php
namespace App\Libraries;

class TaxEngine {

    /**
     * Calculate PAYE tax from progressive tax bands
     */
    public function calculatePAYE(float $grossSalary, int $companyId = 0): float {
        $db = \Config\Database::connect();

        // Check company-specific bands first, fall back to system default (company_id=0)
        $bands = $db->table('ci_paye_bands')
            ->where('is_active', 1)
            ->groupStart()
                ->where('company_id', $companyId)
                ->orWhere('company_id', 0)
            ->groupEnd()
            ->orderBy('company_id', 'DESC')  // company-specific first
            ->orderBy('min_income', 'ASC')
            ->get()->getResultArray();

        // If company has own bands, use those; otherwise use system defaults
        $companyBands = array_filter($bands, fn($b) => $b['company_id'] == $companyId);
        $activeBands = !empty($companyBands) ? array_values($companyBands) : array_values(array_filter($bands, fn($b) => $b['company_id'] == 0));

        $paye = 0;
        foreach ($activeBands as $band) {
            if ($grossSalary <= $band['min_income']) break;
            $upper = $band['max_income'] ?? $grossSalary;
            $taxable = min($grossSalary, $upper) - $band['min_income'];
            $paye += $taxable * ($band['rate_percent'] / 100);
        }
        return round($paye, 2);
    }

    /**
     * Calculate NSSF deductions
     */
    public function calculateNSSF(float $grossSalary): array {
        $employeeRate = (float)(system_setting('nssf_employee_rate') ?: 5.00);
        $employerRate = (float)(system_setting('nssf_employer_rate') ?: 10.00);
        $enabled = (int)(system_setting('nssf_enabled') ?: 1);

        if (!$enabled) {
            return ['employee' => 0, 'employer' => 0];
        }

        return [
            'employee' => round($grossSalary * ($employeeRate / 100), 2),
            'employer' => round($grossSalary * ($employerRate / 100), 2),
        ];
    }

    /**
     * Full payroll deduction calculation
     */
    public function calculateDeductions(float $grossSalary, int $companyId = 0): array {
        $nssf = $this->calculateNSSF($grossSalary);
        $taxableAfterNssf = $grossSalary - $nssf['employee'];
        $paye = $this->calculatePAYE($taxableAfterNssf, $companyId);

        return [
            'gross_salary' => $grossSalary,
            'nssf_employee' => $nssf['employee'],
            'nssf_employer' => $nssf['employer'],
            'paye' => $paye,
            'total_deductions' => $nssf['employee'] + $paye,
            'net_pay' => round($grossSalary - $nssf['employee'] - $paye, 2),
        ];
    }
}
