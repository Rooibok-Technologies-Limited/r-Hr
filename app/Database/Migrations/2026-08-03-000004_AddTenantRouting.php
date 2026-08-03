<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

/**
 * Migration: host-based multi-tenant routing (ADR-003, Phase 1).
 *
 * Each company (a ci_erp_users row, user_type=company) gets a unique `slug` used
 * as its subdomain ({slug}.HOST) and an optional verified `custom_domain`
 * (hr.acme.com). Non-breaking: columns are additive; the TenantResolver falls
 * back to today's behaviour when a host doesn't match. Backfills slugs for
 * existing companies from their name.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTenantRouting extends Migration
{
    private array $columns = [
        'company_slug'           => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'custom_domain'          => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null],
        'custom_domain_verified' => ['type' => 'SMALLINT', 'constraint' => 1, 'null' => false, 'default' => 0],
    ];

    public function up()
    {
        $existing = $this->db->getFieldNames('ci_erp_users');
        $toAdd    = array_diff_key($this->columns, array_flip($existing));
        if ($toAdd !== []) {
            $this->forge->addColumn('ci_erp_users', $toAdd);
        }

        // Backfill unique slugs for existing companies.
        $companies = $this->db->table('ci_erp_users')
            ->select('user_id, company_name')
            ->where('user_type', 'company')
            ->where('company_slug', null)
            ->get()->getResultArray();
        $used = [];
        foreach ($companies as $c) {
            $base = $this->slugify((string) ($c['company_name'] ?? '')) ?: ('company-' . $c['user_id']);
            $slug = $base;
            $n = 1;
            while (in_array($slug, $used, true)
                || $this->db->table('ci_erp_users')->where('company_slug', $slug)->countAllResults() > 0) {
                $slug = $base . '-' . $n;
                $n++;
            }
            $used[] = $slug;
            $this->db->table('ci_erp_users')->where('user_id', $c['user_id'])->update(['company_slug' => $slug]);
        }

        // Unique indexes (partial-friendly: multiple NULLs allowed in Postgres).
        try { $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS uidx_users_company_slug ON ci_erp_users (company_slug)'); } catch (\Throwable $e) {}
        try { $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS uidx_users_custom_domain ON ci_erp_users (custom_domain)'); } catch (\Throwable $e) {}
    }

    public function down()
    {
        try { $this->db->query('DROP INDEX IF EXISTS uidx_users_company_slug'); } catch (\Throwable $e) {}
        try { $this->db->query('DROP INDEX IF EXISTS uidx_users_custom_domain'); } catch (\Throwable $e) {}
        $existing = $this->db->getFieldNames('ci_erp_users');
        foreach (array_keys($this->columns) as $col) {
            if (in_array($col, $existing, true)) {
                $this->forge->dropColumn('ci_erp_users', $col);
            }
        }
    }

    private function slugify(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim((string) $s, '-');
    }
}
