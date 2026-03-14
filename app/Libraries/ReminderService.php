<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\UsersModel;

/**
 * Handles sending subscription reminder notifications through all channels:
 *   - In-app notification (always)
 *   - Email (queued via Beanstalkd)
 *   - SMS  (queued via Beanstalkd)
 *
 * Channel selection per days remaining:
 *   7 days  => email + SMS + in-app
 *   5 days  => SMS only + in-app
 *   3 days  => SMS only + in-app
 *   2 days  => email + SMS + in-app
 *   1 day   => email + SMS + in-app
 *   0 days  => email + SMS + in-app (expired notification)
 */
class ReminderService
{
    private NotificationModel $notificationModel;
    private UsersModel $usersModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        $this->usersModel        = new UsersModel();
    }

    // ------------------------------------------------------------------
    //  Public API
    // ------------------------------------------------------------------

    /**
     * Send a subscription reminder through the appropriate channels.
     *
     * @param int   $companyId     The company to notify.
     * @param int   $daysRemaining Days until subscription expiry (0 = expired today).
     * @param array $companyRow    Raw row from ci_company_membership (optional, avoids extra query).
     */
    public function sendReminder(int $companyId, int $daysRemaining, array $companyRow = []): void
    {
        // Find the company admin user
        $adminUser = $this->usersModel
            ->where('company_id', $companyId)
            ->where('user_type', 'company')
            ->first();

        if (! $adminUser) {
            log_message('warning', "ReminderService: no admin user found for company {$companyId}");
            return;
        }

        $userId = (int) $adminUser['user_id'];

        // Determine channels based on daysRemaining
        $channels = $this->getChannels($daysRemaining);

        // 1. In-app notification (always)
        $this->sendInAppNotification($userId, $companyId, $daysRemaining);

        // 2. Queue email if applicable
        if (in_array('email', $channels, true)) {
            $email = $adminUser['email'] ?? '';
            if (! empty($email)) {
                $this->queueEmail($email, $companyId, $daysRemaining, $adminUser);
            }
        }

        // 3. Queue SMS if applicable
        if (in_array('sms', $channels, true)) {
            $phone = $companyRow['billing_phone']
                ?? $companyRow['phone']
                ?? $adminUser['contact_number']
                ?? '';
            if (! empty($phone)) {
                $this->queueSms($phone, $companyId, $daysRemaining);
            }
        }
    }

    /**
     * Return the list of channels to use for a given daysRemaining value.
     *
     * @return string[] e.g. ['email', 'sms', 'in_app']
     */
    public function getChannels(int $daysRemaining): array
    {
        // In-app is always included (handled outside this method).
        switch ($daysRemaining) {
            case 7:
                return ['email', 'sms'];
            case 5:
            case 3:
                return ['sms'];
            case 2:
                return ['email', 'sms'];
            case 1:
                return ['email', 'sms'];
            case 0:
                return ['email', 'sms'];
            default:
                return [];
        }
    }

    /**
     * Return a human-readable channel string for logging.
     */
    public function getChannelString(int $daysRemaining): string
    {
        $channels   = $this->getChannels($daysRemaining);
        $channels[] = 'in_app'; // always present
        return implode(',', $channels);
    }

    // ------------------------------------------------------------------
    //  In-App
    // ------------------------------------------------------------------

    private function sendInAppNotification(int $userId, int $companyId, int $daysRemaining): void
    {
        $title = $this->getNotificationTitle($daysRemaining);
        $body  = $this->getNotificationBody($daysRemaining);

        $this->notificationModel->notify(
            $userId,
            $companyId,
            $title,
            $body,
            site_url('erp/subscription')
        );
    }

    private function getNotificationTitle(int $days): string
    {
        if ($days === 0) {
            return 'Subscription Expired';
        }
        if ($days === 1) {
            return 'Final Notice: Subscription Expires Today';
        }
        return "Subscription expires in {$days} days";
    }

    private function getNotificationBody(int $days): string
    {
        if ($days === 0) {
            return 'Your Rooibok HR subscription has expired. Please renew to restore access.';
        }
        if ($days === 1) {
            return 'Your subscription expires TODAY. Renew now to avoid service interruption.';
        }
        return "Your Rooibok HR subscription will expire in {$days} days. Renew now to avoid interruption.";
    }

    // ------------------------------------------------------------------
    //  Email (queued)
    // ------------------------------------------------------------------

    private function queueEmail(string $toEmail, int $companyId, int $daysRemaining, array $adminUser): void
    {
        $queue = new Queue();

        if (! $queue->isConnected()) {
            log_message('error', "ReminderService: cannot queue email — Beanstalkd unavailable (company {$companyId})");
            return;
        }

        $name = trim(($adminUser['first_name'] ?? '') . ' ' . ($adminUser['last_name'] ?? '')) ?: 'Valued Customer';

        $queue->push('emails', [
            'to'      => $toEmail,
            'subject' => $this->getEmailSubject($daysRemaining),
            'body'    => $this->getEmailBody($daysRemaining, $name),
        ]);
    }

    /**
     * Return the email subject line for a given daysRemaining value.
     */
    private function getEmailSubject(int $days): string
    {
        switch ($days) {
            case 7:
                return 'Your Rooibok HR subscription expires in 7 days';
            case 5:
                return 'Reminder: Rooibok HR subscription expires in 5 days';
            case 3:
                return 'Action needed: subscription expires in 3 days';
            case 2:
                return 'Urgent: your Rooibok HR subscription expires in 2 days';
            case 1:
                return 'Final notice: subscription expires TODAY';
            case 0:
                return 'Your Rooibok HR subscription has expired';
            default:
                return "Rooibok HR subscription reminder ({$days} days)";
        }
    }

    private function getEmailBody(int $daysRemaining, string $name): string
    {
        if ($daysRemaining === 0) {
            return <<<EOT
Dear {$name},

Your Rooibok HR subscription expired today. Your account has been
deactivated. To restore access please renew your subscription at
your earliest convenience.

Renew here: https://rooibok.co.ug/erp/subscription

If you have already made a payment, please disregard this message —
your account will be reactivated once the payment is confirmed.

Regards,
Rooibok HR Billing
EOT;
        }

        $urgency = $daysRemaining <= 2 ? 'URGENT: ' : '';

        return <<<EOT
Dear {$name},

{$urgency}This is a reminder that your Rooibok HR subscription will
expire in {$daysRemaining} day(s). Please renew before your expiry
date to ensure uninterrupted access.

Renew here: https://rooibok.co.ug/erp/subscription

Regards,
Rooibok HR Billing
EOT;
    }

    // ------------------------------------------------------------------
    //  SMS (queued)
    // ------------------------------------------------------------------

    private function queueSms(string $phone, int $companyId, int $daysRemaining): void
    {
        $queue = new Queue();

        if (! $queue->isConnected()) {
            log_message('error', "ReminderService: cannot queue SMS — Beanstalkd unavailable (company {$companyId})");
            return;
        }

        $queue->push('broadcasts', [
            'type'       => 'sms',
            'phone'      => $phone,
            'message'    => $this->getSmsBody($daysRemaining),
            'company_id' => $companyId,
        ]);
    }

    /**
     * Return SMS body text (under 160 characters).
     */
    private function getSmsBody(int $days): string
    {
        if ($days === 0) {
            return 'Rooibok HR: Your subscription has expired. Renew at rooibok.co.ug/erp to restore access.';
        }
        if ($days === 1) {
            return 'Rooibok HR: Your subscription expires TODAY. Renew at rooibok.co.ug/erp to avoid interruption.';
        }
        return "Rooibok HR: Your subscription expires in {$days} days. Renew at rooibok.co.ug/erp to avoid interruption.";
    }
}
