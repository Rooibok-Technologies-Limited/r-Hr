<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

/**
 * Migration: per-company branding for white-label SaaS.
 *
 * Each tenant can show their OWN logo/favicon in their workspace; only the
 * super-admin area shows the Rooibok HR platform brand. Stored on
 * ci_erp_company_settings (theme colours already live there). Uploaded files
 * go to public/uploads/logo/company/.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompanyBranding extends Migration
{
    private array $columns = [
        'company_logo'    => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'company_favicon' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
    ];

    public function up()
    {
        $existing = $this->db->getFieldNames('ci_erp_company_settings');
        $toAdd    = array_diff_key($this->columns, array_flip($existing));
        if ($toAdd !== []) {
            $this->forge->addColumn('ci_erp_company_settings', $toAdd);
        }
    }

    public function down()
    {
        $existing = $this->db->getFieldNames('ci_erp_company_settings');
        foreach (array_keys($this->columns) as $col) {
            if (in_array($col, $existing, true)) {
                $this->forge->dropColumn('ci_erp_company_settings', $col);
            }
        }
    }
}
