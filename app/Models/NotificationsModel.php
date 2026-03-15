<?php
namespace App\Models;

use CodeIgniter\Model;

class NotificationsModel extends Model {

    protected $table = 'ci_notifications';
    protected $primaryKey = 'notification_id';
    protected $allowedFields = ['user_id', 'company_id', 'title', 'body', 'link', 'is_read', 'created_at'];
    protected $useTimestamps = false;
}
