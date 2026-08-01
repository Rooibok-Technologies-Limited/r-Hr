<?php
/**
 * Migration: ci_audit_log — immutable, tamper-evident compliance trail.
 *
 * Append-only record of sensitive actions (payroll/disbursement, identity and
 * role changes, auth). Each row is hash-chained to the previous one
 * (row_hash = sha256(canonical(row) + prev_hash)), so any later edit or deletion
 * breaks the chain and is detectable. See ROADMAP F1.
 *
 * The application role should hold INSERT + SELECT only on this table — no
 * UPDATE/DELETE — enforced at the DB grant level in production.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'audit_id'      => ['type' => 'BIGSERIAL', 'auto_increment' => true],
            'actor_user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            // super_user | company | staff | api | system
            'actor_type'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'system'],
            'company_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
            // dotted slug, e.g. 'company.created', 'disbursement.approved', 'auth.login'
            'action'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'entity_type'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null],
            'entity_id'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null],
            'summary'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'before_json'   => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'after_json'    => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'ip'            => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true, 'default' => null],
            'user_agent'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'prev_hash'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            'row_hash'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'created_at'    => ['type' => 'TIMESTAMP', 'null' => false],
        ]);

        $this->forge->addPrimaryKey('audit_id');
        $this->forge->createTable('ci_audit_log', true);

        // Query paths: recent-per-company, by action, by entity, by actor.
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_audit_company_created ON ci_audit_log (company_id, created_at DESC)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_audit_action ON ci_audit_log (action)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_audit_entity ON ci_audit_log (entity_type, entity_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_audit_actor ON ci_audit_log (actor_user_id)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS idx_audit_company_created');
        $this->db->query('DROP INDEX IF EXISTS idx_audit_action');
        $this->db->query('DROP INDEX IF EXISTS idx_audit_entity');
        $this->db->query('DROP INDEX IF EXISTS idx_audit_actor');
        $this->forge->dropTable('ci_audit_log', true);
    }
}
