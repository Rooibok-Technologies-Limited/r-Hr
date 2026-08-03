<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

/**
 * Migration: Flutterwave aggregator credentials + selection (ROADMAP F2, ADR-002).
 *
 * Flutterwave is the licensed aggregator holding the master float: collections
 * (company top-ups) and transfers (employee payouts) both run through it. Keys
 * are read via system_setting() (secret/webhook keys are in the helper's
 * $sensitive list = encrypted at rest). `disbursement_aggregator` selects the
 * pooled-aggregator payout path; absent/other = per-type direct drivers.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFlutterwaveSettings extends Migration
{
    private array $columns = [
        'flutterwave_active'         => ['type' => 'SMALLINT', 'constraint' => 1, 'null' => false, 'default' => 0],
        'flutterwave_environment'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => 'sandbox'],
        'flutterwave_base_url'       => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'flutterwave_public_key'     => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'flutterwave_secret_key'     => ['type' => 'TEXT', 'null' => true, 'default' => null], // encrypted
        'flutterwave_encryption_key' => ['type' => 'TEXT', 'null' => true, 'default' => null], // encrypted
        'flutterwave_webhook_secret' => ['type' => 'TEXT', 'null' => true, 'default' => null], // encrypted (verif-hash)
        // Which payout path the engine uses: 'flutterwave' = pooled aggregator for
        // ALL types; absent/'direct' = per-type MTN/Airtel drivers.
        'disbursement_aggregator'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null],
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
