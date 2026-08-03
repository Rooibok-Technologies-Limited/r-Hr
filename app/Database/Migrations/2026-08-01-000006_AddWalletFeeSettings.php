<?php
/**
 * Migration: per-payout fee knobs on ci_erp_settings (ROADMAP F2, ADR-002).
 *
 * WalletService::feeFor() reads these via system_setting(); absent = 0 (no fee).
 * `disbursement_fee_flat` is a flat charge per payout; `disbursement_fee_percent`
 * is a percentage of the amount. Together they are the money-movement revenue
 * line (aggregator cost + optional Rooibok margin).
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWalletFeeSettings extends Migration
{
    private array $columns = [
        'disbursement_fee_flat'    => ['type' => 'NUMERIC', 'constraint' => '14,2', 'null' => true, 'default' => null],
        'disbursement_fee_percent' => ['type' => 'NUMERIC', 'constraint' => '6,3', 'null' => true, 'default' => null],
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
