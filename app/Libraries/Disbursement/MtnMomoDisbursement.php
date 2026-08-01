<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Disbursement;

/**
 * MTN MoMo Disbursements driver.
 *
 * Credentials come from system settings (Super Admin → Settings): mtn_api_user,
 * mtn_api_key, mtn_subscription_key (disbursement product), mtn_environment
 * (sandbox|mtnuganda), mtn_base_url. Follows the payments golden rules — every
 * transfer carries a caller-generated X-Reference-Id, the OAuth token is cached
 * with expiry, and status() is the reconciliation backstop for callbacks.
 *
 * Until credentials are present isConfigured() is false and the Disbursement
 * manager uses NullDisbursement instead, so this class is never asked to move
 * money in an unconfigured environment.
 */
class MtnMomoDisbursement implements DisbursementProviderInterface
{
    private string $apiUser;
    private string $apiKey;
    private string $subKey;
    private string $environment;
    private string $baseUrl;

    public function __construct()
    {
        helper('main');
        $this->apiUser     = system_setting('mtn_api_user') ?: '';
        $this->apiKey      = system_setting('mtn_api_key') ?: '';
        $this->subKey      = system_setting('mtn_subscription_key') ?: '';
        $this->environment = system_setting('mtn_environment') ?: 'sandbox';
        $this->baseUrl     = rtrim(system_setting('mtn_base_url') ?: 'https://sandbox.momodeveloper.mtn.com', '/');
    }

    public function name(): string
    {
        return 'mtn_momo';
    }

    public function isConfigured(): bool
    {
        return $this->apiUser !== '' && $this->apiKey !== '' && $this->subKey !== '';
    }

    public function validateAccountHolder(string $account): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'name' => null, 'reason' => 'not configured'];
        }

        try {
            $token = $this->token();
            $msisdn = $this->normalizeMsisdn($account);
            $res = $this->client()->get("/disbursement/v1_0/accountholder/msisdn/{$msisdn}/active", [
                'headers' => $this->authHeaders($token),
                'http_errors' => false,
            ]);
            $active = trim((string) $res->getBody());
            // Name lookup (basicuserinfo) is a separate call; kept minimal here.
            return ['ok' => $active === 'true' || $active === '1', 'name' => null, 'reason' => null];
        } catch (\Throwable $e) {
            log_message('error', '[MoMo] validateAccountHolder failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'name' => null, 'reason' => 'lookup error'];
        }
    }

    public function transfer(array $params): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'status' => 'failed', 'provider_txn_id' => null, 'raw' => null, 'reason' => 'not configured'];
        }

        // Caller-generated reference id is mandatory (idempotency).
        $reference = (string) ($params['reference'] ?? '');
        if ($reference === '') {
            return ['ok' => false, 'status' => 'failed', 'provider_txn_id' => null, 'raw' => null, 'reason' => 'missing reference'];
        }

        try {
            $token = $this->token();
            $res = $this->client()->post('/disbursement/v1_0/transfer', [
                'headers' => $this->authHeaders($token) + [
                    'X-Reference-Id'        => $reference,
                    'X-Target-Environment'  => $this->environment,
                    'Content-Type'          => 'application/json',
                ],
                'json' => [
                    'amount'       => (string) $params['amount'],
                    'currency'     => $params['currency'] ?? 'UGX',
                    'externalId'   => $reference,
                    'payee'        => ['partyIdType' => 'MSISDN', 'partyId' => $this->normalizeMsisdn((string) $params['account'])],
                    'payerMessage' => mb_substr((string) ($params['note'] ?? 'Payout'), 0, 160),
                    'payeeNote'    => mb_substr((string) ($params['note'] ?? 'Payout'), 0, 160),
                ],
                'http_errors' => false,
            ]);
            // 202 Accepted ⇒ pending; poll status() for the terminal outcome.
            $accepted = $res->getStatusCode() === 202;
            return [
                'ok'              => $accepted,
                'status'          => $accepted ? 'pending' : 'failed',
                'provider_txn_id' => $reference,
                'raw'             => (string) $res->getBody(),
                'reason'          => $accepted ? null : ('http ' . $res->getStatusCode()),
            ];
        } catch (\Throwable $e) {
            log_message('error', '[MoMo] transfer failed: {m}', ['m' => $e->getMessage()]);
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
            $res = $this->client()->get("/disbursement/v1_0/transfer/{$reference}", [
                'headers' => $this->authHeaders($token) + ['X-Target-Environment' => $this->environment],
                'http_errors' => false,
            ]);
            $body = json_decode((string) $res->getBody(), true);
            $map  = ['SUCCESSFUL' => 'successful', 'FAILED' => 'failed', 'PENDING' => 'pending'];
            $s    = $map[$body['status'] ?? ''] ?? 'unknown';
            return ['status' => $s, 'raw' => (string) $res->getBody()];
        } catch (\Throwable $e) {
            log_message('error', '[MoMo] status failed: {m}', ['m' => $e->getMessage()]);
            return ['status' => 'unknown', 'raw' => null];
        }
    }

    // ------------------------------------------------------------------

    /** Cached OAuth token (per environment); refreshed before expiry. */
    private function token(): string
    {
        $cache = \Config\Services::cache();
        $key   = 'momo_disb_token_' . $this->environment;
        if ($tok = $cache->get($key)) {
            return $tok;
        }

        $res = $this->client()->post('/disbursement/token/', [
            'headers' => [
                'Authorization'          => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiKey),
                'Ocp-Apim-Subscription-Key' => $this->subKey,
            ],
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

    private function authHeaders(string $token): array
    {
        return [
            'Authorization'             => 'Bearer ' . $token,
            'Ocp-Apim-Subscription-Key' => $this->subKey,
        ];
    }

    private function client()
    {
        return \Config\Services::curlrequest(['baseURI' => $this->baseUrl, 'timeout' => 20]);
    }

    /** Uganda MSISDN in MoMo's expected 256XXXXXXXXX form. */
    private function normalizeMsisdn(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw);
        if (strpos($d, '0') === 0) {
            $d = '256' . substr($d, 1);
        } elseif (strpos($d, '256') !== 0 && strlen($d) === 9) {
            $d = '256' . $d;
        }
        return $d;
    }
}
