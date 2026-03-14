<?php

namespace App\Models;

use CodeIgniter\Model;

class BroadcastLogModel extends Model
{
    protected $table      = 'ci_broadcast_log';
    protected $primaryKey = 'log_id';

    protected $allowedFields = [
        'log_id',
        'broadcast_id',
        'recipient_id',
        'recipient_type',
        'recipient_email',
        'recipient_phone',
        'personalised_subject',
        'personalised_body',
        'personalised_sms',
        'inapp_sent',
        'email_sent',
        'email_opened',
        'sms_sent',
        'sms_status',
        'error_message',
        'queued_at',
        'sent_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /**
     * Get all log entries for a broadcast.
     */
    public function getByBroadcast(int $broadcastId): array
    {
        return $this->where('broadcast_id', $broadcastId)
                    ->orderBy('log_id', 'ASC')
                    ->findAll();
    }

    /**
     * Count recipients by status for a broadcast.
     */
    public function getStats(int $broadcastId): array
    {
        $db = \Config\Database::connect();

        $total = $this->where('broadcast_id', $broadcastId)->countAllResults();

        $emailSent = $this->where('broadcast_id', $broadcastId)
                         ->where('email_sent', 1)
                         ->countAllResults();

        $smsSent = $this->where('broadcast_id', $broadcastId)
                       ->where('sms_sent', 1)
                       ->countAllResults();

        $inappSent = $this->where('broadcast_id', $broadcastId)
                         ->where('inapp_sent', 1)
                         ->countAllResults();

        $failed = $this->where('broadcast_id', $broadcastId)
                      ->where('error_message IS NOT NULL')
                      ->where('error_message !=', '')
                      ->countAllResults();

        return [
            'total'     => $total,
            'email_sent' => $emailSent,
            'sms_sent'   => $smsSent,
            'inapp_sent' => $inappSent,
            'failed'     => $failed,
        ];
    }

    /**
     * Get failed log entries for retry.
     */
    public function getFailed(int $broadcastId): array
    {
        return $this->where('broadcast_id', $broadcastId)
                    ->where('error_message IS NOT NULL')
                    ->where('error_message !=', '')
                    ->findAll();
    }
}
