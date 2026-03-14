<?php
/**
 * TOTP (Time-based One-Time Password) Library
 * Implements RFC 6238 TOTP with HMAC-SHA1
 *
 * Part of Rooibok HR System - Phase 2.1 Two-Factor Authentication
 */
namespace App\Libraries;

class Totp
{
    /**
     * Base32 alphabet used for encoding/decoding secrets
     */
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Time step in seconds (RFC 6238 default)
     */
    private const TIME_STEP = 30;

    /**
     * Number of digits in the OTP code
     */
    private const CODE_DIGITS = 6;

    /**
     * Generate a random 16-character base32 secret
     *
     * @return string 16-character base32 encoded secret
     */
    public function generateSecret(): string
    {
        $secret = '';
        $chars = self::BASE32_CHARS;
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Get the otpauth:// URI for QR code generation
     *
     * @param string $secret The base32 encoded secret
     * @param string $label  The label (usually email or username)
     * @param string $issuer The issuer name (application name)
     * @return string otpauth:// URI
     */
    public function getQrCodeUrl(string $secret, string $label, string $issuer): string
    {
        $label = rawurlencode($label);
        $issuer = rawurlencode($issuer);
        return "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Verify a 6-digit TOTP code against a secret
     *
     * @param string $secret The base32 encoded secret
     * @param string $code   The 6-digit code to verify
     * @param int    $window The number of time steps to check before/after current (default +-1)
     * @return bool True if the code is valid
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        if (strlen($code) !== self::CODE_DIGITS || !ctype_digit($code)) {
            return false;
        }

        $currentTimeSlice = (int) floor(time() / self::TIME_STEP);

        for ($i = -$window; $i <= $window; $i++) {
            $calculatedCode = $this->generateCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate 8 random 8-character alphanumeric backup codes
     *
     * @param int $count Number of backup codes to generate
     * @return array Array of plaintext backup codes
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $codes[] = $code;
        }
        return $codes;
    }

    /**
     * Generate a TOTP code for a given time slice
     *
     * @param string $secret    Base32 encoded secret
     * @param int    $timeSlice The time slice counter
     * @return string 6-digit TOTP code
     */
    private function generateCode(string $secret, int $timeSlice): string
    {
        // Decode base32 secret to binary
        $secretKey = $this->base32Decode($secret);

        // Pack time into 8-byte big-endian binary string
        $time = pack('N*', 0, $timeSlice);

        // Generate HMAC-SHA1 hash
        $hmac = hash_hmac('sha1', $time, $secretKey, true);

        // Dynamic truncation per RFC 4226
        $offset = ord($hmac[19]) & 0x0F;
        $code = (
            ((ord($hmac[$offset]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF)
        ) % pow(10, self::CODE_DIGITS);

        return str_pad((string) $code, self::CODE_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a base32 encoded string to binary
     *
     * @param string $base32 Base32 encoded string
     * @return string Binary data
     */
    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        $base32 = str_replace('=', '', $base32);

        $lookup = [];
        $chars = self::BASE32_CHARS;
        for ($i = 0; $i < 32; $i++) {
            $lookup[$chars[$i]] = $i;
        }

        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($base32); $i < $len; $i++) {
            $char = $base32[$i];
            if (!isset($lookup[$char])) {
                continue;
            }
            $buffer = ($buffer << 5) | $lookup[$char];
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
