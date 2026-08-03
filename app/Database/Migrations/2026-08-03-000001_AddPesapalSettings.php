<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

/**
 * Migration: PesaPal collections credentials + selection (ROADMAP F2, ADR-002).
 *
 * PesaPal (API 3.0) is a collections-only aggregator: it powers company wallet
 * TOP-UPS via hosted checkout (card / MTN / Airtel), NOT payouts. It is the
 * alternative to Flutterwave for the funding side while payouts stay on the
 * direct MTN/Airtel rails. Consumer key/secret are read via system_setting()
 * (both are in the helper's $sensitive list = encrypted at rest).
 * `collections_provider` selects which funding gateway the wallet uses.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPesapalSettings extends Migration
{
    private array $columns = [
        'pesapal_active'          => ['type' => 'SMALLINT', 'constraint' => 1, 'null' => false, 'default' => 0],
        'pesapal_environment'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => 'sandbox'],
        'pesapal_base_url'        => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'pesapal_consumer_key'    => ['type' => 'TEXT', 'null' => true, 'default' => null], // encrypted
        'pesapal_consumer_secret' => ['type' => 'TEXT', 'null' => true, 'default' => null], // encrypted
        'pesapal_ipn_id'          => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
        // Which gateway funds company wallets: 'flutterwave' | 'pesapal'.
        // Absent = auto (whichever is configured, Flutterwave preferred).
        'collections_provider'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null],
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
