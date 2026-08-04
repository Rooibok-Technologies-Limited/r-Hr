<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Seed a default plan ladder (registration billing). Idempotent — a plan is only
 * inserted if no plan with that name exists, so super-admins can freely edit/delete
 * them afterwards via Membership (erp/membership-list) without this re-adding them.
 * plan_duration: 1 = monthly, 2 = yearly. total_employees: 0 = unlimited.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedMembershipPlans extends Migration
{
    public function up()
    {
        $plans = [
            ['membership_type' => 'Free Trial', 'price' => 0,   'plan_duration' => 1, 'total_employees' => 5,   'description' => 'Get started free — up to 5 employees.'],
            ['membership_type' => 'Starter',    'price' => 29,  'plan_duration' => 1, 'total_employees' => 15,  'description' => 'For small teams — up to 15 employees.'],
            ['membership_type' => 'Business',   'price' => 79,  'plan_duration' => 1, 'total_employees' => 50,  'description' => 'Growing companies — up to 50 employees.'],
            ['membership_type' => 'Enterprise', 'price' => 199, 'plan_duration' => 1, 'total_employees' => 0,   'description' => 'Unlimited employees + priority support.'],
        ];
        foreach ($plans as $p) {
            $exists = $this->db->table('ci_membership')->where('membership_type', $p['membership_type'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('ci_membership')->insert($p + ['created_at' => date('d-m-Y h:i:s')]);
            }
        }
    }

    public function down()
    {
        $this->db->table('ci_membership')
            ->whereIn('membership_type', ['Free Trial', 'Starter', 'Business', 'Enterprise'])
            ->delete();
    }
}
