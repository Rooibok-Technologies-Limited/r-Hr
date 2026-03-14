<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\BroadcastModel;
use App\Models\BroadcastLogModel;
use App\Models\UsersModel;
use App\Libraries\BroadcastPersonaliser;
use App\Libraries\Queue;

/**
 * Scheduled Spark command that picks up broadcasts with status='queued'
 * and scheduled_at <= NOW(), then queues per-recipient jobs to Beanstalkd.
 *
 * Usage:
 *   php spark broadcasts:dispatch
 *
 * Cron:
 *   * * * * * cd /var/www/html && php spark broadcasts:dispatch >> /var/log/broadcasts.log 2>&1
 */
class BroadcastDispatch extends BaseCommand
{
    protected $group       = 'Broadcasts';
    protected $name        = 'broadcasts:dispatch';
    protected $description = 'Dispatch queued broadcasts whose scheduled time has arrived';

    // ------------------------------------------------------------------

    public function run(array $params)
    {
        CLI::write('Broadcast dispatch started at ' . date('Y-m-d H:i:s'), 'green');

        $BroadcastModel    = new BroadcastModel();
        $BroadcastLogModel = new BroadcastLogModel();
        $UsersModel        = new UsersModel();
        $personaliser      = new BroadcastPersonaliser();
        $queue             = new Queue();

        if (! $queue->isConnected()) {
            CLI::error('Cannot connect to Beanstalkd. Check BEANSTALK env var.');
            return;
        }

        // Find broadcasts that are queued and scheduled_at <= NOW()
        $broadcasts = $BroadcastModel
            ->where('status', 'queued')
            ->where('scheduled_at <=', date('Y-m-d H:i:s'))
            ->findAll();

        if (empty($broadcasts)) {
            CLI::write('  No broadcasts ready for dispatch.', 'light_gray');
            CLI::write('Broadcast dispatch completed.', 'green');
            return;
        }

        $dispatched = 0;

        foreach ($broadcasts as $broadcast) {
            $broadcast = $BroadcastModel->decodeJson($broadcast);
            $broadcastId = $broadcast['broadcast_id'];
            $companyId   = (int) ($broadcast['company_id'] ?? 0);

            CLI::write("  Processing broadcast #{$broadcastId}: {$broadcast['subject']}", 'yellow');

            // Check if log entries already exist (jobs already queued by controller)
            $existingLogs = $BroadcastLogModel->where('broadcast_id', $broadcastId)->countAllResults();

            if ($existingLogs > 0) {
                // Jobs were already created by the controller's send() method.
                // Mark broadcast as 'sending' so it won't be picked up again.
                $BroadcastModel->update($broadcastId, ['status' => 'sending']);
                CLI::write("    Already has {$existingLogs} log entries — marked as sending.", 'light_gray');
                $dispatched++;
                continue;
            }

            // Get sender info
            $sender = $UsersModel->find($broadcast['created_by']);
            if (! $sender) {
                CLI::error("    Sender not found (ID: {$broadcast['created_by']})");
                $BroadcastModel->update($broadcastId, ['status' => 'failed']);
                continue;
            }

            // Build recipient list
            $recipients = $personaliser->buildRecipientList($broadcast, $companyId);

            if (empty($recipients)) {
                CLI::write("    No recipients found — marking as failed.", 'light_red');
                $BroadcastModel->update($broadcastId, ['status' => 'failed']);
                continue;
            }

            $channels = $broadcast['channels'];
            if (is_string($channels)) {
                $channels = json_decode($channels, true) ?: [];
            }

            $queued = 0;

            foreach ($recipients as $recipient) {
                $enriched = $personaliser->enrichRecipient($recipient);

                $pSubject = $personaliser->personalise($broadcast['subject'] ?? '', $enriched, $sender);
                $pBody    = $personaliser->personalise($broadcast['body_html'] ?? '', $enriched, $sender);
                $pSms     = $personaliser->personalise($broadcast['body_sms'] ?? '', $enriched, $sender);

                // Insert log entry
                $logData = [
                    'broadcast_id'         => $broadcastId,
                    'recipient_id'         => $recipient['user_id'],
                    'recipient_type'       => $recipient['user_type'] ?? 'staff',
                    'recipient_email'      => $recipient['email'] ?? '',
                    'recipient_phone'      => $recipient['contact_number'] ?? '',
                    'personalised_subject' => $pSubject,
                    'personalised_body'    => $pBody,
                    'personalised_sms'     => $pSms,
                    'inapp_sent'           => 0,
                    'email_sent'           => 0,
                    'sms_sent'             => 0,
                    'queued_at'            => date('Y-m-d H:i:s'),
                ];

                $BroadcastLogModel->insert($logData);
                $logId = $BroadcastLogModel->insertID();

                // Push to Beanstalkd
                $jobPayload = [
                    'log_id'       => $logId,
                    'broadcast_id' => (int) $broadcastId,
                    'recipient_id' => (int) $recipient['user_id'],
                    'channels'     => $channels,
                    'subject'      => $pSubject,
                    'body_html'    => $pBody,
                    'body_sms'     => $pSms,
                    'email'        => $recipient['email'] ?? '',
                    'phone'        => $recipient['contact_number'] ?? '',
                    'company_id'   => $companyId,
                ];

                $queue->push('broadcasts', $jobPayload);
                $queued++;
            }

            // Update broadcast
            $BroadcastModel->update($broadcastId, [
                'status'           => 'sending',
                'total_recipients' => $queued,
            ]);

            CLI::write("    Queued {$queued} recipient jobs.", 'green');
            $dispatched++;
        }

        CLI::write("Broadcast dispatch completed. {$dispatched} broadcast(s) processed.", 'green');
    }
}
