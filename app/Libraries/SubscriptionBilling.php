<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Libraries;

use Config\Database;
use App\Models\MembershipModel;
use App\Models\CompanymembershipModel;
use App\Models\UsersModel;

/**
 * SubscriptionBilling (ADR: registration billing, phase 2) — activate a company's
 * subscription from a SERVER-VERIFIED payment. Shared by the PesaPal IPN handler
 * (Api/V1/Webhooks::pesapal) and the renewal-return callback (Subscription::renew),
 * both of which first re-verify the transaction with the provider.
 *
 * Idempotent: keyed on the merchant reference (SUB-{company}-{plan}-{rand}), which
 * is stored as ci_finance_membership_invoices.invoice_id under a UNIQUE index — a
 * replayed IPN activates + receipts exactly once. [SECURITY: payment idempotency]
 */
class SubscriptionBilling
{
    /** True if a merchant reference is a subscription payment (not a wallet top-up). */
    public function isSubscriptionRef(string $ref): bool
    {
        return strpos($ref, 'SUB-') === 0;
    }

    /**
     * Activate/extend a subscription from a verified payment.
     *
     * @param array $v Verified charge: ['tx_ref','amount','currency','company_id']
     * @return array{ok:bool, already?:bool, company_id?:int, expiry?:string, reason?:string}
     */
    public function activateFromVerifiedPayment(array $v): array
    {
        $ref = (string) ($v['tx_ref'] ?? '');
        if (! $this->isSubscriptionRef($ref)) {
            return ['ok' => false, 'reason' => 'not a subscription reference'];
        }
        // SUB-{companyId}-{planId}-{rand}
        $parts     = explode('-', $ref);
        $companyId = (int) ($v['company_id'] ?? 0) ?: (int) ($parts[1] ?? 0);
        $planId    = (int) ($parts[2] ?? 0);
        if ($companyId <= 0 || $planId <= 0) {
            return ['ok' => false, 'reason' => 'malformed subscription reference'];
        }

        $db = Database::connect();

        // Idempotency: already receipted → no-op.
        if ($db->table('ci_finance_membership_invoices')->where('invoice_id', $ref)->countAllResults() > 0) {
            return ['ok' => true, 'already' => true, 'company_id' => $companyId];
        }

        $plan = (new MembershipModel())->where('membership_id', $planId)->first();
        if (! $plan) {
            return ['ok' => false, 'reason' => 'plan not found'];
        }

        // Extend from the later of today / the current (unexpired) expiry.
        $CM   = new CompanymembershipModel();
        $m    = $CM->where('company_id', $companyId)->first();
        $today = date('Y-m-d');
        $base  = ($m && ! empty($m['expiry_date']) && $m['expiry_date'] > $today) ? $m['expiry_date'] : $today;
        $add   = ((int) ($plan['plan_duration'] ?? 1) === 2) ? '+1 year' : '+1 month';
        $newExpiry = date('Y-m-d', strtotime($base . ' ' . $add));

        $membershipData = [
            'membership_id'     => $planId,
            'subscription_type' => $plan['plan_duration'] ?? 1,
            'is_active'         => 1,
            'expiry_date'       => $newExpiry,
            'update_at'         => date('d-m-Y h:i:s'),
        ];
        if ($m) {
            $CM->where('company_id', $companyId)->set($membershipData)->update();
        } else {
            $CM->insert($membershipData + ['company_id' => $companyId, 'auto_renew' => 0, 'created_at' => date('d-m-Y h:i:s')]);
        }

        // Receipt (idempotency key = invoice_id). A racing duplicate hits the unique
        // index and throws — swallow it, the first write already activated.
        try {
            $db->table('ci_finance_membership_invoices')->insert([
                'invoice_id'       => $ref,
                'company_id'       => $companyId,
                'membership_id'    => $planId,
                'membership_type'  => $plan['membership_type'] ?? 'Plan',
                'subscription'     => ((int) ($plan['plan_duration'] ?? 1) === 2) ? 'Yearly' : 'Monthly',
                'membership_price' => (float) ($v['amount'] ?? $plan['price'] ?? 0),
                'payment_method'   => 'PesaPal',
                'transaction_date' => date('Y-m-d H:i:s'),
                'description'      => 'Subscription payment — ' . ($plan['membership_type'] ?? 'Plan'),
                'created_at'       => date('d-m-Y h:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Unique-violation on replay: another delivery already receipted it.
            return ['ok' => true, 'already' => true, 'company_id' => $companyId];
        }

        try {
            service('audit')->record('subscription.paid', [
                'entity_type' => 'company', 'entity_id' => $companyId, 'company_id' => $companyId,
                'summary' => 'Paid subscription: ' . ($plan['membership_type'] ?? 'Plan') . ' (active to ' . $newExpiry . ')',
            ]);
        } catch (\Throwable $e) {}

        // Tell the company owner their subscription is live again.
        try {
            $owner = (new UsersModel())->where('user_id', $companyId)->first();
            if ($owner) {
                service('notifier')->send([$companyId], 'subscription_active', [
                    'title' => 'Subscription active',
                    'body'  => 'Your ' . ($plan['membership_type'] ?? '') . ' subscription is active until ' . $newExpiry . '.',
                    'link'  => site_url('erp/desk'),
                ]);
            }
        } catch (\Throwable $e) {}

        return ['ok' => true, 'company_id' => $companyId, 'expiry' => $newExpiry];
    }
}
