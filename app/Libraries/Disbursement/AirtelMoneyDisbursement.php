<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Disbursement;

/**
 * Airtel Money Disbursements driver.
 *
 * Credentials from system settings: airtel_client_id, airtel_client_secret,
 * airtel_environment (staging|production), airtel_base_url, airtel_country
 * (UG), airtel_currency (UGX). Same discipline as the MoMo driver: cached
 * OAuth token, caller-generated transaction id, status() reconciliation.
 * Degrades to NullDisbursement until configured.
 */
class AirtelMoneyDisbursement implements DisbursementProviderInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $country;
    private string $currency;
    private string $baseUrl;

    public function __construct()
    {
        helper('main');
        $this->clientId     = system_setting('airtel_client_id') ?: '';
        $this->clientSecret = system_setting('airtel_client_secret') ?: '';
        $this->country      = system_setting('airtel_country') ?: 'UG';
        $this->currency     = system_setting('airtel_currency') ?: 'UGX';
        $this->baseUrl      = rtrim(system_setting('airtel_base_url') ?: 'https://openapiuat.airtel.africa', '/');
    }

    public function name(): string
    {
        return 'airtel';
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function validateAccountHolder(string $account): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'name' => null, 'reason' => 'not configured'];
        }

        try {
            $token  = $this->token();
            $msisdn = $this->normalizeMsisdn($account);
            $res = $this->client()->get("/standard/v1/users/{$msisdn}", [
                'headers' => $this->headers($token),
                'http_errors' => false,
            ]);
            $body = json_decode((string) $res->getBody(), true);
            $name = $body['data']['first_name'] ?? null;
            return ['ok' => $res->getStatusCode() === 200, 'name' => $name, 'reason' => null];
        } catch (\Throwable $e) {
            log_message('error', '[Airtel] validateAccountHolder failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'name' => null, 'reason' => 'lookup error'];
        }
    }

    public function transfer(array $params): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'status' => 'failed', 'provider_txn_id' => null, 'raw' => null, 'reason' => 'not configured'];
        }
        $reference = (string) ($params['reference'] ?? '');
        if ($reference === '') {
            return ['ok' => false, 'status' => 'failed', 'provider_txn_id' => null, 'raw' => null, 'reason' => 'missing reference'];
        }

        try {
            $token = $this->token();
            $res = $this->client()->post('/standard/v1/disbursements/', [
                'headers' => $this->headers($token) + ['Content-Type' => 'application/json'],
                'json' => [
                    'payee'       => ['msisdn' => $this->normalizeMsisdn((string) $params['account'])],
                    'reference'   => $reference,
                    'pin'         => system_setting('airtel_pin') ?: '',
                    'transaction' => ['amount' => (int) $params['amount'], 'id' => $reference],
                ],
                'http_errors' => false,
            ]);
            $body    = json_decode((string) $res->getBody(), true);
            $success = ($body['status']['success'] ?? false) === true;
            return [
                'ok'              => $success,
                'status'          => $success ? 'pending' : 'failed',
                'provider_txn_id' => $reference,
                'raw'             => (string) $res->getBody(),
                'reason'          => $success ? null : ($body['status']['message'] ?? 'transfer rejected'),
            ];
        } catch (\Throwable $e) {
            log_message('error', '[Airtel] transfer failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'status' => 'failed', 'provider_txn_id' => $reference, 'raw' => null, 'reason' => 'transfer error'];
        }
    }

    public function status(string $reference): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'unknown', 'raw' => null];
        }
        try {
            $token = $this->token();
            $res = $this->client()->get("/standard/v1/disbursements/{$reference}", [
                'headers' => $this->headers($token),
                'http_errors' => false,
            ]);
            $body = json_decode((string) $res->getBody(), true);
            $tx   = strtoupper((string) ($body['data']['transaction']['status'] ?? ''));
            $map  = ['TS' => 'successful', 'TF' => 'failed', 'TA' => 'pending', 'TIP' => 'pending'];
            return ['status' => $map[$tx] ?? 'unknown', 'raw' => (string) $res->getBody()];
        } catch (\Throwable $e) {
            log_message('error', '[Airtel] status failed: {m}', ['m' => $e->getMessage()]);
            return ['status' => 'unknown', 'raw' => null];
        }
    }

    // ------------------------------------------------------------------

    private function token(): string
    {
        $cache = \Config\Services::cache();
        $key   = 'airtel_disb_token';
        if ($tok = $cache->get($key)) {
            return $tok;
        }
        $res = $this->client()->post('/auth/oauth2/token', [
            'headers' => ['Content-Type' => 'application/json'],
            'json'    => ['client_id' => $this->clientId, 'client_secret' => $this->clientSecret, 'grant_type' => 'client_credentials'],
            'http_errors' => false,
        ]);
        $body = json_decode((string) $res->getBody(), true);
        $tok  = $body['access_token'] ?? '';
        $ttl  = max(60, (int) ($body['expires_in'] ?? 3600) - 60);
        if ($tok !== '') {
            $cache->save($key, $tok, $ttl);
        }
        return $tok;
    }

    private function headers(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'X-Country'     => $this->country,
            'X-Currency'    => $this->currency,
            'Accept'        => 'application/json',
        ];
    }

    private function client()
    {
        return \Config\Services::curlrequest(['baseURI' => $this->baseUrl, 'timeout' => 20]);
    }

    private function normalizeMsisdn(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw);
        if (strpos($d, '256') === 0) {
            $d = substr($d, 3);
        } elseif (strpos($d, '0') === 0) {
            $d = substr($d, 1);
        }
        return $d; // Airtel expects the national number (no country code).
    }
}
