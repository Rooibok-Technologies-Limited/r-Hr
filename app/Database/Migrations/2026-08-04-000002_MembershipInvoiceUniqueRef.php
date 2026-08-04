<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Idempotency for subscription payments (ADR: registration billing, phase 2). The
 * PesaPal merchant reference (SUB-{company}-{plan}-{rand}) is stored as
 * ci_finance_membership_invoices.invoice_id; a UNIQUE index makes a replayed IPN /
 * double callback activate + receipt exactly once.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MembershipInvoiceUniqueRef extends Migration
{
    public function up()
    {
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS idx_membership_invoice_ref ON ci_finance_membership_invoices (invoice_id) WHERE invoice_id IS NOT NULL');
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS idx_membership_invoice_ref');
    }
}
