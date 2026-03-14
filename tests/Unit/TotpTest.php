<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\Totp;

/**
 * Unit tests for the TOTP (Time-based One-Time Password) library.
 */
class TotpTest extends CIUnitTestCase
{
    private Totp $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new Totp();
    }

    // ---------------------------------------------------------------
    //  Secret Generation
    // ---------------------------------------------------------------

    public function testSecretGenerationIs16CharsBase32(): void
    {
        $secret = $this->totp->generateSecret();

        $this->assertEquals(16, strlen($secret), 'Secret should be exactly 16 characters');
        $this->assertMatchesRegularExpression(
            '/^[A-Z2-7]{16}$/',
            $secret,
            'Secret should contain only valid base32 characters (A-Z, 2-7)'
        );
    }

    public function testSecretGenerationIsRandom(): void
    {
        $secret1 = $this->totp->generateSecret();
        $secret2 = $this->totp->generateSecret();

        $this->assertNotEquals($secret1, $secret2, 'Two generated secrets should not be identical');
    }

    // ---------------------------------------------------------------
    //  QR Code URL
    // ---------------------------------------------------------------

    public function testQrUrlContainsOtpauth(): void
    {
        $secret = $this->totp->generateSecret();
        $url = $this->totp->getQrCodeUrl($secret, 'user@example.com', 'Rooibok HR');

        $this->assertStringStartsWith('otpauth://totp/', $url, 'QR URL should start with otpauth://totp/');
    }

    public function testQrUrlContainsSecret(): void
    {
        $secret = $this->totp->generateSecret();
        $url = $this->totp->getQrCodeUrl($secret, 'user@example.com', 'Rooibok HR');

        $this->assertStringContainsString("secret={$secret}", $url, 'QR URL should contain the secret');
    }

    public function testQrUrlContainsIssuer(): void
    {
        $secret = $this->totp->generateSecret();
        $url = $this->totp->getQrCodeUrl($secret, 'user@example.com', 'Rooibok HR');

        $this->assertStringContainsString('issuer=Rooibok%20HR', $url, 'QR URL should contain the issuer');
    }

    public function testQrUrlContainsAlgorithmAndDigits(): void
    {
        $secret = $this->totp->generateSecret();
        $url = $this->totp->getQrCodeUrl($secret, 'user@example.com', 'Rooibok HR');

        $this->assertStringContainsString('algorithm=SHA1', $url);
        $this->assertStringContainsString('digits=6', $url);
        $this->assertStringContainsString('period=30', $url);
    }

    // ---------------------------------------------------------------
    //  Code Verification
    // ---------------------------------------------------------------

    public function testVerifyCodeRejectsInvalidFormat(): void
    {
        $secret = $this->totp->generateSecret();

        $this->assertFalse($this->totp->verifyCode($secret, ''), 'Empty code should fail');
        $this->assertFalse($this->totp->verifyCode($secret, '12345'), 'Five-digit code should fail');
        $this->assertFalse($this->totp->verifyCode($secret, '1234567'), 'Seven-digit code should fail');
        $this->assertFalse($this->totp->verifyCode($secret, 'abcdef'), 'Non-numeric code should fail');
    }

    public function testVerifyCodeReturnsBool(): void
    {
        $secret = $this->totp->generateSecret();
        $result = $this->totp->verifyCode($secret, '000000');

        $this->assertIsBool($result, 'verifyCode should return a boolean');
    }

    // ---------------------------------------------------------------
    //  Backup Codes
    // ---------------------------------------------------------------

    public function testBackupCodeGenerationReturns8Codes(): void
    {
        $codes = $this->totp->generateBackupCodes();

        $this->assertCount(8, $codes, 'Should generate exactly 8 backup codes');
    }

    public function testBackupCodesAre8CharsEach(): void
    {
        $codes = $this->totp->generateBackupCodes();

        foreach ($codes as $i => $code) {
            $this->assertEquals(
                8,
                strlen($code),
                "Backup code #{$i} should be exactly 8 characters, got '{$code}'"
            );
        }
    }

    public function testBackupCodesAreAlphanumeric(): void
    {
        $codes = $this->totp->generateBackupCodes();

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression(
                '/^[0-9a-z]{8}$/',
                $code,
                "Backup code should be lowercase alphanumeric, got '{$code}'"
            );
        }
    }

    public function testBackupCodesCustomCount(): void
    {
        $codes = $this->totp->generateBackupCodes(5);
        $this->assertCount(5, $codes, 'Should generate the requested number of backup codes');
    }

    public function testBackupCodesAreUnique(): void
    {
        $codes = $this->totp->generateBackupCodes();
        $unique = array_unique($codes);

        $this->assertCount(count($codes), $unique, 'All backup codes should be unique');
    }
}
