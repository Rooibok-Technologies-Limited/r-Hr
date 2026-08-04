<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Feature-gating by plan tier (registration billing). Adds ci_membership.features
 * — a JSON array of granted feature keys (see plan_gateable_features()). NULL =
 * unlimited (all features), so any plan left unset stays fully-featured. Seeds the
 * default ladder; super-admins edit these per plan via Membership CRUD.
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PlanFeatures extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('features', 'ci_membership')) {
            $this->forge->addColumn('ci_membership', [
                'features' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            ]);
        }

        // Default ladder (keys must match plan_gateable_features()). NULL = all.
        $ladder = [
            'Free Trial' => [],
            'Starter'    => ['recruitment', 'performance'],
            'Pro Plan'   => ['payroll', 'recruitment', 'performance', 'training'],
            'Business'   => ['payroll', 'recruitment', 'performance', 'training', 'projects', 'inventory'],
            'Enterprise' => null, // all features
        ];
        foreach ($ladder as $name => $feats) {
            $this->db->table('ci_membership')->where('membership_type', $name)
                ->update(['features' => $feats === null ? null : json_encode($feats)]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('features', 'ci_membership')) {
            $this->forge->dropColumn('ci_membership', 'features');
        }
    }
}
