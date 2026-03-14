<?php

namespace App\Libraries;

/**
 * Airtel Africa Payments API Client
 *
 * Integrates with Airtel Money API to request payments
 * via USSD push and check transaction status.
 *
 * Phase 3.4 - UPGRADE.md
 */
class AirtelMoney
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $environment;
    private string $country  = 'UG';
    private string $currency = 'UGX';

    public function __construct()
    {
        $this->clientId     = system_setting('airtel_client_id') ?? '';
        $this->clientSecret = system_setting('airtel_client_secret') ?? '';
        $this->environment  = system_setting('airtel_environment') ?? 'sandbox';

        $this->baseUrl = $this->environment === 'production'
            ? 'https://openapi.airtel.africa'
            : 'https://openapiuat.airtel.africa';
    }

    /**
     * Obtain an OAuth2 access token using client credentials grant.
     *
     * @return string The access token, or empty string on failure.
     */
    public function getToken(): string
    {
        $url     = $this->baseUrl . '/auth/oauth2/token';
        $headers = [
            'Content-Type: application/json',
            'Accept: */*',
        ];

        $body = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'client_credentials',
        ];

        $response = $this->httpRequest('POST', $url, $headers, $body);

        if (isset($response['body']['access_token'])) {
            return $response['body']['access_token'];
        }

        log_message('error', '[AirtelMoney] Failed to obtain token: ' . json_encode($response));
        return '';
    }

    /**
     * Request a payment (USSD push). The subscriber receives a
     * USSD prompt to authorize the debit from their Airtel Money wallet.
     *
     * @param string $phone    MSISDN without country code (e.g. 771234567)
     * @param int    $amount   Amount in smallest currency unit
     * @param string $reference External reference / invoice number
     *
     * @return array{success: bool, transaction_id: string, status_code: int, error?: string}
     */
    public function requestPayment(string $phone, int $amount, string $reference): array
    {
        $token = $this->getToken();
        if ($token === '') {
            return [
                'success'        => false,
                'transaction_id' => '',
                'status_code'    => 0,
                'error'          => 'Failed to obtain access token',
            ];
        }

        $transactionId = $this->generateTransactionId();

        $url     = $this->baseUrl . '/merchant/v2/payments/';
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'X-Country: ' . $this->country,
            'X-Currency: ' . $this->currency,
        ];

        $body = [
            'reference' => $reference,
            'subscriber' => [
                'country'  => $this->country,
                'currency' => $this->currency,
                'msisdn'   => $phone,
            ],
            'transaction' => [
                'amount'   => $amount,
                'country'  => $this->country,
                'currency' => $this->currency,
                'id'       => $transactionId,
            ],
        ];

        $response   = $this->httpRequest('POST', $url, $headers, $body);
        $statusCode = $response['status_code'] ?? 0;
        $respBody   = $response['body'] ?? [];

        // Airtel returns 200 with status.code "200" on success
        $airtelCode = $respBody['status']['code'] ?? '';

        if ($statusCode === 200 && $airtelCode === '200') {
            return [
                'success'        => true,
                'transaction_id' => $transactionId,
                'status_code'    => $statusCode,
            ];
        }

        log_message('error', '[AirtelMoney] requestPayment failed: ' . json_encode($response));

        return [
            'success'        => false,
            'transaction_id' => $transactionId,
            'status_code'    => $statusCode,
            'error'          => $respBody['status']['message'] ?? 'Payment request failed',
        ];
    }

    /**
     * Check the status of a previously requested payment.
     *
     * @param string $transactionId The transaction ID used when creating the request.
     *
     * @return array{success: bool, status: string, amount?: string, currency?: string, error?: string}
     */
    public function getPaymentStatus(string $transactionId): array
    {
        $token = $this->getToken();
        if ($token === '') {
            return [
                'success' => false,
                'status'  => 'UNKNOWN',
                'error'   => 'Failed to obtain access token',
            ];
        }

        $url     = $this->baseUrl . '/standard/v1/payments/' . $transactionId;
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'X-Country: ' . $this->country,
            'X-Currency: ' . $this->currency,
        ];

        $response   = $this->httpRequest('GET', $url, $headers);
        $statusCode = $response['status_code'] ?? 0;
        $respBody   = $response['body'] ?? [];

        if ($statusCode === 200 && isset($respBody['data']['transaction']['status'])) {
            $txn = $respBody['data']['transaction'];
            return [
                'success'  => true,
                'status'   => $txn['status'], // TIP, TS, TF, TA (initiated, success, failed, ambiguous)
                'amount'   => $txn['amount'] ?? '',
                'currency' => $txn['currency'] ?? '',
            ];
        }

        log_message('error', '[AirtelMoney] getPaymentStatus failed: ' . json_encode($response));

        return [
            'success' => false,
            'status'  => 'UNKNOWN',
            'error'   => $respBody['status']['message'] ?? 'Failed to retrieve payment status',
        ];
    }

    /**
     * Generate a unique transaction ID.
     */
    private function generateTransactionId(): string
    {
        return 'RBK-' . strtoupper(bin2hex(random_bytes(8))) . '-' . time();
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
            CURLOPT_USERAGENT      => 'RooibokHR/1.0 AirtelMoney-PHP',
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
            log_message('error', '[AirtelMoney] cURL error: ' . $curlError . ' URL: ' . $url);
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
