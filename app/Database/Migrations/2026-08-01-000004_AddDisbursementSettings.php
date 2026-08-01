<?php
/**
 * Migration: disbursement config columns on ci_erp_settings (ROADMAP F2 phase 3).
 *
 * The MoMo/Airtel credential columns (mtn_api_user/mtn_api_key/mtn_subscription_key,
 * airtel_client_id/airtel_client_secret, …) already exist. This adds the missing
 * knobs the drivers and engine read via system_setting(): base URLs, callback
 * secrets, and the safety caps. All nullable — absent = default/unlimited.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDisbursementSettings extends Migration
{
    private array $columns = [
        'mtn_base_url'             => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'mtn_callback_secret'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'airtel_base_url'          => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'airtel_country'           => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'default' => null],
        'airtel_currency'          => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'default' => null],
        'airtel_pin'               => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'airtel_callback_secret'   => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'disbursement_max_per_txn' => ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => true, 'default' => null],
        'disbursement_max_per_run' => ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => true, 'default' => null],
        'disbursement_max_per_day' => ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => true, 'default' => null],
    ];

    public function up()
    {
        $existing = $this->db->getFieldNames('ci_erp_settings');
        $toAdd    = array_diff_key($this->columns, array_flip($existing));
        if ($toAdd !== []) {
            $this->forge->addColumn('ci_erp_settings', $toAdd);
        }
    }

    public function down()
    {
        $existing = $this->db->getFieldNames('ci_erp_settings');
        foreach (array_keys($this->columns) as $col) {
            if (in_array($col, $existing, true)) {
                $this->forge->dropColumn('ci_erp_settings', $col);
            }
        }
    }
}
