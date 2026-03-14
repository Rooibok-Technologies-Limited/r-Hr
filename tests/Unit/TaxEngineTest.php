<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Libraries\TaxEngine;

/**
 * Unit tests for TaxEngine — PAYE and NSSF calculations.
 *
 * Note: PAYE tests require the ci_paye_bands table to be seeded with the
 * standard URA bands (system default, company_id = 0).
 */
class TaxEngineTest extends TestCase
{
    private TaxEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new TaxEngine();

        // Seed the default URA PAYE bands (company_id = 0)
        $db = \Config\Database::connect();

        // Ensure the table exists with expected structure before seeding
        if ($db->tableExists('ci_paye_bands')) {
            $db->table('ci_paye_bands')->truncate();
        }

        $bands = [
            ['company_id' => 0, 'min_income' => 0,      'max_income' => 235000,  'rate_percent' => 0,  'is_active' => 1],
            ['company_id' => 0, 'min_income' => 235000,  'max_income' => 335000,  'rate_percent' => 10, 'is_active' => 1],
            ['company_id' => 0, 'min_income' => 335000,  'max_income' => 410000,  'rate_percent' => 20, 'is_active' => 1],
            ['company_id' => 0, 'min_income' => 410000,  'max_income' => 10000000,'rate_percent' => 30, 'is_active' => 1],
            ['company_id' => 0, 'min_income' => 10000000,'max_income' => null,    'rate_percent' => 40, 'is_active' => 1],
        ];

        foreach ($bands as $band) {
            $db->table('ci_paye_bands')->insert($band);
        }
    }

    // ---------------------------------------------------------------
    //  PAYE Calculation Tests
    // ---------------------------------------------------------------

    public function testZeroIncomeReturnsZeroPaye(): void
    {
        $paye = $this->engine->calculatePAYE(0);
        $this->assertEquals(0, $paye, 'PAYE on zero income should be 0');
    }

    public function testIncomeInFirstBandReturnsZeroPaye(): void
    {
        // 0 – 235,000 UGX is tax-free
        $paye = $this->engine->calculatePAYE(200000);
        $this->assertEquals(0, $paye, 'Income within first band (0-235000) should have 0 PAYE');
    }

    public function testIncomeAtExactFirstBandCeiling(): void
    {
        $paye = $this->engine->calculatePAYE(235000);
        $this->assertEquals(0, $paye, 'Income exactly at 235,000 should have 0 PAYE');
    }

    public function testIncomeInSecondBandReturns10Percent(): void
    {
        // 300,000 UGX → taxable in second band = 300,000 - 235,000 = 65,000
        // PAYE = 65,000 * 10% = 6,500
        $paye = $this->engine->calculatePAYE(300000);
        $this->assertEquals(6500, $paye, 'PAYE for 300,000 should be 6,500 (10% of 65,000)');
    }

    public function testIncomeAcrossMultipleBands(): void
    {
        // 500,000 UGX
        // Band 1: 0-235,000 → 0
        // Band 2: 235,000-335,000 → 100,000 * 10% = 10,000
        // Band 3: 335,000-410,000 → 75,000 * 20% = 15,000
        // Band 4: 410,000-500,000 → 90,000 * 30% = 27,000
        // Total PAYE = 52,000
        $paye = $this->engine->calculatePAYE(500000);
        $this->assertEquals(52000, $paye, 'PAYE for 500,000 should be 52,000');
    }

    public function testHighIncomeReachesTopBand(): void
    {
        // 15,000,000 UGX
        // Band 1: 0-235,000 → 0
        // Band 2: 235,000-335,000 → 100,000 * 10% = 10,000
        // Band 3: 335,000-410,000 → 75,000 * 20% = 15,000
        // Band 4: 410,000-10,000,000 → 9,590,000 * 30% = 2,877,000
        // Band 5: 10,000,000-15,000,000 → 5,000,000 * 40% = 2,000,000
        // Total = 4,902,000
        $paye = $this->engine->calculatePAYE(15000000);
        $this->assertEquals(4902000, $paye, 'PAYE for 15,000,000 should be 4,902,000');
    }

    // ---------------------------------------------------------------
    //  NSSF Calculation Tests
    // ---------------------------------------------------------------

    public function testNssfCalculationWithDefaultRates(): void
    {
        // Default rates: 5% employee, 10% employer
        $nssf = $this->engine->calculateNSSF(1000000);
        $this->assertEquals(50000, $nssf['employee'], 'NSSF employee contribution should be 5% of gross');
        $this->assertEquals(100000, $nssf['employer'], 'NSSF employer contribution should be 10% of gross');
    }

    public function testNssfDisabledReturnsZero(): void
    {
        // Temporarily disable NSSF via system_setting mock
        // Since system_setting reads from DB, we'll rely on integration context.
        // If nssf_enabled is set to 0, both should return 0.
        $db = \Config\Database::connect();
        if ($db->tableExists('ci_system_settings')) {
            $db->table('ci_system_settings')
               ->where('setting_key', 'nssf_enabled')
               ->update(['setting_value' => '0']);

            // Clear any cached values
            if (function_exists('system_setting_clear_cache')) {
                system_setting_clear_cache();
            }

            $nssf = $this->engine->calculateNSSF(1000000);
            $this->assertEquals(0, $nssf['employee'], 'NSSF employee should be 0 when disabled');
            $this->assertEquals(0, $nssf['employer'], 'NSSF employer should be 0 when disabled');

            // Restore
            $db->table('ci_system_settings')
               ->where('setting_key', 'nssf_enabled')
               ->update(['setting_value' => '1']);
        } else {
            $this->markTestSkipped('ci_system_settings table not available');
        }
    }

    // ---------------------------------------------------------------
    //  Full Deduction Tests
    // ---------------------------------------------------------------

    public function testFullDeductionCalculation(): void
    {
        $result = $this->engine->calculateDeductions(1000000);

        $this->assertArrayHasKey('gross_salary', $result);
        $this->assertArrayHasKey('nssf_employee', $result);
        $this->assertArrayHasKey('nssf_employer', $result);
        $this->assertArrayHasKey('paye', $result);
        $this->assertArrayHasKey('total_deductions', $result);
        $this->assertArrayHasKey('net_pay', $result);

        $this->assertEquals(1000000, $result['gross_salary']);

        // Net pay = gross - nssf_employee - paye
        $expectedNet = $result['gross_salary'] - $result['nssf_employee'] - $result['paye'];
        $this->assertEquals($expectedNet, $result['net_pay'], 'Net pay should equal gross minus employee NSSF minus PAYE');

        // Total deductions = nssf_employee + paye
        $this->assertEquals(
            $result['nssf_employee'] + $result['paye'],
            $result['total_deductions'],
            'Total deductions should equal NSSF employee + PAYE'
        );
    }
}
