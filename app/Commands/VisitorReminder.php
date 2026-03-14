<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Finds visitors who checked in more than 8 hours ago with no check-out
 * and logs a reminder. Run hourly via cron.
 *
 * Usage:
 *   php spark visitors:remind-checkout
 *
 * Cron:
 *   0 * * * * cd /var/www/html && php spark visitors:remind-checkout >> /var/log/visitor-reminder.log 2>&1
 */
class VisitorReminder extends BaseCommand
{
    protected $group       = 'Visitors';
    protected $name        = 'visitors:remind-checkout';
    protected $description = 'Remind reception about visitors who have not checked out after 8 hours';

    public function run(array $params)
    {
        CLI::write('Visitor checkout reminder started at ' . date('Y-m-d H:i:s'), 'green');

        $db       = \Config\Database::connect();
        $cutoff   = date('Y-m-d H:i:s', strtotime('-8 hours'));

        $visitors = $db->table('ci_visitors')
            ->where('check_out IS NULL')
            ->where('check_in IS NOT NULL')
            ->where('check_in <=', $cutoff)
            ->get()
            ->getResultArray();

        $count = count($visitors);

        if ($count === 0) {
            CLI::write('No overdue visitors found.', 'yellow');
            return;
        }

        CLI::write("Found {$count} visitor(s) checked in > 8 hours without checkout:", 'light_red');

        foreach ($visitors as $v) {
            $name    = $v['visitor_name'] ?? 'Unknown';
            $checkIn = $v['check_in']     ?? 'N/A';
            $id      = $v['visitor_id']   ?? '';

            CLI::write("  [ID {$id}] {$name} - checked in at {$checkIn}", 'yellow');

            log_message('warning', "Visitor overdue checkout: ID={$id}, Name={$name}, CheckIn={$checkIn}");
        }

        CLI::write('Reminder processing complete.', 'green');
    }
}
