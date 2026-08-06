<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Display-only currency conversion: a tenant's monetary data is STORED in a
 * fixed base_currency (contractual — salaries never fluctuate), while
 * default_currency becomes the DISPLAY preference. When display != base, the UI
 * converts for viewing only via the trusted FX rates. Seed base_currency to the
 * tenant's current default_currency so nothing is re-interpreted (base == display
 * at first → zero conversion until a tenant chooses a different display currency).
 */
class AddBaseCurrency extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('base_currency', 'ci_erp_company_settings')) {
            $this->forge->addColumn('ci_erp_company_settings', [
                'base_currency' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true, 'default' => null],
            ]);
        }
        // Existing data is in each tenant's current operating currency → base = that.
        $this->db->query("UPDATE ci_erp_company_settings SET base_currency = default_currency WHERE base_currency IS NULL OR base_currency = ''");
    }

    public function down()
    {
        if ($this->db->fieldExists('base_currency', 'ci_erp_company_settings')) {
            $this->forge->dropColumn('ci_erp_company_settings', 'base_currency');
        }
    }
}
