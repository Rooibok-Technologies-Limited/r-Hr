<?php

namespace App\Libraries;

/**
 * MTN Mobile Money Collections API Client
 *
 * Integrates with MTN MoMo Collections API to request payments
 * via USSD push and check transaction status.
 *
 * Phase 3.3 - UPGRADE.md
 */
class MtnMomo
{
    private string $baseUrl;
    private string $subscriptionKey;
    private string $apiUser;
    private string $apiKey;
    private string $environment;
    private string $currency = 'UGX';

    public function __construct()
    {
        $this->subscriptionKey = system_setting('mtn_subscription_key') ?? '';
        $this->apiUser         = system_setting('mtn_api_user') ?? '';
        $this->apiKey          = system_setting('mtn_api_key') ?? '';
        $this->environment     = system_setting('mtn_environment') ?? 'sandbox';

        $this->baseUrl = $this->environment === 'production'
            ? 'https://proxy.momoapi.mtn.com'
            : 'https://sandbox.momodeveloper.mtn.com';
    }

    /**
     * Obtain an OAuth2 access token from MTN MoMo API.
     *
     * @return string The access token, or empty string on failure.
     */
    public function getToken(): string
    {
        $url     = $this->baseUrl . '/collection/token/';
        $headers = [
            'Authorization: Basic ' . base64_encode($this->apiUser . ':' . $this->apiKey),
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
        ];

        $response = $this->httpRequest('POST', $url, $headers);

        if (isset($response['body']['access_token'])) {
            return $response['body']['access_token'];
        }

        log_message('error', '[MtnMomo] Failed to obtain token: ' . json_encode($response));
        return '';
    }

    /**
     * Request a payment (Collection). Triggers a USSD prompt on
     * the customer's phone asking them to authorize the debit.
     *
     * @param string $phone    MSISDN in international format (e.g. 256771234567)
     * @param int    $amount   Amount in smallest currency unit
     * @param string $reference External reference / invoice number
     *
     * @return array{success: bool, reference_id: string, status_code: int, error?: string}
     */
    public function requestPayment(string $phone, int $amount, string $reference): array
    {
        $token = $this->getToken();
        if ($token === '') {
            return [
                'success'      => false,
                'reference_id' => '',
                'status_code'  => 0,
                'error'        => 'Failed to obtain access token',
            ];
        }

        $referenceId = $this->generateUuid();

        $url     = $this->baseUrl . '/collection/v1_0/requesttopay';
        $headers = [
            'Authorization: Bearer ' . $token,
            'X-Reference-Id: ' . $referenceId,
            'X-Target-Environment: ' . $this->environment,
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'Content-Type: application/json',
        ];

        $body = [
            'amount'       => (string) $amount,
            'currency'     => $this->currency,
            'externalId'   => $reference,
            'payer'        => [
                'partyIdType' => 'MSISDN',
                'partyId'     => $phone,
            ],
            'payerMessage' => 'Payment for ' . $reference,
            'payeeNote'    => 'Rooibok HR - ' . $reference,
        ];

        $response = $this->httpRequest('POST', $url, $headers, $body);

        $statusCode = $response['status_code'] ?? 0;

        // 202 Accepted means the request was queued successfully
        if ($statusCode === 202) {
            return [
                'success'      => true,
                'reference_id' => $referenceId,
                'status_code'  => $statusCode,
            ];
        }

        log_message('error', '[MtnMomo] requestPayment failed: ' . json_encode($response));

        return [
            'success'      => false,
            'reference_id' => $referenceId,
            'status_code'  => $statusCode,
            'error'        => $response['body']['message'] ?? 'Request to pay failed',
        ];
    }

    /**
     * Check the status of a previously requested payment.
     *
     * @param string $referenceId The X-Reference-Id used when creating the request.
     *
     * @return array{success: bool, status: string, reason?: string, amount?: string, currency?: string, payer?: array, error?: string}
     */
    public function getPaymentStatus(string $referenceId): array
    {
        $token = $this->getToken();
        if ($token === '') {
            return [
                'success' => false,
                'status'  => 'UNKNOWN',
                'error'   => 'Failed to obtain access token',
            ];
        }

        $url     = $this->baseUrl . '/collection/v1_0/requesttopay/' . $referenceId;
        $headers = [
            'Authorization: Bearer ' . $token,
            'X-Target-Environment: ' . $this->environment,
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
        ];

        $response = $this->httpRequest('GET', $url, $headers);

        $statusCode = $response['status_code'] ?? 0;

        if ($statusCode === 200 && isset($response['body']['status'])) {
            $body = $response['body'];
            return [
                'success'  => true,
                'status'   => $body['status'], // SUCCESSFUL, FAILED, PENDING
                'reason'   => $body['reason'] ?? '',
                'amount'   => $body['amount'] ?? '',
                'currency' => $body['currency'] ?? '',
                'payer'    => $body['payer'] ?? [],
            ];
        }

        log_message('error', '[MtnMomo] getPaymentStatus failed: ' . json_encode($response));

        return [
            'success' => false,
            'status'  => 'UNKNOWN',
            'error'   => $response['body']['message'] ?? 'Failed to retrieve payment status',
        ];
    }

    /**
     * Generate a UUID v4.
     */
    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant RFC 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Execute an HTTP request via cURL.
     *
     * @param string     $method  HTTP method (GET, POST, etc.)
     * @param string     $url     Full request URL
     * @param array      $headers Indexed array of header strings
     * @param array|null $body    Request body (will be JSON-encoded)
     *
     * @return array{status_code: int, body: mixed, error?: string}
     */
    private function httpRequest(string $method, string $url, array $headers, ?array $body = null): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERAGENT      => 'RooibokHR/1.0 MtnMomo-PHP',
        ]);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                }
                break;
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                }
                break;
        }

        $responseBody = curl_exec($ch);
        $statusCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            log_message('error', '[MtnMomo] cURL error: ' . $curlError . ' URL: ' . $url);
            return [
                'status_code' => 0,
                'body'        => [],
                'error'       => $curlError,
            ];
        }

        $decoded = json_decode($responseBody, true);

        return [
            'status_code' => $statusCode,
            'body'        => is_array($decoded) ? $decoded : ['raw' => $responseBody],
        ];
    }
}
