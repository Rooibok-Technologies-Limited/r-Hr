<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Reset mutable data for the demo company so it stays clean.
 *
 * Usage:
 *   php spark demo:reset
 */
class DemoReset extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:reset';
    protected $description = 'Reset the demo company data (attendance, leave, payroll)';

    public function run(array $params)
    {
        CLI::write('Demo reset started at ' . date('Y-m-d H:i:s'), 'green');

        $db = \Config\Database::connect();

        // Find the demo company user
        $demoUser = $db->table('ci_erp_users')
            ->where('is_demo', 1)
            ->where('user_type', 'company')
            ->get()
            ->getRowArray();

        if (! $demoUser) {
            CLI::write('No demo company found. Mark a user with is_demo = 1.', 'red');
            return;
        }

        $companyId = $demoUser['company_id'] ?? $demoUser['user_id'];

        CLI::write("Demo company ID: {$companyId}", 'yellow');

        // Truncate mutable data for the demo company
        $tables = [
            'ci_attendance'      => 'company_id',
            'ci_leave'           => 'company_id',
            'ci_payroll'         => 'company_id',
            'ci_advance_salary'  => 'company_id',
            'ci_overtime'        => 'company_id',
        ];

        foreach ($tables as $table => $column) {
            // Check if table exists before attempting delete
            if ($db->tableExists($table)) {
                $deleted = $db->table($table)
                    ->where($column, $companyId)
                    ->delete();
                $count = $db->affectedRows();
                CLI::write("  {$table}: {$count} rows deleted", 'light_gray');
            } else {
                CLI::write("  {$table}: table not found — skipped", 'light_gray');
            }
        }

        CLI::write('Demo data reset complete', 'green');
    }
}
