<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Sms;

/**
 * Africa's Talking SMS gateway driver.
 *
 * Credentials are read from system settings (Super Admin → Settings → SMS tab),
 * consistent with the MtnMomo / AirtelMoney libraries:
 *   - sms_username     Africa's Talking username ("sandbox" for the sandbox)
 *   - sms_api_key      API key (stored encrypted; decrypted by system_setting())
 *   - sms_sender_id    Optional alphanumeric/short-code sender ("from")
 *   - sms_environment  "production" (default) or "sandbox"
 *
 * @see https://developers.africastalking.com/docs/sms/sending/bulk
 */
class AfricasTalkingProvider implements SmsProviderInterface
{
    private string $username;
    private string $apiKey;
    private string $senderId;
    private string $endpoint;

    public function __construct()
    {
        $this->username = system_setting('sms_username') ?: '';
        $this->apiKey   = system_setting('sms_api_key') ?: '';
        $this->senderId = system_setting('sms_sender_id') ?: '';

        $environment    = system_setting('sms_environment') ?: 'production';
        $this->endpoint = $environment === 'sandbox'
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';
    }

    /**
     * True when the driver has the minimum credentials to attempt a send.
     */
    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->apiKey !== '';
    }

    public function send(string $to, string $message): bool
    {
        if (! $this->isConfigured()) {
            log_message('error', '[SMS:africastalking] Missing username/api_key — cannot send to {to}', ['to' => $to]);
            return false;
        }

        $fields = [
            'username' => $this->username,
            'to'       => $this->normalise($to),
            'message'  => $message,
        ];

        if ($this->senderId !== '') {
            $fields['from'] = $this->senderId;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_HTTPHEADER     => [
                'apiKey: ' . $this->apiKey,
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_USERAGENT      => 'RooibokHR/1.0 AfricasTalking-PHP',
        ]);

        $body       = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            log_message('error', '[SMS:africastalking] cURL error: {err}', ['err' => $curlError]);
            return false;
        }

        $decoded    = json_decode((string) $body, true);
        $recipients = $decoded['SMSMessageData']['Recipients'] ?? [];

        // Africa's Talking returns statusCode 100 (processed) / 101 (sent) on success.
        foreach ($recipients as $recipient) {
            $code = (int) ($recipient['statusCode'] ?? 0);
            if ($code === 100 || $code === 101) {
                return true;
            }
        }

        log_message('error', '[SMS:africastalking] Send rejected (HTTP {http}): {body}', [
            'http' => $statusCode,
            'body' => is_string($body) ? mb_substr($body, 0, 300) : '',
        ]);

        return false;
    }

    /**
     * Coerce a number into the E.164 form Africa's Talking expects (leading +).
     */
    private function normalise(string $to): string
    {
        $to = trim($to);
        if ($to === '') {
            return $to;
        }
        if ($to[0] === '+') {
            return '+' . preg_replace('/\D/', '', substr($to, 1));
        }

        return '+' . preg_replace('/\D/', '', $to);
    }
}
