<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\ReminderService;

/**
 * Daily cron job that checks subscription expiry dates and sends
 * reminder notifications at 7, 5, 3, 2, 1, and 0 days before expiry.
 *
 * On expiry day (0 days remaining) the company is deactivated.
 * At 1 day remaining, the show_modal flag is set to surface an urgent modal.
 *
 * Usage:
 *   php spark billing:check
 *
 * Cron (inside Docker or host):
 *   0 6 * * * cd /var/www/html && php spark billing:check >> /var/log/billing.log 2>&1
 */
class BillingCheck extends BaseCommand
{
    protected $group       = 'Billing';
    protected $name        = 'billing:check';
    protected $description = 'Check subscription expiry dates and send reminders';

    /** Days before expiry at which reminders are sent. */
    private array $reminderDays = [7, 5, 3, 2, 1, 0];

    // ------------------------------------------------------------------

    public function run(array $params)
    {
        CLI::write('Billing check started at ' . date('Y-m-d H:i:s'), 'green');

        $db              = \Config\Database::connect();
        $today           = date('Y-m-d');
        $reminderService = new ReminderService();

        // 1. Query all companies where is_active = 1
        $companies = $db->table('ci_company_membership')
            ->where('is_active', 1)
            ->get()
            ->getResultArray();

        if (empty($companies)) {
            CLI::write('  No active companies found.', 'light_gray');
            CLI::write('Billing check completed.', 'green');
            return;
        }

        $processed = 0;

        foreach ($companies as $company) {
            $expiryDate = $company['expiry_date'] ?? null;
            $companyId  = $company['company_id']  ?? null;

            if (! $expiryDate || ! $companyId) {
                continue;
            }

            // 2. Calculate days remaining: expiry_date - TODAY
            $daysRemaining = (int) ((strtotime($expiryDate) - strtotime($today)) / 86400);

            // > 7 days remaining: do nothing
            if ($daysRemaining > 7) {
                continue;
            }

            // Only act on specific reminder days
            if (! in_array($daysRemaining, $this->reminderDays, true)) {
                continue;
            }

            // 4. Check ci_billing_reminders_log before sending — never send same reminder twice
            $alreadySent = $db->table('ci_billing_reminders_log')
                ->where('company_id', $companyId)
                ->where('reminder_day', $daysRemaining)
                ->where('sent_at >=', $today)
                ->countAllResults();

            if ($alreadySent > 0) {
                CLI::write("  Company {$companyId}: day {$daysRemaining} reminder already sent today — skipping", 'light_gray');
                continue;
            }

            // 3. Handle each reminder threshold

            // --- 0 days: expired today — deactivate ---
            if ($daysRemaining === 0) {
                $db->table('ci_company_membership')
                    ->where('company_id', $companyId)
                    ->update(['is_active' => 0]);

                CLI::write("  Company {$companyId}: EXPIRED — deactivated", 'red');
            }

            // --- 1 day: set show_modal flag for urgent in-app modal ---
            if ($daysRemaining === 1) {
                $db->table('ci_company_membership')
                    ->where('company_id', $companyId)
                    ->update(['show_modal' => 1]);

                CLI::write("  Company {$companyId}: 1 day remaining — show_modal set", 'light_red');
            }

            // Send reminder through appropriate channels via ReminderService
            if ($daysRemaining > 0) {
                CLI::write("  Company {$companyId}: {$daysRemaining} day(s) remaining — sending reminder", 'yellow');
            }

            $reminderService->sendReminder($companyId, $daysRemaining, $company);

            // 5. Log every action
            $db->table('ci_billing_reminders_log')->insert([
                'company_id'   => $companyId,
                'reminder_day' => $daysRemaining,
                'channel'      => $reminderService->getChannelString($daysRemaining),
                'sent_at'      => date('Y-m-d H:i:s'),
            ]);

            $processed++;
        }

        CLI::write("Billing check completed. {$processed} reminder(s) processed.", 'green');
    }
}
