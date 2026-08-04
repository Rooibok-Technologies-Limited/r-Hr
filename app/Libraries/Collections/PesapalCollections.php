<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Collections;

/**
 * PesaPal (API 3.0) collections driver — the company wallet-funding side of
 * ADR-002. PesaPal is a collections-only aggregator (it receives money via
 * hosted checkout: card, MTN, Airtel); it does NOT do arbitrary payouts, so it
 * powers top-ups only — employee payouts stay on the direct MTN/Airtel rails.
 *
 * Flow:
 *   1. token()      — OAuth-style Bearer token (POST /Auth/RequestToken), 5-min
 *                     lived, cached ~4 min.
 *   2. registerIpn()— one-time IPN registration → ipn_id (run via `spark
 *                     pesapal:setup`); stored in settings.
 *   3. initiate()   — SubmitOrderRequest → hosted redirect_url. `id`
 *                     (merchant_reference) = TOPUP-{companyId}-{hex} is our
 *                     idempotency key AND carries the company mapping.
 *   4. verify()     — GetTransactionStatus by order_tracking_id. PesaPal's IPN
 *                     deliberately carries NO status, so the wallet is credited
 *                     only after this authoritative server-side check. [SECURITY]
 *
 * @see https://developer.pesapal.com/how-to-integrate/e-commerce/api-30-json/authentication
 */
class PesapalCollections implements CollectionsProviderInterface
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $baseUrl;
    private string $ipnId;

    /** GetTransactionStatus status_code → our state machine. */
    private const STATUS = [
        1 => 'successful', // COMPLETED
        2 => 'failed',     // FAILED
        3 => 'failed',     // REVERSED
        0 => 'pending',    // INVALID (not found / not yet paid) — never credits
    ];

    public function __construct()
    {
        helper('main');
        $this->consumerKey    = system_setting('pesapal_consumer_key') ?: '';
        $this->consumerSecret = system_setting('pesapal_consumer_secret') ?: '';
        $env                  = system_setting('pesapal_environment') ?: 'sandbox';
        $default              = $env === 'production'
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';
        $this->baseUrl        = rtrim(system_setting('pesapal_base_url') ?: $default, '/');
        $this->ipnId          = system_setting('pesapal_ipn_id') ?: '';
    }

    public function name(): string
    {
        return 'pesapal';
    }

    public function isConfigured(): bool
    {
        return $this->consumerKey !== '' && $this->consumerSecret !== '';
    }

    /**
     * Start a hosted top-up. `id` (merchant_reference) is our idempotency key and
     * encodes the company so verify() can map the credit back.
     *
     * @return array{ok:bool, link?:string, tx_ref?:string, order_tracking_id?:?string, reason?:string}
     */
    public function initiate(int $companyId, float $amount, array $opts = []): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'reason' => 'not configured'];
        }
        if ($amount <= 0) {
            return ['ok' => false, 'reason' => 'amount must be positive'];
        }
        if ($this->ipnId === '') {
            return ['ok' => false, 'reason' => 'IPN not registered — run: php spark pesapal:setup'];
        }
        $token = $this->token();
        if ($token === null) {
            return ['ok' => false, 'reason' => 'authentication failed'];
        }

        $merchantRef = $opts['tx_ref'] ?? ('TOPUP-' . $companyId . '-' . bin2hex(random_bytes(6)));
        $body = [
            'id'              => $merchantRef,
            'currency'        => $opts['currency'] ?? 'UGX',
            'amount'          => round($amount, 2),
            'description'     => mb_substr((string) ($opts['description'] ?? ('Wallet top-up company ' . $companyId)), 0, 100),
            'callback_url'    => $opts['redirect_url'] ?? site_url('erp/wallet'),
            'notification_id' => $this->ipnId,
            'billing_address' => [
                'email_address' => $opts['email'] ?? ('company' . $companyId . '@rooibok.local'),
                'phone_number'  => $opts['phone'] ?? '',
                'first_name'    => $opts['name'] ?? ('Company ' . $companyId),
            ],
        ];

        try {
            $res  = $this->client()->post($this->baseUrl . '/api/Transactions/SubmitOrderRequest', ['headers' => $this->authHeaders($token), 'json' => $body, 'http_errors' => false]);
            $json = json_decode((string) $res->getBody(), true) ?: [];
            if ((string) ($json['status'] ?? '') === '200' && ! empty($json['redirect_url'])) {
                return [
                    'ok'                => true,
                    'link'              => $json['redirect_url'],
                    'tx_ref'            => $merchantRef,
                    'order_tracking_id' => $json['order_tracking_id'] ?? null,
                ];
            }
            return ['ok' => false, 'reason' => $this->errText($json) ?: ('http ' . $res->getStatusCode())];
        } catch (\Throwable $e) {
            log_message('error', '[Pesapal] initiate top-up failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'reason' => 'initiate error'];
        }
    }

    /**
     * Authoritative status of a transaction, keyed on PesaPal's order_tracking_id.
     * Returns our-state + the amount/currency/company parsed back from the
     * merchant_reference so the caller can credit the right wallet. [SECURITY]
     *
     * @param string $transactionId PesaPal order_tracking_id
     * @return array{ok:bool, status?:string, amount?:float, currency?:string, tx_ref?:string, company_id?:int, reason?:string}
     */
    public function verify($transactionId): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'reason' => 'not configured'];
        }
        $token = $this->token();
        if ($token === null) {
            return ['ok' => false, 'reason' => 'authentication failed'];
        }
        try {
            $res  = $this->client()->get(
                $this->baseUrl . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . rawurlencode((string) $transactionId),
                ['headers' => $this->authHeaders($token), 'http_errors' => false]
            );
            $json = json_decode((string) $res->getBody(), true) ?: [];
            // A real transaction always echoes a status_code / description; its
            // absence means the tracking id was rejected.
            if (! isset($json['status_code']) && ! isset($json['payment_status_description'])) {
                return ['ok' => false, 'reason' => $this->errText($json) ?: 'verify failed'];
            }
            $code        = (int) ($json['status_code'] ?? 0);
            $merchantRef = (string) ($json['merchant_reference'] ?? '');
            return [
                'ok'         => true,
                'status'     => self::STATUS[$code] ?? 'pending',
                'amount'     => (float) ($json['amount'] ?? 0),
                'currency'   => (string) ($json['currency'] ?? 'UGX'),
                'tx_ref'     => $merchantRef,
                'company_id' => $this->companyFromRef($merchantRef),
            ];
        } catch (\Throwable $e) {
            log_message('error', '[Pesapal] verify failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'reason' => 'verify error'];
        }
    }

    /**
     * One-time IPN URL registration → ipn_id (persisted in settings by the
     * pesapal:setup command). SubmitOrderRequest requires a registered IPN id.
     *
     * @return array{ok:bool, ipn_id?:string, reason?:string}
     */
    public function registerIpn(string $url, string $type = 'POST'): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'reason' => 'not configured'];
        }
        $token = $this->token();
        if ($token === null) {
            return ['ok' => false, 'reason' => 'authentication failed'];
        }
        try {
            $res   = $this->client()->post($this->baseUrl . '/api/URLSetup/RegisterIPN', [
                'headers'     => $this->authHeaders($token),
                'json'        => ['url' => $url, 'ipn_notification_type' => $type],
                'http_errors' => false,
            ]);
            $json  = json_decode((string) $res->getBody(), true) ?: [];
            $ipnId = (string) ($json['ipn_id'] ?? '');
            if ($ipnId !== '') {
                return ['ok' => true, 'ipn_id' => $ipnId];
            }
            return ['ok' => false, 'reason' => $this->errText($json) ?: ('http ' . $res->getStatusCode())];
        } catch (\Throwable $e) {
            log_message('error', '[Pesapal] registerIpn failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'reason' => 'register error'];
        }
    }

    // ------------------------------------------------------------------

    /**
     * Bearer token, cached ~4 min (PesaPal tokens live 5). Never logged.
     */
    private function token(): ?string
    {
        $cache = \Config\Services::cache();
        $key   = 'pesapal_token_' . md5($this->baseUrl . '|' . $this->consumerKey);
        $tok   = $cache->get($key);
        if (is_string($tok) && $tok !== '') {
            return $tok;
        }
        try {
            $res  = $this->client()->post($this->baseUrl . '/api/Auth/RequestToken', [
                'headers'     => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'json'        => ['consumer_key' => $this->consumerKey, 'consumer_secret' => $this->consumerSecret],
                'http_errors' => false,
            ]);
            $json = json_decode((string) $res->getBody(), true) ?: [];
            $tok  = (string) ($json['token'] ?? '');
            if ($tok !== '' && (string) ($json['status'] ?? '') === '200') {
                $cache->save($key, $tok, 240);
                return $tok;
            }
            log_message('error', '[Pesapal] auth failed: {m}', ['m' => $this->errText($json) ?: ('http ' . $res->getStatusCode())]);
            return null;
        } catch (\Throwable $e) {
            log_message('error', '[Pesapal] auth error: {m}', ['m' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * A bare cURL client. Headers are passed PER REQUEST (not here): CI4's
     * CurlRequest drops config-level default headers on these calls, so the
     * Authorization bearer must travel in each request's options. [gotcha]
     */
    private function client()
    {
        return \Config\Services::curlrequest(['timeout' => 25]);
    }

    /** Auth + JSON headers for an authenticated request. */
    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
    }

    /** Recover company_id from a TOPUP-{companyId}-{hex} merchant_reference. */
    private function companyFromRef(string $ref): int
    {
        // TOPUP-{company}-... (wallet) or SUB-{company}-{plan}-... (subscription)
        if (preg_match('/^(?:TOPUP|SUB)-(\d+)-/', $ref, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /** Extract a human message from PesaPal's error object / message field. */
    private function errText(array $json): string
    {
        if (! empty($json['error']) && is_array($json['error'])) {
            return (string) ($json['error']['message'] ?? $json['error']['code'] ?? '');
        }
        return (string) ($json['message'] ?? '');
    }
}
