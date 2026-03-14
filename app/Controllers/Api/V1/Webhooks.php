<?php

namespace App\Controllers\Api\V1;

require_once APPPATH . 'ThirdParty/Stripe/init.php';

use App\Models\CompanymembershipModel;
use App\Models\MembershipModel;
use App\Models\InvoicepaymentsModel;
use App\Models\UsersModel;
use App\Libraries\SubscriptionService;

class Webhooks extends ApiBaseController
{
    protected CompanymembershipModel $CompanymembershipModel;
    protected MembershipModel $MembershipModel;
    protected $db;

    public function __construct()
    {
        $this->CompanymembershipModel = new CompanymembershipModel();
        $this->MembershipModel        = new MembershipModel();
        $this->db                     = \Config\Database::connect();
    }

    /**
     * POST /api/v1/webhooks/stripe
     *
     * No JWT authentication — uses Stripe-Signature header verification instead.
     */
    public function stripe()
    {
        $payload = file_get_contents('php://input');
        $sig     = $this->request->getHeaderLine('Stripe-Signature');
        $secret  = system_setting('stripe_webhook_secret');

        // Verify signature using Stripe SDK
        try {
            \Stripe\Stripe::setApiKey(system_setting('stripe_secret_key'));
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            log_message('error', 'Stripe webhook signature verification failed: ' . $e->getMessage());
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid signature']);
        } catch (\Exception $e) {
            log_message('error', 'Stripe webhook error: ' . $e->getMessage());
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid payload']);
        }

        switch ($event->type) {
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            default:
                log_message('info', 'Stripe webhook: unhandled event type ' . $event->type);
                break;
        }

        return $this->response->setStatusCode(200)->setJSON(['received' => true]);
    }

    /**
     * Handle invoice.payment_succeeded — extend subscription, generate invoice.
     */
    private function handlePaymentSucceeded(object $invoice): void
    {
        $stripeCustomerId = $invoice->customer ?? null;
        if (empty($stripeCustomerId)) {
            log_message('error', 'Stripe webhook: invoice.payment_succeeded missing customer ID');
            return;
        }

        // Find the company by stripe_customer_id
        $companyMembership = $this->CompanymembershipModel
            ->where('stripe_customer_id', $stripeCustomerId)
            ->first();

        if (!$companyMembership) {
            log_message('error', 'Stripe webhook: no company found for customer ' . $stripeCustomerId);
            return;
        }

        $companyId    = $companyMembership['company_id'];
        $membershipId = $companyMembership['membership_id'];
        $txRef        = $invoice->id ?? $invoice->payment_intent ?? 'stripe_' . time();

        $subscriptionService = new SubscriptionService();
        $subscriptionService->activate((int)$companyId, (int)$membershipId, $txRef);

        // Record payment in invoice history
        $membership = $this->MembershipModel->find($membershipId);
        if ($membership) {
            $InvoicepaymentsModel = new InvoicepaymentsModel();
            $InvoicepaymentsModel->insert([
                'invoice_id'       => $invoice->id ?? '',
                'company_id'       => $companyId,
                'membership_id'    => $membershipId,
                'subscription_id'  => $membership['subscription_id'] ?? '',
                'membership_type'  => $membership['membership_type'] ?? '',
                'subscription'     => $membership['plan_duration'] ?? '',
                'description'      => 'Stripe auto-renewal',
                'membership_price' => ($invoice->amount_paid ?? 0) / 100,
                'payment_method'   => 'Stripe',
                'invoice_month'    => date('Y-m'),
                'transaction_date' => date('Y-m-d H:i:s'),
                'created_at'       => date('Y-m-d H:i:s'),
                'receipt_url'      => $invoice->hosted_invoice_url ?? '',
                'source_info'      => 'auto_renew',
            ]);
        }

        log_message('info', 'Stripe webhook: subscription renewed for company ' . $companyId);
    }

    /**
     * Handle invoice.payment_failed — notify company admin, log failure.
     */
    private function handlePaymentFailed(object $invoice): void
    {
        $stripeCustomerId = $invoice->customer ?? null;
        if (empty($stripeCustomerId)) {
            log_message('error', 'Stripe webhook: invoice.payment_failed missing customer ID');
            return;
        }

        $companyMembership = $this->CompanymembershipModel
            ->where('stripe_customer_id', $stripeCustomerId)
            ->first();

        if (!$companyMembership) {
            log_message('error', 'Stripe webhook: no company found for customer ' . $stripeCustomerId);
            return;
        }

        $companyId = $companyMembership['company_id'];

        // Log the failure
        log_message('warning', 'Stripe payment failed for company ' . $companyId
            . ' | invoice: ' . ($invoice->id ?? 'unknown')
            . ' | attempt: ' . ($invoice->attempt_count ?? 'unknown'));

        // Notify the company admin via email
        $UsersModel = new UsersModel();
        $companyUser = $UsersModel->where('user_id', $companyId)
                                  ->where('user_type', 'company')
                                  ->first();

        if ($companyUser && !empty($companyUser['email'])) {
            $email = \Config\Services::email();
            $email->setTo($companyUser['email']);
            $email->setSubject('Payment Failed - Rooibok HR Subscription');
            $email->setMessage(
                'Your subscription payment could not be processed. '
                . 'Please update your payment method to avoid service interruption. '
                . 'Log in to your account and visit Subscription settings to update your card.'
            );
            $email->send(false); // false = don't throw on failure
        }
    }

    /**
     * Handle customer.subscription.deleted — disable auto-renew, switch to manual.
     */
    private function handleSubscriptionDeleted(object $subscription): void
    {
        $stripeCustomerId = $subscription->customer ?? null;
        if (empty($stripeCustomerId)) {
            log_message('error', 'Stripe webhook: subscription.deleted missing customer ID');
            return;
        }

        $companyMembership = $this->CompanymembershipModel
            ->where('stripe_customer_id', $stripeCustomerId)
            ->first();

        if (!$companyMembership) {
            log_message('error', 'Stripe webhook: no company found for customer ' . $stripeCustomerId);
            return;
        }

        $this->CompanymembershipModel
            ->where('company_id', $companyMembership['company_id'])
            ->set([
                'auto_renew'    => 0,
                'billing_mode'  => 'manual',
                'stripe_sub_id' => null,
                'updated_at'    => date('Y-m-d H:i:s'),
            ])
            ->update();

        log_message('info', 'Stripe webhook: subscription deleted for company ' . $companyMembership['company_id']);
    }

    /**
     * POST /api/v1/webhooks/mtn
     *
     * Placeholder for MTN Mobile Money callback — implemented in Phase 3.3.
     */
    public function mtn()
    {
        // Implemented in Phase 3.3
        return $this->response->setStatusCode(200)->setJSON(['received' => true]);
    }

    /**
     * POST /api/v1/webhooks/airtel
     *
     * Placeholder for Airtel Money callback — implemented in Phase 3.4.
     */
    public function airtel()
    {
        // Implemented in Phase 3.4
        return $this->response->setStatusCode(200)->setJSON(['received' => true]);
    }
}
