<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Disbursement;

/**
 * Flutterwave Transfers driver — the pooled-aggregator payout path (ADR-002).
 *
 * When `disbursement_aggregator = flutterwave`, the engine routes EVERY payout
 * (momo/airtel/bank) through this one driver: Flutterwave holds the master float
 * and fans out to the right rail. Credentials come from system settings
 * (flutterwave_secret_key etc., encrypted at rest). Follows the payments golden
 * rules — the caller-generated `reference` is the idempotency key, transfers are
 * async (return `pending`; the webhook / status() poll settle them), and the
 * driver degrades to NullDisbursement when unconfigured so nothing moves by
 * accident.
 *
 * @see https://developer.flutterwave.com/reference/create-a-transfer
 */
class FlutterwaveDisbursement implements DisbursementProviderInterface
{
    private string $secretKey;
    private string $baseUrl;

    /** Uganda mobile-money network → Flutterwave account_bank code. */
    private const MOMO_BANKS = [
        'momo'   => 'MPS',   // MTN / mobile money switch (UG)
        'mtn'    => 'MPS',
        'airtel' => 'MPS',
    ];

    public function __construct()
    {
        helper('main');
        $this->secretKey = system_setting('flutterwave_secret_key') ?: '';
        $this->baseUrl   = rtrim(system_setting('flutterwave_base_url') ?: 'https://api.flutterwave.com/v3', '/');
    }

    public function name(): string
    {
        return 'flutterwave';
    }

    public function isConfigured(): bool
    {
        return $this->secretKey !== '';
    }

    /**
     * Bank account name-resolution (name lookup). Mobile-money name lookup is not
     * offered, so momo/airtel destinations fall back to the OTP control check.
     */
    public function validateAccountHolder(string $account): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'name' => null, 'reason' => 'not configured'];
        }
        return ['ok' => true, 'name' => null, 'reason' => 'name lookup deferred to OTP control check'];
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

        $type     = strtolower((string) ($params['type'] ?? 'momo'));
        $currency = $params['currency'] ?? 'UGX';
        $body     = [
            'account_number' => $this->normalizeMsisdn((string) $params['account'], $type),
            'amount'         => (float) $params['amount'],
            'currency'       => $currency,
            'reference'      => $reference,               // our idempotency key
            'narration'      => mb_substr((string) ($params['note'] ?? 'Payout'), 0, 100),
            'debit_currency' => $currency,
        ];
        if ($type === 'bank') {
            // Bank transfers need an explicit destination bank code. Fail fast
            // with a clear reason rather than posting an empty account_bank the
            // provider would reject opaquely.
            $bankCode = (string) ($params['bank_code'] ?? '');
            if ($bankCode === '') {
                return ['ok' => false, 'status' => 'failed', 'provider_txn_id' => null, 'raw' => null, 'reason' => 'missing bank code for bank payout'];
            }
            $body['account_bank'] = $bankCode;
        } else {
            $body['account_bank'] = self::MOMO_BANKS[$type] ?? 'MPS';
        }

        try {
            $res  = $this->client()->post($this->baseUrl . '/transfers', ['headers' => $this->authHeaders(), 'json' => $body, 'http_errors' => false]);
            $json = json_decode((string) $res->getBody(), true) ?: [];
            $data = $json['data'] ?? [];
            $ok   = ($json['status'] ?? '') === 'success' && isset($data['id']);
            return [
                'ok'              => $ok,
                'status'          => $ok ? $this->mapStatus($data['status'] ?? 'NEW') : 'failed',
                'provider_txn_id' => isset($data['id']) ? (string) $data['id'] : null,
                'raw'             => (string) $res->getBody(),
                'reason'          => $ok ? null : ($json['message'] ?? ('http ' . $res->getStatusCode())),
            ];
        } catch (\Throwable $e) {
            log_message('error', '[Flutterwave] transfer failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'status' => 'failed', 'provider_txn_id' => null, 'raw' => null, 'reason' => 'transfer error'];
        }
    }

    public function status(string $reference): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'unknown', 'raw' => null];
        }
        try {
            // Transfers are queried by our client reference.
            $res  = $this->client()->get($this->baseUrl . '/transfers?reference=' . rawurlencode($reference), ['headers' => $this->authHeaders(), 'http_errors' => false]);
            $json = json_decode((string) $res->getBody(), true) ?: [];
            $row  = $json['data'][0] ?? null;
            if (! $row) {
                return ['status' => 'unknown', 'raw' => (string) $res->getBody()];
            }
            return ['status' => $this->mapStatus($row['status'] ?? ''), 'raw' => (string) $res->getBody()];
        } catch (\Throwable $e) {
            log_message('error', '[Flutterwave] status failed: {m}', ['m' => $e->getMessage()]);
            return ['status' => 'unknown', 'raw' => null];
        }
    }

    /** Master-float available balance for a currency (reconciliation oversight). */
    public function balance(string $currency = 'UGX'): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'available' => null, 'currency' => $currency, 'reason' => 'not configured'];
        }
        try {
            $res  = $this->client()->get($this->baseUrl . '/balances/' . rawurlencode($currency), ['headers' => $this->authHeaders(), 'http_errors' => false]);
            $json = json_decode((string) $res->getBody(), true) ?: [];
            $data = $json['data'] ?? [];
            return [
                'ok'        => ($json['status'] ?? '') === 'success',
                'available' => isset($data['available_balance']) ? (float) $data['available_balance'] : null,
                'ledger'    => isset($data['ledger_balance']) ? (float) $data['ledger_balance'] : null,
                'currency'  => $currency,
            ];
        } catch (\Throwable $e) {
            log_message('error', '[Flutterwave] balance failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'available' => null, 'currency' => $currency, 'reason' => 'balance error'];
        }
    }

    // ------------------------------------------------------------------

    private function mapStatus(string $s): string
    {
        return [
            'SUCCESSFUL' => 'successful',
            'FAILED'     => 'failed',
            'PENDING'    => 'pending',
            'NEW'        => 'pending',
        ][strtoupper($s)] ?? 'pending';
    }

    // Headers travel per-request: CI4 CurlRequest drops config-level defaults
    // on these calls, so the bearer must be in each request's options. [gotcha]
    private function client()
    {
        return \Config\Services::curlrequest(['timeout' => 25]);
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type'  => 'application/json',
        ];
    }

    /** Bank numbers pass through; mobile numbers to 256XXXXXXXXX. */
    private function normalizeMsisdn(string $raw, string $type): string
    {
        if ($type === 'bank') {
            return preg_replace('/\s+/', '', $raw);
        }
        $d = preg_replace('/\D+/', '', $raw);
        if (strpos($d, '0') === 0) {
            $d = '256' . substr($d, 1);
        } elseif (strpos($d, '256') !== 0 && strlen($d) === 9) {
            $d = '256' . $d;
        }
        return $d;
    }
}
