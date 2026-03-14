<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table      = 'ci_notifications';
    protected $primaryKey = 'notification_id';

    protected $allowedFields = [
        'notification_id',
        'user_id',
        'company_id',
        'title',
        'body',
        'link',
        'is_read',
        'created_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /**
     * Get unread notifications for a user, newest first.
     */
    public function getUnread(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Count unread notifications for a user.
     */
    public function countUnread(int $userId): int
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->countAllResults();
    }

    /**
     * Mark a notification as read.
     */
    public function markRead(int $notificationId): bool
    {
        return $this->update($notificationId, ['is_read' => 1]);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllRead(int $userId): bool
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->set(['is_read' => 1])
                    ->update();
    }

    /**
     * Create an in-app notification.
     */
    public function notify(int $userId, ?int $companyId, string $title, string $body = '', string $link = ''): bool
    {
        return (bool) $this->insert([
            'user_id'    => $userId,
            'company_id' => $companyId,
            'title'      => $title,
            'body'       => $body,
            'link'       => $link,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
