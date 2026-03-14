<?php

namespace App\Controllers\Api\V1;

use App\Models\CompanymembershipModel;
use App\Models\MembershipModel;

class Subscription extends ApiBaseController
{
    /**
     * GET /api/v1/subscription/status
     *
     * Returns the company's subscription status, expiry date, and plan name.
     */
    public function status()
    {
        $companyId = $this->jwtCompanyId();

        $companyMembershipModel = new CompanymembershipModel();
        $companyMembership = $companyMembershipModel
            ->where('company_id', $companyId)
            ->orderBy('company_membership_id', 'DESC')
            ->first();

        if (!$companyMembership) {
            return $this->jsonResponse([
                'company_id'  => $companyId,
                'status'      => 'no_subscription',
                'plan_name'   => null,
                'expiry_date' => null,
            ]);
        }

        // Get the membership/plan details
        $membershipModel = new MembershipModel();
        $membership = $membershipModel->find($companyMembership['membership_id']);

        $planName = $membership['membership_type'] ?? 'Unknown';

        // Calculate expiry based on plan duration and subscription creation
        $createdAt    = $companyMembership['created_at'] ?? null;
        $planDuration = $membership['plan_duration']     ?? 0; // assumed in days
        $expiryDate   = null;

        if ($createdAt && $planDuration > 0) {
            $expiryDate = date('Y-m-d', strtotime($createdAt . ' + ' . $planDuration . ' days'));
        }

        $isActive = true;
        if ($expiryDate && strtotime($expiryDate) < time()) {
            $isActive = false;
        }

        return $this->jsonResponse([
            'company_id'        => (int) $companyId,
            'status'            => $isActive ? 'active' : 'expired',
            'plan_name'         => $planName,
            'subscription_type' => $companyMembership['subscription_type'] ?? null,
            'total_employees'   => $membership['total_employees']          ?? null,
            'expiry_date'       => $expiryDate,
            'created_at'        => $createdAt,
        ]);
    }
}
