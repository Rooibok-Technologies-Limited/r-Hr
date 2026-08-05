<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Orientation support for the Staff ID Card system. Cards persist the orientation
 * they were issued with (so historical cards keep their layout when the company
 * default later changes). Settings gain a default orientation + a flag allowing
 * HR to pick orientation at generation time.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdCardOrientation extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('orientation', 'ci_employee_id_cards')) {
            $this->forge->addColumn('ci_employee_id_cards', [
                'orientation' => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => false, 'default' => 'portrait'],
            ]);
        }
        if (! $this->db->fieldExists('default_orientation', 'ci_id_card_settings')) {
            $this->forge->addColumn('ci_id_card_settings', [
                'default_orientation'     => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => false, 'default' => 'portrait'],
                'allow_orientation_choice'=> ['type' => 'SMALLINT', 'null' => false, 'default' => 1],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('orientation', 'ci_employee_id_cards')) {
            $this->forge->dropColumn('ci_employee_id_cards', 'orientation');
        }
        foreach (['default_orientation', 'allow_orientation_choice'] as $col) {
            if ($this->db->fieldExists($col, 'ci_id_card_settings')) {
                $this->forge->dropColumn('ci_id_card_settings', $col);
            }
        }
    }
}
