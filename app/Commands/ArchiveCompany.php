<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\ContactExtractor;
use App\Libraries\Queue;

/**
 * Full company archiving — triggered manually or after 90 days expired.
 *
 * Steps:
 *   1. Create snapshot in arc_company_snapshots
 *   2. Archive all employees
 *   3. Archive all attendance, payroll, leave
 *   4. Archive system logs
 *   5. Extract contacts into arc_contacts
 *   6. Queue vault bundle generation
 *
 * Usage:
 *   php spark archive:company --company_id=42
 */
class ArchiveCompany extends BaseCommand
{
    protected $group       = 'Archive';
    protected $name        = 'archive:company';
    protected $description = 'Archive an entire company and queue vault bundle generation';

    // ------------------------------------------------------------------

    public function run(array $params)
    {
        $companyId = (int) ($params[0] ?? CLI::getOption('company_id'));

        if (! $companyId) {
            CLI::error('Usage: php spark archive:company --company_id=<id>');
            return;
        }

        CLI::write("Archiving company #{$companyId} at " . date('Y-m-d H:i:s'), 'green');

        $liveDb = \Config\Database::connect('default');
        $archDb = \Config\Database::connect('archive');

        // Verify the company exists
        $company = $liveDb->table('ci_erp_users')
            ->where('user_id', $companyId)
            ->where('user_type', 'company')
            ->get()
            ->getRowArray();

        if (! $company) {
            // Try by company_id field
            $company = $liveDb->table('ci_erp_users')
                ->where('company_id', $companyId)
                ->where('user_type', 'company')
                ->get()
                ->getRowArray();
        }

        if (! $company) {
            CLI::error("Company #{$companyId} not found.");
            return;
        }

        // Step 1: Create snapshot
        $snapshotId = $this->createSnapshot($liveDb, $archDb, $company, $companyId);
        CLI::write("  Step 1: Snapshot created (ID: {$snapshotId})", 'yellow');

        // Step 2: Archive all employees
        $empCount = $this->archiveEmployees($liveDb, $archDb, $companyId, $snapshotId);
        CLI::write("  Step 2: Archived {$empCount} employees", 'yellow');

        // Step 3: Archive attendance, payroll, leave
        $attCount  = $this->archiveAttendance($liveDb, $archDb, $companyId);
        $payCount  = $this->archivePayroll($liveDb, $archDb, $companyId);
        $leaveCount = $this->archiveLeave($liveDb, $archDb, $companyId);
        CLI::write("  Step 3: Archived {$attCount} attendance, {$payCount} payroll, {$leaveCount} leave records", 'yellow');

        // Step 4: Archive system logs
        $logCount = $this->archiveSystemLogs($liveDb, $archDb, $companyId);
        CLI::write("  Step 4: Archived {$logCount} system log entries", 'yellow');

        // Step 5: Extract contacts
        $extractor = new ContactExtractor();
        $contactCount = $extractor->extractContacts($companyId, $snapshotId);
        CLI::write("  Step 5: Extracted {$contactCount} contacts", 'yellow');

        // Step 6: Queue vault bundle generation
        $this->queueVaultBundle($companyId);
        CLI::write('  Step 6: Vault bundle generation queued', 'yellow');

        CLI::write("Company #{$companyId} archive complete", 'green');
    }

    // ------------------------------------------------------------------
    //  Step 1: Company snapshot
    // ------------------------------------------------------------------

    private function createSnapshot($liveDb, $archDb, array $company, int $companyId): int
    {
        // Load company settings for additional details
        $settings = $liveDb->table('ci_erp_settings')
            ->where('setting_id', $companyId)
            ->get()
            ->getRowArray();

        // Load membership info
        $membership = $liveDb->table('ci_company_membership')
            ->where('company_id', $companyId)
            ->get()
            ->getRowArray();

        $planName = null;
        $planTier = null;
        if ($membership) {
            $plan = $liveDb->table('ci_membership')
                ->where('membership_id', $membership['membership_id'] ?? 0)
                ->get()
                ->getRowArray();
            $planName = $plan['membership_type'] ?? null;
            $planTier = $membership['subscription_type'] ?? null;
        }

        // Count employees
        $employeeCount = $liveDb->table('ci_erp_users')
            ->where('company_id', $companyId)
            ->where('user_type', 'employee')
            ->countAllResults();

        // Determine archive reason
        $isActive = $membership['is_active'] ?? 0;
        $archiveReason = $isActive ? 'manual' : 'expired';

        $snapshot = [
            'source_company_id'  => $companyId,
            'company_name'       => $company['company_name'] ?? ($settings['company_name'] ?? null),
            'trading_name'       => $company['trading_name'] ?? ($settings['trading_name'] ?? null),
            'admin_first_name'   => $company['first_name'],
            'admin_last_name'    => $company['last_name'],
            'admin_email'        => $company['email'],
            'admin_phone'        => $company['contact_number'] ?? null,
            'country'            => $settings['country'] ?? ($company['country'] ?? null),
            'city'               => $settings['city'] ?? ($company['city'] ?? null),
            'region'             => $settings['state'] ?? ($company['state'] ?? null),
            'company_type'       => null,
            'registration_no'    => $company['registration_no'] ?? ($settings['registration_no'] ?? null),
            'employee_count'     => $employeeCount,
            'plan_name'          => $planName,
            'plan_tier'          => $planTier,
            'subscription_start' => $membership['created_at'] ?? null,
            'subscription_end'   => $membership['expiry_date'] ?? null,
            'archive_reason'     => $archiveReason,
            'consent_given'      => $company['marketing_consent'] ?? 0,
            'consent_date'       => $company['consent_date'] ?? null,
        ];

        // Resolve company_type name from ci_general_options
        if ($company['company_type_id'] ?? null) {
            $companyType = $liveDb->table('ci_general_options')
                ->select('option_value')
                ->where('option_id', $company['company_type_id'])
                ->get()
                ->getRowArray();
            $snapshot['company_type'] = $companyType['option_value'] ?? null;
        }

        $archDb->table('arc_company_snapshots')->insert($snapshot);

        return $archDb->insertID();
    }

    // ------------------------------------------------------------------
    //  Step 2: Employees
    // ------------------------------------------------------------------

    private function archiveEmployees($liveDb, $archDb, int $companyId, int $snapshotId): int
    {
        $employees = $liveDb->table('ci_erp_users u')
            ->select('u.user_id, u.first_name, u.last_name, u.email, u.contact_number,
                      d.department_name, des.designation_name,
                      ud.date_of_joining, ud.date_of_leaving')
            ->join('ci_erp_users_details ud', 'ud.user_id = u.user_id', 'left')
            ->join('ci_departments d', 'd.department_id = ud.department_id', 'left')
            ->join('ci_designations des', 'des.designation_id = ud.designation_id', 'left')
            ->where('u.company_id', $companyId)
            ->where('u.user_type', 'employee')
            ->get()
            ->getResultArray();

        if (empty($employees)) {
            return 0;
        }

        foreach ($employees as $emp) {
            $archRow = [
                'source_record_id'  => $emp['user_id'],
                'source_company_id' => $companyId,
                'snapshot_id'       => $snapshotId,
                'first_name'        => $emp['first_name'],
                'last_name'         => $emp['last_name'],
                'email'             => $emp['email'],
                'phone'             => $emp['contact_number'] ?? null,
                'department'        => $emp['department_name'] ?? null,
                'designation'       => $emp['designation_name'] ?? null,
                'employment_type'   => 'full_time',
                'date_joined'       => $emp['date_of_joining'] ?? null,
                'date_left'         => $emp['date_of_leaving'] ?? null,
                'archive_reason'    => 'company_archived',
            ];
            $archDb->table('arc_employees')->insert($archRow);
        }

        return count($employees);
    }

    // ------------------------------------------------------------------
    //  Step 3a: Attendance
    // ------------------------------------------------------------------

    private function archiveAttendance($liveDb, $archDb, int $companyId): int
    {
        $rows = $liveDb->table('ci_timesheet')
            ->where('company_id', $companyId)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return 0;
        }

        foreach ($rows as $row) {
            $user = $liveDb->table('ci_erp_users')
                ->select('first_name, last_name')
                ->where('user_id', $row['employee_id'])
                ->get()
                ->getRowArray();

            $archRow = [
                'source_record_id'  => $row['time_attendance_id'],
                'source_company_id' => $companyId,
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

        return count($rows);
    }

    // ------------------------------------------------------------------
    //  Step 3b: Payroll
    // ------------------------------------------------------------------

    private function archivePayroll($liveDb, $archDb, int $companyId): int
    {
        $rows = $liveDb->table('ci_payslips')
            ->where('company_id', $companyId)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return 0;
        }

        foreach ($rows as $row) {
            $user = $liveDb->table('ci_erp_users')
                ->select('first_name, last_name')
                ->where('user_id', $row['staff_id'])
                ->get()
                ->getRowArray();

            $archRow = [
                'source_record_id'  => $row['payslip_id'],
                'source_company_id' => $companyId,
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

        return count($rows);
    }

    // ------------------------------------------------------------------
    //  Step 3c: Leave
    // ------------------------------------------------------------------

    private function archiveLeave($liveDb, $archDb, int $companyId): int
    {
        $rows = $liveDb->table('ci_leave_applications')
            ->where('company_id', $companyId)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return 0;
        }

        // Load leave types for this company
        $leaveTypes = $liveDb->table('ci_leave_types')
            ->where('company_id', $companyId)
            ->get()
            ->getResultArray();
        $leaveTypeMap = [];
        foreach ($leaveTypes as $lt) {
            $leaveTypeMap[$lt['leave_type_id']] = $lt['leave_type'] ?? $lt['leave_name'] ?? 'Unknown';
        }

        foreach ($rows as $row) {
            $user = $liveDb->table('ci_erp_users')
                ->select('first_name, last_name')
                ->where('user_id', $row['employee_id'])
                ->get()
                ->getRowArray();

            // Calculate days taken
            $fromDate = strtotime($row['from_date']);
            $toDate   = strtotime($row['to_date']);
            $daysTaken = $fromDate && $toDate ? max(1, (int) (($toDate - $fromDate) / 86400) + 1) : 1;
            if (! empty($row['is_half_day'])) {
                $daysTaken = max(1, $daysTaken); // keep at least 1
            }

            $statusMap = [0 => 'rejected', 1 => 'pending', 2 => 'approved'];

            $archRow = [
                'source_record_id'  => $row['leave_id'],
                'source_company_id' => $companyId,
                'employee_name'     => $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Unknown',
                'leave_type'        => $leaveTypeMap[$row['leave_type_id']] ?? 'Unknown',
                'start_date'        => $row['from_date'],
                'end_date'          => $row['to_date'],
                'days_taken'        => $daysTaken,
                'status'            => $statusMap[$row['status']] ?? 'pending',
            ];
            $archDb->table('arc_leaves')->insert($archRow);
        }

        $ids = array_column($rows, 'leave_id');
        $liveDb->table('ci_leave_applications')->whereIn('leave_id', $ids)->delete();

        return count($rows);
    }

    // ------------------------------------------------------------------
    //  Step 4: System logs (notifications, billing reminders, broadcasts)
    // ------------------------------------------------------------------

    private function archiveSystemLogs($liveDb, $archDb, int $companyId): int
    {
        $total = 0;

        // Notifications
        $notifications = $liveDb->table('ci_notifications')
            ->where('company_id', $companyId)
            ->get()
            ->getResultArray();

        foreach ($notifications as $row) {
            $archDb->table('arc_system_logs')->insert([
                'source_company_id' => $companyId,
                'log_type'          => 'notification',
                'description'       => $row['title'] . ': ' . ($row['body'] ?? ''),
                'metadata'          => json_encode([
                    'notification_id' => $row['notification_id'],
                    'user_id'         => $row['user_id'],
                    'link'            => $row['link'] ?? null,
                    'is_read'         => $row['is_read'] ?? 0,
                ]),
                'log_date'          => $row['created_at'],
            ]);
        }

        if (! empty($notifications)) {
            $ids = array_column($notifications, 'notification_id');
            $liveDb->table('ci_notifications')->whereIn('notification_id', $ids)->delete();
            $total += count($notifications);
        }

        // Billing reminder logs
        $billingLogs = $liveDb->table('ci_billing_reminders_log')
            ->where('company_id', $companyId)
            ->get()
            ->getResultArray();

        foreach ($billingLogs as $row) {
            $archDb->table('arc_system_logs')->insert([
                'source_company_id' => $companyId,
                'log_type'          => 'billing_reminder',
                'description'       => "Billing reminder day {$row['reminder_day']} via {$row['channel']}",
                'metadata'          => json_encode([
                    'log_id'       => $row['log_id'],
                    'reminder_day' => $row['reminder_day'],
                    'channel'      => $row['channel'],
                ]),
                'log_date'          => $row['sent_at'],
            ]);
        }

        if (! empty($billingLogs)) {
            $ids = array_column($billingLogs, 'log_id');
            $liveDb->table('ci_billing_reminders_log')->whereIn('log_id', $ids)->delete();
            $total += count($billingLogs);
        }

        // Broadcasts and their delivery logs
        $broadcasts = $liveDb->table('ci_broadcasts')
            ->where('company_id', $companyId)
            ->get()
            ->getResultArray();

        foreach ($broadcasts as $broadcast) {
            $archDb->table('arc_system_logs')->insert([
                'source_company_id' => $companyId,
                'log_type'          => 'broadcast',
                'description'       => $broadcast['subject'],
                'metadata'          => json_encode([
                    'broadcast_id'     => $broadcast['broadcast_id'],
                    'broadcast_type'   => $broadcast['broadcast_type'],
                    'audience_type'    => $broadcast['audience_type'],
                    'channels'         => $broadcast['channels'],
                    'status'           => $broadcast['status'],
                    'total_recipients' => $broadcast['total_recipients'],
                ]),
                'log_date'          => $broadcast['created_at'],
            ]);

            // Archive delivery logs
            $deliveryLogs = $liveDb->table('ci_broadcast_log')
                ->where('broadcast_id', $broadcast['broadcast_id'])
                ->get()
                ->getResultArray();

            if (! empty($deliveryLogs)) {
                $logIds = array_column($deliveryLogs, 'log_id');
                $liveDb->table('ci_broadcast_log')->whereIn('log_id', $logIds)->delete();
                $total += count($deliveryLogs);
            }
        }

        if (! empty($broadcasts)) {
            $broadcastIds = array_column($broadcasts, 'broadcast_id');
            $liveDb->table('ci_broadcasts')->whereIn('broadcast_id', $broadcastIds)->delete();
            $total += count($broadcasts);
        }

        return $total;
    }

    // ------------------------------------------------------------------
    //  Step 6: Queue vault bundle
    // ------------------------------------------------------------------

    private function queueVaultBundle(int $companyId): void
    {
        $queue = new Queue();

        if (! $queue->isConnected()) {
            CLI::write('  Warning: Cannot connect to Beanstalkd — vault bundle not queued', 'light_red');
            CLI::write('  Run manually: generate bundle for company #' . $companyId, 'light_red');
            return;
        }

        $queue->push('archive_vault', [
            'action'     => 'generate_bundle',
            'company_id' => $companyId,
        ]);
    }
}
