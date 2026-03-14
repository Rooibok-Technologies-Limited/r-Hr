<?php

namespace App\Models;

use CodeIgniter\Model;

class BroadcastModel extends Model
{
    protected $table      = 'ci_broadcasts';
    protected $primaryKey = 'broadcast_id';

    protected $allowedFields = [
        'broadcast_id',
        'company_id',
        'created_by',
        'broadcast_type',
        'subject',
        'body_html',
        'body_sms',
        'audience_type',
        'audience_ids',
        'channels',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'created_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Cast JSONB columns to/from arrays automatically.
     */
    protected $casts = [];

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /**
     * Decode JSONB fields that PostgreSQL returns as strings.
     */
    public function decodeJson(array $row): array
    {
        if (isset($row['audience_ids']) && is_string($row['audience_ids'])) {
            $row['audience_ids'] = json_decode($row['audience_ids'], true) ?: [];
        }
        if (isset($row['channels']) && is_string($row['channels'])) {
            $row['channels'] = json_decode($row['channels'], true) ?: [];
        }
        return $row;
    }

    /**
     * Get broadcasts for a company, newest first.
     */
    public function getByCompany(int $companyId, int $limit = 50): array
    {
        return $this->where('company_id', $companyId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get all broadcasts (super admin), newest first.
     */
    public function getAll(int $limit = 50): array
    {
        return $this->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get broadcasts that are queued and ready to send.
     */
    public function getReadyToDispatch(): array
    {
        return $this->where('status', 'queued')
                    ->where('scheduled_at <=', date('Y-m-d H:i:s'))
                    ->findAll();
    }
}
