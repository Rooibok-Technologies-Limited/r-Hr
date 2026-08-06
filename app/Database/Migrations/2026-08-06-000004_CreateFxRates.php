<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cached foreign-exchange rates fetched from a trusted daily source
 * (exchangerate-api's free open.er-api.com endpoint, central-bank sourced) so
 * currency conversion uses auto-updated rates, never manually-entered ones.
 * One row per base currency holding the full quote map as JSON + a fetch time.
 */
class CreateFxRates extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'base_currency' => ['type' => 'VARCHAR', 'constraint' => 8],
            'rates_json'    => ['type' => 'TEXT'],
            'source'        => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => ''],
            'fetched_at'    => ['type' => 'TIMESTAMP', 'null' => false],
            'updated_at'    => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('base_currency', true);
        $this->forge->createTable('ci_fx_rates', true);
    }

    public function down()
    {
        $this->forge->dropTable('ci_fx_rates', true);
    }
}
