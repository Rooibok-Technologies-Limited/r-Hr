<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

/**
 * FlutterwaveCollections — the company-funding (top-up) side of the aggregator
 * (ADR-002). A company funds its virtual wallet by paying Flutterwave via a
 * hosted checkout (card / mobile money / bank); the `charge.completed` webhook
 * then credits the wallet.
 *
 * initiate() creates the hosted payment and returns the redirect link; verify()
 * re-checks a transaction server-side (never trust webhook body amounts) before
 * the wallet is credited. Degrades safely (isConfigured() === false) with no
 * network calls when unconfigured.
 *
 * @see https://developer.flutterwave.com/reference/create-a-payment
 */
class FlutterwaveCollections
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        helper('main');
        $this->secretKey = system_setting('flutterwave_secret_key') ?: '';
        $this->baseUrl   = rtrim(system_setting('flutterwave_base_url') ?: 'https://api.flutterwave.com/v3', '/');
    }

    public function isConfigured(): bool
    {
        return $this->secretKey !== '';
    }

    /**
     * Create a hosted top-up payment for a company. `tx_ref` is our idempotency
     * key and carries company_id in meta so the webhook can map the credit back.
     *
     * @return array{ok:bool, link?:string, tx_ref?:string, reason?:string}
     */
    public function initiate(int $companyId, float $amount, array $opts = []): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'reason' => 'not configured'];
        }
        if ($amount <= 0) {
            return ['ok' => false, 'reason' => 'amount must be positive'];
        }

        $txRef = $opts['tx_ref'] ?? ('TOPUP-' . $companyId . '-' . bin2hex(random_bytes(6)));
        $body  = [
            'tx_ref'          => $txRef,
            'amount'          => $amount,
            'currency'        => $opts['currency'] ?? 'UGX',
            'redirect_url'    => $opts['redirect_url'] ?? site_url('erp/wallet'),
            'payment_options' => 'card,mobilemoneyuganda,banktransfer',
            'customer'        => [
                'email' => $opts['email'] ?? ('company' . $companyId . '@rooibok.local'),
                'name'  => $opts['name'] ?? ('Company ' . $companyId),
            ],
            'meta'            => ['company_id' => $companyId, 'purpose' => 'wallet_topup'],
            'customizations'  => ['title' => 'Wallet top-up', 'description' => 'Fund your payout wallet'],
        ];

        try {
            $res  = $this->client()->post('/payments', ['json' => $body, 'http_errors' => false]);
            $json = json_decode((string) $res->getBody(), true) ?: [];
            if (($json['status'] ?? '') === 'success' && ! empty($json['data']['link'])) {
                return ['ok' => true, 'link' => $json['data']['link'], 'tx_ref' => $txRef];
            }
            return ['ok' => false, 'reason' => $json['message'] ?? ('http ' . $res->getStatusCode())];
        } catch (\Throwable $e) {
            log_message('error', '[Flutterwave] initiate top-up failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'reason' => 'initiate error'];
        }
    }

    /**
     * Authoritative server-side verification of a completed charge. Use its
     * returned amount/currency/tx_ref/company_id — NEVER the webhook body's — to
     * decide what to credit. [SECURITY]
     *
     * @return array{ok:bool, status?:string, amount?:float, currency?:string, tx_ref?:string, company_id?:int, reason?:string}
     */
    public function verify($transactionId): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'reason' => 'not configured'];
        }
        try {
            $res  = $this->client()->get('/transactions/' . rawurlencode((string) $transactionId) . '/verify', ['http_errors' => false]);
            $json = json_decode((string) $res->getBody(), true) ?: [];
            $data = $json['data'] ?? [];
            if (($json['status'] ?? '') !== 'success' || ! $data) {
                return ['ok' => false, 'reason' => $json['message'] ?? 'verify failed'];
            }
            return [
                'ok'         => true,
                'status'     => strtolower((string) ($data['status'] ?? '')),
                'amount'     => (float) ($data['amount'] ?? 0),
                'currency'   => (string) ($data['currency'] ?? 'UGX'),
                'tx_ref'     => (string) ($data['tx_ref'] ?? ''),
                'company_id' => (int) ($data['meta']['company_id'] ?? ($data['meta']['companyId'] ?? 0)),
            ];
        } catch (\Throwable $e) {
            log_message('error', '[Flutterwave] verify failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'reason' => 'verify error'];
        }
    }

    private function client()
    {
        return \Config\Services::curlrequest([
            'baseURI' => $this->baseUrl,
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type'  => 'application/json',
            ],
        ]);
    }
}
