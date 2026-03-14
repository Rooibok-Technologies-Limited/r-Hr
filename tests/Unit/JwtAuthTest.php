<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\JwtAuth;
use RuntimeException;

/**
 * Unit tests for JwtAuth — JWT encoding, decoding, expiry, and tampering.
 *
 * These tests require system_setting('jwt_secret') to be configured
 * in the test database (ci_system_settings table).
 */
class JwtAuthTest extends CIUnitTestCase
{
    private ?JwtAuth $jwt = null;
    private bool $jwtAvailable = true;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->jwt = new JwtAuth();
        } catch (RuntimeException $e) {
            // JWT secret not configured in test environment
            $this->jwtAvailable = false;
        }
    }

    private function requireJwt(): void
    {
        if (!$this->jwtAvailable) {
            $this->markTestSkipped('JWT secret not configured in test environment (set jwt_secret in ci_system_settings)');
        }
    }

    // ---------------------------------------------------------------
    //  Encode
    // ---------------------------------------------------------------

    public function testEncodeReturnsValidJwtFormat(): void
    {
        $this->requireJwt();

        $token = $this->jwt->encode(['sub' => 1, 'user_type' => 'company']);

        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'JWT should have exactly 3 dot-separated parts (header.payload.signature)');

        // Each part should be non-empty base64url
        foreach ($parts as $i => $part) {
            $this->assertNotEmpty($part, "JWT part {$i} should not be empty");
            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9_-]+$/',
                $part,
                "JWT part {$i} should be valid base64url"
            );
        }
    }

    // ---------------------------------------------------------------
    //  Decode
    // ---------------------------------------------------------------

    public function testDecodeReturnsOriginalPayload(): void
    {
        $this->requireJwt();

        $payload = [
            'sub'        => 42,
            'company_id' => 7,
            'user_type'  => 'staff',
        ];

        $token   = $this->jwt->encode($payload);
        $decoded = $this->jwt->decode($token);

        $this->assertEquals(42, $decoded['sub'], 'Decoded sub should match');
        $this->assertEquals(7, $decoded['company_id'], 'Decoded company_id should match');
        $this->assertEquals('staff', $decoded['user_type'], 'Decoded user_type should match');
        $this->assertArrayHasKey('iat', $decoded, 'Decoded payload should contain iat');
        $this->assertArrayHasKey('exp', $decoded, 'Decoded payload should contain exp');
    }

    // ---------------------------------------------------------------
    //  Expiration
    // ---------------------------------------------------------------

    public function testExpiredTokenThrowsException(): void
    {
        $this->requireJwt();

        // Create a token that expired 10 seconds ago
        $token = $this->jwt->encode([
            'sub' => 1,
            'iat' => time() - 120,
            'exp' => time() - 10,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Token has expired');

        $this->jwt->decode($token);
    }

    // ---------------------------------------------------------------
    //  Tampered Token
    // ---------------------------------------------------------------

    public function testTamperedTokenThrowsException(): void
    {
        $this->requireJwt();

        $token = $this->jwt->encode(['sub' => 1]);
        $parts = explode('.', $token);

        // Tamper with the payload (change a character)
        $parts[1] = $parts[1] . 'x';
        $tampered = implode('.', $parts);

        $this->expectException(RuntimeException::class);

        $this->jwt->decode($tampered);
    }

    // ---------------------------------------------------------------
    //  Invalid Structure
    // ---------------------------------------------------------------

    public function testMissingTokenDetection(): void
    {
        $this->requireJwt();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid token structure');

        $this->jwt->decode('not-a-valid-jwt');
    }

    public function testEmptyTokenThrowsException(): void
    {
        $this->requireJwt();

        $this->expectException(RuntimeException::class);

        $this->jwt->decode('');
    }

    public function testTwoPartTokenThrowsException(): void
    {
        $this->requireJwt();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid token structure');

        $this->jwt->decode('header.payload');
    }

    // ---------------------------------------------------------------
    //  TTL
    // ---------------------------------------------------------------

    public function testGetTtlReturnsPositiveInt(): void
    {
        $this->requireJwt();

        $ttl = $this->jwt->getTtl();
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl, 'TTL should be positive');
    }
}
