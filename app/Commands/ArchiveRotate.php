<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Monthly age-based rotation of old records from live DB to archive DB.
 *
 * Thresholds:
 *   - Attendance (ci_timesheet)       > 24 months
 *   - Payroll    (ci_payslips)        > 36 months
 *   - System logs (ci_notifications)  > 12 months
 *   - Broadcasts (ci_broadcasts)      > 6 months
 *
 * Usage:
 *   php spark archive:rotate
 *
 * Cron (monthly):
 *   0 2 1 * * cd /var/www/html && php spark archive:rotate >> /var/log/archive.log 2>&1
 */
class ArchiveRotate extends BaseCommand
{
    protected $group       = 'Archive';
    protected $name        = 'archive:rotate';
    protected $description = 'Rotate old records from live DB to archive DB';

    // ------------------------------------------------------------------

    public function run(array $params)
    {
        CLI::write('Archive rotation started at ' . date('Y-m-d H:i:s'), 'green');

        $this->archiveOldAttendance();   // > 24 months
        $this->archiveOldPayroll();      // > 36 months
        $this->archiveOldSystemLogs();   // > 12 months
        $this->archiveOldBroadcasts();   // > 6 months

        CLI::write('Monthly rotation complete', 'green');
    }

    // ------------------------------------------------------------------
    //  Attendance — older than 24 months
    // ------------------------------------------------------------------

    private function archiveOldAttendance(): void
    {
        $cutoff = date('Y-m-d', strtotime('-24 months'));
        $liveDb = \Config\Database::connect('default');
        $archDb = \Config\Database::connect('archive');

        $rows = $liveDb->table('ci_timesheet')
            ->where('attendance_date <', $cutoff)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            CLI::write('  Attendance: nothing to archive', 'light_gray');
            return;
        }

        foreach ($rows as $row) {
            // Denormalise employee name for the archive
            $user = $liveDb->table('ci_erp_users')
                ->select('first_name, last_name')
                ->where('user_id', $row['employee_id'])
                ->get()
                ->getRowArray();

            $archRow = [
                'source_record_id'  => $row['time_attendance_id'],
                'source_company_id' => $row['company_id'],
                'employee_id'       => $row['employee_id'],
                'employee_name'     => $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Unknown',
                'attendance_date'   => $row['attendance_date'],
                'clock_in'          => $row['clock_in'] ?: null,
                'clock_out'         => $row['clock_out'] ?: null,
                'total_work'        => $row['total_work'] ?? null,
                'attendance_status' => $row['attendance_status'] ?? null,
            ];
            $archDb->table('arc_attendance')->insert($archRow);
        }

        $ids = array_column($rows, 'time_attendance_id');
        $liveDb->table('ci_timesheet')->whereIn('time_attendance_id', $ids)->delete();

        CLI::write('  Archived ' . count($rows) . ' attendance records', 'yellow');
    }

    // ------------------------------------------------------------------
    //  Payroll (payslips) — older than 36 months
    // ------------------------------------------------------------------

    private function archiveOldPayroll(): void
    {
        $cutoff = date('Y-m', strtotime('-36 months')); // e.g. "2023-03"
        $liveDb = \Config\Database::connect('default');
        $archDb = \Config\Database::connect('archive');

        $rows = $liveDb->table('ci_payslips')
            ->where('salary_month <', $cutoff)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            CLI::write('  Payroll: nothing to archive', 'light_gray');
            return;
        }

        foreach ($rows as $row) {
            // Denormalise employee name
            $user = $liveDb->table('ci_erp_users')
                ->select('first_name, last_name')
                ->where('user_id', $row['staff_id'])
                ->get()
                ->getRowArray();

            $archRow = [
                'source_record_id'  => $row['payslip_id'],
                'source_company_id' => $row['company_id'],
                'employee_id'       => $row['staff_id'],
                'employee_name'     => $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Unknown',
                'payroll_month'     => $row['salary_month'],
                'gross_salary'      => $row['basic_salary'] + ($row['total_allowances'] ?? 0) + ($row['total_commissions'] ?? 0) + ($row['total_other_payments'] ?? 0),
                'paye_deduction'    => $row['paye_tax'] ?? 0,
                'nssf_employee'     => $row['nssf_employee'] ?? 0,
                'nssf_employer'     => $row['nssf_employer'] ?? 0,
                'net_pay'           => $row['net_salary'],
                'currency'          => 'UGX',
            ];
            $archDb->table('arc_payroll')->insert($archRow);
        }

        $ids = array_column($rows, 'payslip_id');
        $liveDb->table('ci_payslips')->whereIn('payslip_id', $ids)->delete();

        CLI::write('  Archived ' . count($rows) . ' payroll records', 'yellow');
    }

    // ------------------------------------------------------------------
    //  System logs (notifications) — older than 12 months
    // ------------------------------------------------------------------

    private function archiveOldSystemLogs(): void
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-12 months'));
        $liveDb = \Config\Database::connect('default');
        $archDb = \Config\Database::connect('archive');

        $rows = $liveDb->table('ci_notifications')
            ->where('created_at <', $cutoff)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            CLI::write('  System logs: nothing to archive', 'light_gray');
            return;
        }

        foreach ($rows as $row) {
            $archRow = [
                'source_company_id' => $row['company_id'] ?? null,
                'log_type'          => 'notification',
                'description'       => $row['title'] . ': ' . ($row['body'] ?? ''),
                'metadata'          => json_encode([
                    'notification_id' => $row['notification_id'],
                    'user_id'         => $row['user_id'],
                    'link'            => $row['link'] ?? null,
                    'is_read'         => $row['is_read'] ?? 0,
                ]),
                'log_date'          => $row['created_at'],
            ];
            $archDb->table('arc_system_logs')->insert($archRow);
        }

        $ids = array_column($rows, 'notification_id');
        $liveDb->table('ci_notifications')->whereIn('notification_id', $ids)->delete();

        // Also archive old billing reminder logs
        $billingRows = $liveDb->table('ci_billing_reminders_log')
            ->where('sent_at <', $cutoff)
            ->get()
            ->getResultArray();

        if (! empty($billingRows)) {
            foreach ($billingRows as $row) {
                $archRow = [
                    'source_company_id' => $row['company_id'],
                    'log_type'          => 'billing_reminder',
                    'description'       => "Billing reminder day {$row['reminder_day']} via {$row['channel']}",
                    'metadata'          => json_encode([
                        'log_id'       => $row['log_id'],
                        'reminder_day' => $row['reminder_day'],
                        'channel'      => $row['channel'],
                    ]),
                    'log_date'          => $row['sent_at'],
                ];
                $archDb->table('arc_system_logs')->insert($archRow);
            }

            $ids = array_column($billingRows, 'log_id');
            $liveDb->table('ci_billing_reminders_log')->whereIn('log_id', $ids)->delete();
        }

        $total = count($rows) + count($billingRows);
        CLI::write("  Archived {$total} system log records", 'yellow');
    }

    // ------------------------------------------------------------------
    //  Broadcasts — older than 6 months
    // ------------------------------------------------------------------

    private function archiveOldBroadcasts(): void
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-6 months'));
        $liveDb = \Config\Database::connect('default');
        $archDb = \Config\Database::connect('archive');

        $broadcasts = $liveDb->table('ci_broadcasts')
            ->where('created_at <', $cutoff)
            ->get()
            ->getResultArray();

        if (empty($broadcasts)) {
            CLI::write('  Broadcasts: nothing to archive', 'light_gray');
            return;
        }

        $totalLogs = 0;

        foreach ($broadcasts as $broadcast) {
            // Archive each broadcast as a system log entry with full metadata
            $archRow = [
                'source_company_id' => $broadcast['company_id'] ?? null,
                'log_type'          => 'broadcast',
                'description'       => $broadcast['subject'],
                'metadata'          => json_encode([
                    'broadcast_id'    => $broadcast['broadcast_id'],
                    'broadcast_type'  => $broadcast['broadcast_type'],
                    'audience_type'   => $broadcast['audience_type'],
                    'channels'        => $broadcast['channels'],
                    'status'          => $broadcast['status'],
                    'total_recipients' => $broadcast['total_recipients'],
                    'body_html'       => $broadcast['body_html'],
                    'body_sms'        => $broadcast['body_sms'],
                    'scheduled_at'    => $broadcast['scheduled_at'],
                    'sent_at'         => $broadcast['sent_at'],
                ]),
                'log_date'          => $broadcast['created_at'],
            ];
            $archDb->table('arc_system_logs')->insert($archRow);

            // Also archive the associated broadcast log entries
            $logs = $liveDb->table('ci_broadcast_log')
                ->where('broadcast_id', $broadcast['broadcast_id'])
                ->get()
                ->getResultArray();

            if (! empty($logs)) {
                foreach ($logs as $log) {
                    $logRow = [
                        'source_company_id' => $broadcast['company_id'] ?? null,
                        'log_type'          => 'broadcast_delivery',
                        'description'       => "Broadcast #{$broadcast['broadcast_id']} to {$log['recipient_email']}",
                        'metadata'          => json_encode([
                            'log_id'         => $log['log_id'],
                            'broadcast_id'   => $log['broadcast_id'],
                            'recipient_id'   => $log['recipient_id'],
                            'recipient_type' => $log['recipient_type'],
                            'inapp_sent'     => $log['inapp_sent'],
                            'email_sent'     => $log['email_sent'],
                            'sms_sent'       => $log['sms_sent'],
                            'error_message'  => $log['error_message'],
                        ]),
                        'log_date'          => $log['sent_at'] ?? $broadcast['created_at'],
                    ];
                    $archDb->table('arc_system_logs')->insert($logRow);
                }

                $logIds = array_column($logs, 'log_id');
                $liveDb->table('ci_broadcast_log')->whereIn('log_id', $logIds)->delete();
                $totalLogs += count($logs);
            }
        }

        // Delete the broadcasts after their logs have been removed
        $broadcastIds = array_column($broadcasts, 'broadcast_id');
        $liveDb->table('ci_broadcasts')->whereIn('broadcast_id', $broadcastIds)->delete();

        CLI::write('  Archived ' . count($broadcasts) . " broadcasts and {$totalLogs} delivery logs", 'yellow');
    }
}
