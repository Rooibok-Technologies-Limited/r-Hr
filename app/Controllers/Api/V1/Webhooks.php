<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

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
        // Idempotency: Stripe retries the same event; extend + record once per invoice.
        if (! empty($invoice->id) && (new InvoicepaymentsModel())->where('invoice_id', $invoice->id)->first()) {
            log_message('info', 'Stripe webhook: duplicate payment for invoice ' . $invoice->id . ' — skipped');
            return;
        }
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
     * POST /api/v1/webhooks/mtn — MoMo disbursement callback (ROADMAP F2).
     *
     * MoMo posts the transaction with our externalId/referenceId and a status
     * (SUCCESSFUL|FAILED|PENDING). We apply the terminal outcome idempotently.
     */
    public function mtn()
    {
        return $this->handleDisbursementCallback('mtn', function (array $body) {
            $ref    = $body['externalId'] ?? $body['referenceId'] ?? ($body['financialTransactionId'] ?? '');
            $map    = ['SUCCESSFUL' => 'successful', 'FAILED' => 'failed', 'PENDING' => 'pending'];
            $status = $map[strtoupper((string) ($body['status'] ?? ''))] ?? 'unknown';
            return [$ref, $status, $body['financialTransactionId'] ?? null];
        });
    }

    /**
     * POST /api/v1/webhooks/airtel — Airtel Money disbursement callback.
     */
    public function airtel()
    {
        return $this->handleDisbursementCallback('airtel', function (array $body) {
            $tx     = $body['transaction'] ?? $body['data']['transaction'] ?? [];
            $ref    = $tx['id'] ?? ($body['reference'] ?? '');
            $code   = strtoupper((string) ($tx['status'] ?? ($tx['status_code'] ?? '')));
            $map    = ['TS' => 'successful', 'TF' => 'failed', 'TA' => 'pending', 'TIP' => 'pending'];
            return [$ref, $map[$code] ?? 'unknown', $tx['airtel_money_id'] ?? null];
        });
    }

    /**
     * POST /api/v1/webhooks/flutterwave — the aggregator's unified webhook
     * (ROADMAP F2, ADR-002). Handles two event families:
     *   - charge.completed   → a company top-up succeeded ⇒ credit its wallet.
     *   - transfer.completed → an employee payout reached a terminal state ⇒
     *                          settle/fail the disbursement line.
     *
     * Flutterwave authenticates by a static `verif-hash` header equal to our
     * configured secret. Charge amounts are NEVER trusted from the body — the
     * transaction is re-verified server-side before crediting. [SECURITY]
     */
    public function flutterwave()
    {
        helper('main');
        $raw  = $this->request->getBody() ?: '';
        $body = json_decode($raw, true) ?: [];

        $secret = system_setting('flutterwave_webhook_secret');
        if (! empty($secret)) {
            $sig = $this->request->getHeaderLine('verif-hash');
            if (! hash_equals((string) $secret, (string) $sig)) {
                log_message('warning', '[Webhook:flutterwave] verif-hash mismatch');
                return $this->response->setStatusCode(401)->setJSON(['error' => 'invalid signature']);
            }
        } elseif (ENVIRONMENT === 'production') {
            // Fail closed: never process money events on unauthenticated input in
            // production. Sandbox/dev may run without a secret for testing. [SECURITY: H4]
            log_message('error', '[Webhook:flutterwave] refused — no webhook secret configured in production');
            return $this->response->setStatusCode(400)->setJSON(['error' => 'webhook secret not configured']);
        }

        $event = (string) ($body['event'] ?? '');
        $data  = $body['data'] ?? [];

        if (strpos($event, 'charge') === 0) {
            $this->handleFlutterwaveCharge($data);
        } elseif (strpos($event, 'transfer') === 0) {
            $ref = (string) ($data['reference'] ?? '');
            if ($ref !== '') {
                // Do NOT trust the body's status for a money transition. Re-poll
                // the provider for the authoritative status. [SECURITY: H4]
                $status = $this->reverifyTransfer($ref);
                if (in_array($status, ['successful', 'failed'], true)) {
                    service('disbursementEngine')->handleCallback($ref, $status, (string) ($data['id'] ?? ''), $raw);
                }
            }
        } else {
            log_message('info', '[Webhook:flutterwave] unhandled event {e}', ['e' => $event]);
        }

        return $this->response->setStatusCode(200)->setJSON(['received' => true]);
    }

    /**
     * Re-poll the provider for a transfer's authoritative status, keyed on our
     * reference. Returns 'successful'|'failed'|'unknown'. Looks the line up to
     * pick the right rail/driver.
     */
    private function reverifyTransfer(string $reference): string
    {
        $line = $this->db->table('ci_disbursements')->select('type')->where('reference', $reference)->get()->getRowArray();
        if (! $line) {
            return 'unknown';
        }
        $res = service('disbursement')->for($line['type'])->status($reference);
        return $res['status'] ?? 'unknown';
    }

    /**
     * A top-up charge fired. Re-verify server-side (authoritative amount +
     * company), then credit the wallet idempotently on the transaction ref.
     */
    private function handleFlutterwaveCharge(array $data): void
    {
        if (strtolower((string) ($data['status'] ?? '')) !== 'successful') {
            return; // only successful charges credit
        }
        $txnId = $data['id'] ?? null;
        if (! $txnId) {
            return;
        }

        $v = service('flutterwaveCollections')->verify($txnId);
        if (! ($v['ok'] ?? false) || ($v['status'] ?? '') !== 'successful') {
            log_message('warning', '[Webhook:flutterwave] charge {id} failed re-verification', ['id' => $txnId]);
            return;
        }
        $this->creditVerifiedTopup($v, 'Flutterwave');
    }

    /**
     * POST/GET /api/v1/webhooks/pesapal — the PesaPal IPN (ROADMAP F2, ADR-002).
     *
     * PesaPal's IPN deliberately carries NO payment status; it delivers only
     * OrderTrackingId + OrderMerchantReference (via GET or POST). We fetch the
     * authoritative status server-side with GetTransactionStatus and credit the
     * wallet only on a verified COMPLETED charge — the amount/company come from
     * that server response, never the IPN body, so the notification is
     * unforgeable. Idempotent on merchant_reference. [SECURITY]
     */
    public function pesapal()
    {
        helper('main');
        $tracking    = (string) ($this->request->getVar('OrderTrackingId') ?? '');
        $merchantRef = (string) ($this->request->getVar('OrderMerchantReference') ?? '');
        $notifType   = $this->request->getVar('OrderNotificationType');

        if ($tracking !== '') {
            $v = service('pesapalCollections')->verify($tracking);
            if (($v['ok'] ?? false) && ($v['status'] ?? '') === 'successful') {
                $this->creditVerifiedTopup($v, 'Pesapal');
            } elseif (! ($v['ok'] ?? false)) {
                log_message('warning', '[Webhook:pesapal] verify failed for {t}: {r}', ['t' => $tracking, 'r' => $v['reason'] ?? '']);
            }
        } else {
            log_message('warning', '[Webhook:pesapal] IPN missing OrderTrackingId');
        }

        // PesaPal expects the IPN response to echo the delivered params as JSON.
        return $this->response->setStatusCode(200)->setJSON([
            'orderNotificationType' => $notifType,
            'orderTrackingId'       => $tracking,
            'orderMerchantReference'=> $merchantRef,
            'status'                => 200,
        ]);
    }

    /**
     * Credit a company wallet from an already server-verified top-up charge
     * (Flutterwave or PesaPal). Idempotent on the transaction ref: a replayed
     * webhook/IPN credits exactly once. [SECURITY]
     */
    private function creditVerifiedTopup(array $v, string $label): void
    {
        if (($v['company_id'] ?? 0) <= 0 || ($v['amount'] ?? 0) <= 0) {
            log_message('warning', '[Webhook:{l}] verified charge missing company/amount (ref {r})', ['l' => $label, 'r' => $v['tx_ref'] ?? '']);
            return;
        }
        service('wallet')->credit((int) $v['company_id'], (float) $v['amount'], 'topup', [
            'reference'   => $v['tx_ref'],   // idempotent: a replayed webhook credits once
            'description' => $label . ' top-up ' . $v['tx_ref'],
        ]);
    }

    /**
     * Shared disbursement-callback handler: store raw, (optionally) verify the
     * signature, look up by our reference, apply the terminal state once, and
     * always 200 for known/duplicate deliveries so the provider stops retrying.
     *
     * @param callable(array):array{0:string,1:string,2:?string} $extract
     */
    private function handleDisbursementCallback(string $source, callable $extract)
    {
        $raw  = $this->request->getBody() ?? '';
        $body = json_decode($raw, true) ?: [];

        // Signature verification (enforced only when a secret is configured, so
        // sandbox works without one; production MUST set it). [SECURITY]
        helper('main');
        $secret = system_setting($source === 'mtn' ? 'mtn_callback_secret' : 'airtel_callback_secret');
        if (! empty($secret)) {
            $sig      = $this->request->getHeaderLine('X-Callback-Signature') ?: $this->request->getHeaderLine('X-Signature');
            $expected = hash_hmac('sha256', $raw, $secret);
            if (! hash_equals($expected, (string) $sig)) {
                log_message('warning', '[Webhook:{s}] signature mismatch', ['s' => $source]);
                return $this->response->setStatusCode(401)->setJSON(['error' => 'invalid signature']);
            }
        } elseif (ENVIRONMENT === 'production') {
            // Fail closed in production: a callback settles/releases real money, so
            // never act on unauthenticated input. Sandbox may run without a secret. [SECURITY: H4]
            log_message('error', '[Webhook:{s}] refused — no callback secret configured in production', ['s' => $source]);
            return $this->response->setStatusCode(400)->setJSON(['error' => 'callback secret not configured']);
        }

        [$reference, $status, $providerTxnId] = $extract($body);
        if ($reference === '' || $status === 'unknown') {
            log_message('warning', '[Webhook:{s}] unparseable callback: {b}', ['s' => $source, 'b' => mb_substr($raw, 0, 500)]);
            return $this->response->setStatusCode(200)->setJSON(['received' => true]);
        }
        if ($status === 'pending') {
            return $this->response->setStatusCode(200)->setJSON(['received' => true]);
        }

        $result = service('disbursementEngine')->handleCallback($reference, $status, $providerTxnId, $raw);
        // Unknown reference → 200 + log (avoid provider retry storms).
        return $this->response->setStatusCode(200)->setJSON(['received' => true, 'applied' => $result['applied'] ?? false]);
    }

    /**
     * POST /api/v1/webhooks/zkteco
     *
     * ZKTeco / RFID biometric devices push attendance data via HTTP.
     * Supports both XML and JSON payload formats (firmware-dependent).
     */
    public function zkteco()
    {
        helper('main');
        // Device authentication: attendance rows are payroll-adjacent, so the
        // endpoint must not be open. Require a shared device secret when one is
        // configured; fail closed in production if it is missing. [SECURITY]
        $secret = system_setting('zkteco_device_secret');
        if (! empty($secret)) {
            $sig = $this->request->getHeaderLine('X-Device-Secret') ?: $this->request->getHeaderLine('X-Device-Token');
            if (! hash_equals((string) $secret, (string) $sig)) {
                log_message('warning', '[Webhook:zkteco] device secret mismatch');
                return $this->response->setStatusCode(401)->setJSON(['error' => 'invalid device secret']);
            }
        } elseif (ENVIRONMENT === 'production') {
            log_message('error', '[Webhook:zkteco] refused — no device secret configured in production');
            return $this->response->setStatusCode(400)->setJSON(['error' => 'device secret not configured']);
        }

        $contentType = $this->request->getHeaderLine('Content-Type');
        $body = $this->request->getBody();

        $records = [];

        if (strpos($contentType, 'xml') !== false || strpos($body, '<?xml') === 0) {
            // Parse XML format
            $xml = @simplexml_load_string($body);
            if ($xml === false) {
                log_message('error', 'ZKTeco webhook: failed to parse XML payload');
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid XML']);
            }
            foreach ($xml->Row as $row) {
                $records[] = [
                    'employee_id' => (string) $row->PIN,
                    'punch_time'  => (string) $row->DateTime,
                    'device_id'   => (string) ($row->DeviceID ?? 'unknown'),
                    'verify_type' => (string) ($row->VerifyType ?? 'fingerprint'),
                ];
            }
        } else {
            // Parse JSON format
            $data = json_decode($body, true);
            if (isset($data['records'])) {
                $records = $data['records'];
            } elseif (isset($data['PIN'])) {
                $records[] = $data;
            }
        }

        $processed = 0;

        foreach ($records as $record) {
            $employeeId = $record['employee_id'] ?? $record['PIN'] ?? null;
            $punchTime  = $record['punch_time'] ?? $record['DateTime'] ?? date('Y-m-d H:i:s');

            if (!$employeeId) {
                continue;
            }

            // Find employee by employee_id field or user_id
            $user = $this->db->table('ci_erp_users')
                ->where('employee_id', $employeeId)
                ->orWhere('user_id', $employeeId)
                ->get()->getRowArray();

            if (!$user) {
                log_message('warning', 'ZKTeco webhook: no user found for employee_id ' . $employeeId);
                continue;
            }

            // Determine if this is clock-in or clock-out
            $today = date('Y-m-d', strtotime($punchTime));
            $existing = $this->db->table('ci_timesheet')
                ->where('employee_id', $user['user_id'])
                ->where('attendance_date', $today)
                ->orderBy('time_attendance_id', 'DESC')
                ->get()->getRowArray();

            if (!$existing) {
                // Clock in
                $this->db->table('ci_timesheet')->insert([
                    'company_id'        => $user['company_id'],
                    'employee_id'       => $user['user_id'],
                    'attendance_date'   => $today,
                    'clock_in'          => $punchTime,
                    'clock_in_out'      => 0,
                    'total_work'        => '00:00',
                    'attendance_status' => 'Present',
                ]);
            } elseif (empty($existing['clock_out']) || $existing['clock_in_out'] == 0) {
                // Clock out
                $clockIn  = new \DateTime($existing['clock_in']);
                $clockOut = new \DateTime($punchTime);
                $diff     = $clockIn->diff($clockOut);
                $totalWork = $diff->format('%h:%I');

                $this->db->table('ci_timesheet')
                    ->where('time_attendance_id', $existing['time_attendance_id'])
                    ->update([
                        'clock_out'    => $punchTime,
                        'clock_in_out' => 1,
                        'total_work'   => $totalWork,
                    ]);
            }
            $processed++;
        }

        log_message('info', 'ZKTeco webhook: processed ' . $processed . ' of ' . count($records) . ' records');

        return $this->response->setStatusCode(200)->setJSON([
            'received'  => true,
            'processed' => $processed,
        ]);
    }
}
