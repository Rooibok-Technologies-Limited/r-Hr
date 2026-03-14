<?php

namespace App\Libraries;

use App\Models\CompanymembershipModel;
use App\Models\MembershipModel;
use App\Models\NotificationModel;
use App\Models\UsersModel;

/**
 * Reusable subscription activation/extension service.
 *
 * Used by:
 *  - Webhooks controller (Stripe, MTN, Airtel callbacks)
 *  - Super Admin manual renewal
 *  - Any future payment gateway integration
 *
 * Logic:
 *  - If renewing early (before expiry): extend from current expiry date
 *  - If renewing after expiry: extend from today
 *  - Plan duration: 1 = 30 days, else 365 days
 *  - Set is_active = 1
 *  - Clear billing reminders log
 *  - Queue invoice generation
 *  - Queue confirmation email
 *  - Send in-app notification
 */
class SubscriptionService
{
    private CompanymembershipModel $companyMembershipModel;
    private MembershipModel $membershipModel;
    private $db;

    public function __construct()
    {
        $this->companyMembershipModel = new CompanymembershipModel();
        $this->membershipModel        = new MembershipModel();
        $this->db                     = \Config\Database::connect();
    }

    // ------------------------------------------------------------------
    //  Public API
    // ------------------------------------------------------------------

    /**
     * Activate or extend a company subscription after successful payment.
     *
     * @param int    $companyId    The company being activated.
     * @param int    $membershipId The membership plan purchased.
     * @param string $txRef        Transaction reference for auditing.
     *
     * @return string The new expiry date (Y-m-d).
     */
    public function activate(int $companyId, int $membershipId, string $txRef): string
    {
        $membership = $this->membershipModel->find($membershipId);
        $current    = $this->companyMembershipModel
                           ->where('company_id', $companyId)
                           ->first();

        // If renewing early (before expiry): extend from current expiry date.
        // If renewing after expiry: extend from today.
        $baseDate = date('Y-m-d');
        if ($current
            && $current['is_active'] == 1
            && ! empty($current['expiry_date'])
            && $current['expiry_date'] > $baseDate
        ) {
            $baseDate = $current['expiry_date'];
        }

        $daysToAdd = ($membership && $membership['plan_duration'] == 1) ? 30 : 365;
        $newExpiry = date('Y-m-d', strtotime($baseDate . " +{$daysToAdd} days"));

        // Update the company membership record.
        $this->companyMembershipModel
            ->where('company_id', $companyId)
            ->set([
                'membership_id' => $membershipId,
                'expiry_date'   => $newExpiry,
                'is_active'     => 1,
                'updated_at'    => date('Y-m-d H:i:s'),
            ])
            ->update();

        // Clear reminder log so the next billing cycle restarts cleanly.
        $this->db->table('ci_billing_reminders_log')
                 ->where('company_id', $companyId)
                 ->delete();

        // Queue invoice generation.
        $this->queueInvoiceGeneration($companyId, $membershipId, $txRef);

        // Queue confirmation email + in-app notification.
        $this->sendRenewalConfirmation($companyId, $newExpiry);

        log_message('info', "SubscriptionService: activated company {$companyId}, plan {$membershipId}, ref {$txRef}, new expiry {$newExpiry}");

        return $newExpiry;
    }

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /**
     * Queue an invoice generation job for the subscription payment.
     */
    private function queueInvoiceGeneration(int $companyId, int $membershipId, string $txRef): void
    {
        $queue = new Queue();

        if ($queue->isConnected()) {
            $queue->push('invoices', [
                'type'          => 'subscription_invoice',
                'company_id'    => $companyId,
                'membership_id' => $membershipId,
                'tx_ref'        => $txRef,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Fallback: just log it so it can be handled manually.
            log_message('info', "SubscriptionService: invoice generation queued (offline) for company {$companyId}, ref {$txRef}");
        }
    }

    /**
     * Send a renewal confirmation email (queued) and in-app notification.
     */
    private function sendRenewalConfirmation(int $companyId, string $newExpiry): void
    {
        $usersModel = new UsersModel();
        $adminUser  = $usersModel
            ->where('company_id', $companyId)
            ->where('user_type', 'company')
            ->first();

        if (! $adminUser) {
            log_message('warning', "SubscriptionService: no admin user found for company {$companyId}");
            return;
        }

        $userId = (int) $adminUser['user_id'];
        $name   = trim(($adminUser['first_name'] ?? '') . ' ' . ($adminUser['last_name'] ?? '')) ?: 'Valued Customer';

        // In-app notification
        $notificationModel = new NotificationModel();
        $notificationModel->notify(
            $userId,
            $companyId,
            'Subscription Renewed',
            "Your Rooibok HR subscription has been renewed. New expiry date: {$newExpiry}.",
            site_url('erp/subscription')
        );

        // Queue confirmation email
        $email = $adminUser['email'] ?? '';
        if (! empty($email)) {
            $queue = new Queue();
            if ($queue->isConnected()) {
                $queue->push('emails', [
                    'to'      => $email,
                    'subject' => 'Subscription Renewed - Rooibok HR',
                    'body'    => <<<EOT
Dear {$name},

Your Rooibok HR subscription has been successfully renewed.
Your new expiry date is: {$newExpiry}.

Thank you for your continued support.

Regards,
Rooibok HR Billing
EOT,
                ]);
            }
        }
    }
}
