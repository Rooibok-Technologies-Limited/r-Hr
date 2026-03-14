<?php

namespace App\Libraries;

use RuntimeException;

class JwtAuth
{
    private string $secret;
    private string $algorithm;
    private int    $ttl;

    public function __construct()
    {
        $config          = config('Jwt');
        $this->algorithm = $config->algorithm;
        $this->ttl       = $config->ttl;

        // Load secret from system_setting helper (cached DB lookup)
        $this->secret = system_setting('jwt_secret');

        if (empty($this->secret)) {
            throw new RuntimeException('JWT secret is not configured. Set jwt_secret in system settings.');
        }
    }

    /**
     * Create a JWT token from the given payload.
     */
    public function encode(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $payload['iat'] = $payload['iat'] ?? time();
        $payload['exp'] = $payload['exp'] ?? (time() + $this->ttl);

        $segments   = [];
        $segments[] = $this->base64UrlEncode(json_encode($header));
        $segments[] = $this->base64UrlEncode(json_encode($payload));

        $signingInput = implode('.', $segments);
        $signature    = hash_hmac('sha256', $signingInput, $this->secret, true);
        $segments[]   = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Decode and verify a JWT token. Returns the payload array.
     *
     * @throws RuntimeException on invalid or expired token
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token structure');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verify signature
        $signingInput    = $headerB64 . '.' . $payloadB64;
        $expectedSig     = hash_hmac('sha256', $signingInput, $this->secret, true);
        $providedSig     = $this->base64UrlDecode($signatureB64);

        if (!hash_equals($expectedSig, $providedSig)) {
            throw new RuntimeException('Invalid token signature');
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid token payload');
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new RuntimeException('Token has expired');
        }

        return $payload;
    }

    /**
     * Get the configured TTL in seconds.
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}
