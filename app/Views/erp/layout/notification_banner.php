<?php
/**
 * Subscription expiry warning banner.
 *
 * Include this in the main layout. It checks the current company's
 * membership expiry date and displays a dismissible alert banner when
 * the subscription is within 7 days of expiring.
 *
 * Colour scheme:
 *   7-3 days  => amber/warning
 *   2-1 days  => red/danger
 *   0 or less => red/danger (expired)
 *
 * The banner includes a "Renew Now" button linking to the subscription page.
 * It is hidden automatically when expiry is more than 7 days away.
 */

use App\Models\CompanymembershipModel;

$session  = \Config\Services::session();
$username = $session->get('sup_username');

if (! empty($username['sup_user_id'])):
    $companyMembershipModel = new CompanymembershipModel();

    // Determine company_id: for company users it's their own user_id,
    // for staff it's the company_id field on their user record.
    $companyId = $user_info['company_id'] ?? $username['sup_user_id'] ?? null;

    if ($companyId):
        $membership = $companyMembershipModel
            ->where('company_id', $companyId)
            ->first();

        if ($membership && ! empty($membership['expiry_date'])):
            $today         = date('Y-m-d');
            $daysRemaining = (int) ((strtotime($membership['expiry_date']) - strtotime($today)) / 86400);

            if ($daysRemaining <= 7):
                // Determine banner style
                if ($daysRemaining <= 0) {
                    $alertClass = 'alert-danger';
                    $message    = 'Your Rooibok HR subscription has <strong>expired</strong>. Please renew immediately to restore full access.';
                } elseif ($daysRemaining <= 2) {
                    $alertClass = 'alert-danger';
                    $message    = "Your Rooibok HR subscription expires in <strong>{$daysRemaining} day" . ($daysRemaining > 1 ? 's' : '') . "</strong>. Renew now to avoid service interruption.";
                } else {
                    $alertClass = 'alert-warning';
                    $message    = "Your Rooibok HR subscription expires in <strong>{$daysRemaining} days</strong>. Renew to ensure uninterrupted access.";
                }
?>
<div class="alert <?= $alertClass; ?> alert-dismissible fade show m-b-15" role="alert" id="billing-banner">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <i class="feather icon-alert-triangle m-r-5"></i>
            <?= $message; ?>
        </div>
        <div class="ml-3">
            <a href="<?= site_url('erp/subscription'); ?>" class="btn btn-sm <?= $daysRemaining <= 2 ? 'btn-light' : 'btn-dark'; ?> rounded-pill">
                Renew Now
            </a>
            <button type="button" class="close ml-2" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
</div>
<?php
            endif; // daysRemaining <= 7
        endif; // membership exists
    endif; // companyId
endif; // session exists
?>
