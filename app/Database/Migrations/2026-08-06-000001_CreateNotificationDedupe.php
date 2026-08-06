<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Claims table backing notification idempotency (P8): one row per claimed
 * dedupe key. A unique insert is the atomic "first sender wins" guard for
 * both dispatch-level dedupe and at-most-once paid-SMS delivery.
 */
class CreateNotificationDedupe extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'dedupe_key' => ['type' => 'VARCHAR', 'constraint' => 64],
            'channel'    => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => ''],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => false],
        ]);
        $this->forge->addKey('dedupe_key', true);
        $this->forge->createTable('ci_notification_dedupe', true);

        // Cleanup scans prune by age.
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_notification_dedupe_created ON ci_notification_dedupe (created_at)');
    }

    public function down()
    {
        $this->forge->dropTable('ci_notification_dedupe', true);
    }
}
