<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * API tests for the /api/v1/health endpoint.
 */
class HealthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHealthReturns200WithStatusOk(): void
    {
        $result = $this->call('get', 'api/v1/health');

        $result->assertStatus(200);

        $json = json_decode($result->getJSON(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('status', $json);
        $this->assertEquals('ok', $json['status']);
    }

    public function testHealthResponseContainsVersion(): void
    {
        $result = $this->call('get', 'api/v1/health');

        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('version', $json);
    }

    public function testHealthResponseContainsTimestamp(): void
    {
        $result = $this->call('get', 'api/v1/health');

        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('timestamp', $json);
    }
}
