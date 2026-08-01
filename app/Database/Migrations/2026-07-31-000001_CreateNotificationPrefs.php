<?php
/**
 * Migration: Notification preferences + notification query index
 *
 * - Creates ci_user_notification_prefs: per-user, per-event channel opt-in
 *   (in-app / email / SMS). A row with event '*' is the user's global default;
 *   a row with a specific event slug overrides it. Absence of any row falls
 *   back to hard defaults in the Notifier (in-app + email on, SMS off).
 * - Adds a composite index matching the unread-notifications query
 *   (WHERE user_id = ? AND is_read = ? ORDER BY created_at DESC).
 *
 * ci_notifications itself already exists (docker/postgres/init.sql); it is not
 * (re)created here. This migration is additive and reversible.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationPrefs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'pref_id' => [
                'type'           => 'SERIAL',
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            // '*' = global default for the user; otherwise a specific event slug.
            'event' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
                'default'    => '*',
            ],
            'inapp' => [
                'type'       => 'SMALLINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
            ],
            'email' => [
                'type'       => 'SMALLINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
            ],
            'sms' => [
                'type'       => 'SMALLINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addPrimaryKey('pref_id');
        $this->forge->addUniqueKey(['user_id', 'event']);
        $this->forge->createTable('ci_user_notification_prefs', true);

        // Composite index for the ordered unread lookup. Raw SQL so we can
        // express DESC ordering on created_at, which Forge keys cannot. Guarded
        // with IF NOT EXISTS so it is safe on databases where it already exists.
        $this->db->query(
            'CREATE INDEX IF NOT EXISTS idx_notifications_user_read_created '
            . 'ON ci_notifications (user_id, is_read, created_at DESC)'
        );
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS idx_notifications_user_read_created');
        $this->forge->dropTable('ci_user_notification_prefs', true);
    }
}
