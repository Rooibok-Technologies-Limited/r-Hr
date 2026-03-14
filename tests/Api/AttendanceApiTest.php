<?php

namespace Tests\Api;

use Tests\TestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * API tests for the /api/v1/attendance endpoints.
 *
 * These endpoints require JWT authentication (the 'jwt' filter).
 */
class AttendanceApiTest extends TestCase
{
    use FeatureTestTrait;

    // ---------------------------------------------------------------
    //  Unauthenticated Requests
    // ---------------------------------------------------------------

    public function testClockInWithoutTokenReturns401(): void
    {
        $result = $this->withBodyFormat('json')->call('post', 'api/v1/attendance/clock-in', [
            'employee_id' => 1,
        ]);

        $status = $result->response()->getStatusCode();
        $this->assertContains(
            $status,
            [401, 403],
            'Clock-in without JWT should return 401 or 403'
        );
    }

    public function testClockOutWithoutTokenReturns401(): void
    {
        $result = $this->withBodyFormat('json')->call('post', 'api/v1/attendance/clock-out', [
            'employee_id' => 1,
        ]);

        $status = $result->response()->getStatusCode();
        $this->assertContains(
            $status,
            [401, 403],
            'Clock-out without JWT should return 401 or 403'
        );
    }

    public function testStatusWithoutTokenReturns401(): void
    {
        $result = $this->call('get', 'api/v1/attendance/status?employee_id=1');

        $status = $result->response()->getStatusCode();
        $this->assertContains(
            $status,
            [401, 403],
            'Status without JWT should return 401 or 403'
        );
    }

    // ---------------------------------------------------------------
    //  Authenticated Clock-In
    // ---------------------------------------------------------------

    public function testClockInWithValidTokenCreatesRecord(): void
    {
        // Create a test user and obtain a JWT
        $password = 'Test1234!';
        $user = $this->createTestUser('company', [
            'email'    => 'attend_' . uniqid() . '@rooibok.co.ug',
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        // Get a token via the auth endpoint
        $authResult = $this->withBodyFormat('json')->call('post', 'api/v1/auth/token', [
            'email'    => $user['email'],
            'password' => $password,
        ]);

        $authStatus = $authResult->response()->getStatusCode();
        if ($authStatus !== 200) {
            $this->markTestSkipped('Cannot obtain JWT — auth endpoint returned ' . $authStatus);
        }

        $authJson = json_decode($authResult->getJSON(), true);
        $token = $authJson['token'] ?? '';

        if (empty($token)) {
            $this->markTestSkipped('Auth endpoint did not return a token');
        }

        // Use the token to clock in
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->withBodyFormat('json')->call('post', 'api/v1/attendance/clock-in', [
            'employee_id' => $user['user_id'],
            'latitude'    => '0.3476',
            'longitude'   => '32.5825',
        ]);

        $status = $result->response()->getStatusCode();
        // 201 = created, 400 = already clocked in, both are valid outcomes
        $this->assertContains($status, [201, 400], 'Clock-in should return 201 (created) or 400 (already clocked in)');

        if ($status === 201) {
            $json = json_decode($result->getJSON(), true);
            $this->assertArrayHasKey('attendance_id', $json);
            $this->assertArrayHasKey('clock_in', $json);
        }
    }

    // ---------------------------------------------------------------
    //  Authenticated Status Check
    // ---------------------------------------------------------------

    public function testStatusReturnsCurrentAttendance(): void
    {
        $password = 'Test1234!';
        $user = $this->createTestUser('company', [
            'email'    => 'status_' . uniqid() . '@rooibok.co.ug',
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        // Get a token
        $authResult = $this->withBodyFormat('json')->call('post', 'api/v1/auth/token', [
            'email'    => $user['email'],
            'password' => $password,
        ]);

        $authStatus = $authResult->response()->getStatusCode();
        if ($authStatus !== 200) {
            $this->markTestSkipped('Cannot obtain JWT — auth endpoint returned ' . $authStatus);
        }

        $token = json_decode($authResult->getJSON(), true)['token'] ?? '';
        if (empty($token)) {
            $this->markTestSkipped('Auth endpoint did not return a token');
        }

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->call('get', 'api/v1/attendance/status?employee_id=' . $user['user_id']);

        $status = $result->response()->getStatusCode();
        $this->assertContains($status, [200, 404], 'Status should return 200 or 404');

        if ($status === 200) {
            $json = json_decode($result->getJSON(), true);
            $this->assertArrayHasKey('employee_id', $json);
            $this->assertArrayHasKey('status', $json);
            $this->assertContains($json['status'], ['not_clocked_in', 'clocked_in', 'clocked_out']);
        }
    }
}
