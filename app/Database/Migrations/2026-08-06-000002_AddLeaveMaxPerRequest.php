<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-leave-type "maximum days per single application" cap, tenant-configurable.
 * NULL / 0 = no cap (only the annual quota applies). Lives on the shared
 * ci_erp_constants row for a leave_type (alongside field_one=days/year,
 * field_two=requires_approval).
 */
class AddLeaveMaxPerRequest extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('leave_max_per_request', 'ci_erp_constants')) {
            $this->forge->addColumn('ci_erp_constants', [
                'leave_max_per_request' => [
                    'type'    => 'INTEGER',
                    'null'    => true,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('leave_max_per_request', 'ci_erp_constants')) {
            $this->forge->dropColumn('ci_erp_constants', 'leave_max_per_request');
        }
    }
}
