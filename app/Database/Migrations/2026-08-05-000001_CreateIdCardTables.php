<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Staff ID Card system. Two tenant-scoped tables:
 *  - ci_employee_id_cards   : one issued card per employee (number, secure verify
 *                             token, issue/expiry, lifecycle status).
 *  - ci_id_card_settings    : per-company card configuration (template, branding
 *                             overrides, field toggles, ID-number format, validity,
 *                             terms). Geometry is fixed in the SVG template; only
 *                             colours/toggles are data — so a colour change never
 *                             alters the pattern.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIdCardTables extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('ci_employee_id_cards')) {
            $this->forge->addField([
                'card_id'          => ['type' => 'SERIAL'],
                'company_id'       => ['type' => 'INTEGER', 'null' => false, 'default' => 0],
                'user_id'          => ['type' => 'INTEGER', 'null' => false],
                'card_number'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'verify_token'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'active'],
                'issued_at'        => ['type' => 'TIMESTAMP', 'null' => true],
                'expiry_date'      => ['type' => 'DATE', 'null' => true],
                'revoked_at'       => ['type' => 'TIMESTAMP', 'null' => true],
                'revoked_by'       => ['type' => 'INTEGER', 'null' => true],
                'last_generated_at'=> ['type' => 'TIMESTAMP', 'null' => true],
                'created_at'       => ['type' => 'TIMESTAMP', 'null' => true],
                'updated_at'       => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey('card_id', true);
            $this->forge->addUniqueKey('verify_token');
            $this->forge->addUniqueKey(['company_id', 'user_id']);
            $this->forge->addKey('company_id');
            $this->forge->createTable('ci_employee_id_cards', true);
        }

        if (! $this->db->tableExists('ci_id_card_settings')) {
            $this->forge->addField([
                'setting_id'      => ['type' => 'SERIAL'],
                'company_id'      => ['type' => 'INTEGER', 'null' => false],
                'template'        => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false, 'default' => 'abstract_organic'],
                'show_logo'       => ['type' => 'SMALLINT', 'null' => false, 'default' => 1],
                'enable_qr'       => ['type' => 'SMALLINT', 'null' => false, 'default' => 1],
                // JSON map of optional field toggles (photo/name/position/staff_id/
                // join_date/expiry_date/date_of_birth/department/phone/blood_group).
                'fields'          => ['type' => 'TEXT', 'null' => true],
                'id_prefix'       => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => false, 'default' => 'RT'],
                'id_pattern'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false, 'default' => '{PREFIX}-{YEAR}-{SEQUENCE}'],
                'seq_length'      => ['type' => 'SMALLINT', 'null' => false, 'default' => 4],
                'validity_years'  => ['type' => 'SMALLINT', 'null' => false, 'default' => 2],
                'terms'           => ['type' => 'TEXT', 'null' => true],
                // Branding overrides — NULL means "use the Abstract Organic default".
                'color_primary'   => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true],
                'color_secondary' => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true],
                'color_accent'    => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true],
                'color_dark'      => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true],
                'color_light'     => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true],
                'color_bg'        => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true],
                'color_text'      => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true],
                'color_muted'     => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true],
                'created_at'      => ['type' => 'TIMESTAMP', 'null' => true],
                'updated_at'      => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey('setting_id', true);
            $this->forge->addUniqueKey('company_id');
            $this->forge->createTable('ci_id_card_settings', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('ci_employee_id_cards', true);
        $this->forge->dropTable('ci_id_card_settings', true);
    }
}
