<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

/**
 * Migration: single-use, expiring password-reset tokens on ci_erp_users.
 *
 * Replaces the old reset flow (which set a hardcoded constant password via a
 * replayable link) with a hashed, time-boxed, one-time token. [SECURITY]
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPasswordResetToken extends Migration
{
    private array $columns = [
        'reset_token_hash'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
        'reset_token_expires' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
    ];

    public function up()
    {
        $existing = $this->db->getFieldNames('ci_erp_users');
        $toAdd    = array_diff_key($this->columns, array_flip($existing));
        if ($toAdd !== []) {
            $this->forge->addColumn('ci_erp_users', $toAdd);
        }
    }

    public function down()
    {
        $existing = $this->db->getFieldNames('ci_erp_users');
        foreach (array_keys($this->columns) as $col) {
            if (in_array($col, $existing, true)) {
                $this->forge->dropColumn('ci_erp_users', $col);
            }
        }
    }
}
