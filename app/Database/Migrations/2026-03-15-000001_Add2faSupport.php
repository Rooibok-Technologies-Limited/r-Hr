<?php
/**
 * Migration: Add Two-Factor Authentication support
 * Phase 2.1 - Adds totp_secret and totp_enabled columns to ci_erp_users,
 * and creates ci_totp_backup_codes table.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Add2faSupport extends Migration
{
    public function up()
    {
        // Add 2FA columns to ci_erp_users
        $this->forge->addColumn('ci_erp_users', [
            'totp_secret' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'is_active',
            ],
            'totp_enabled' => [
                'type'       => 'SMALLINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'after'      => 'totp_secret',
            ],
        ]);

        // Create backup codes table
        $this->forge->addField([
            'id' => [
                'type'           => 'SERIAL',
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'code_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'is_used' => [
                'type'       => 'SMALLINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
            'used_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->createTable('ci_totp_backup_codes', true);
    }

    public function down()
    {
        // Remove 2FA columns from users table
        $this->forge->dropColumn('ci_erp_users', ['totp_secret', 'totp_enabled']);

        // Drop backup codes table
        $this->forge->dropTable('ci_totp_backup_codes', true);
    }
}
