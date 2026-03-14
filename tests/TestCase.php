<?php
namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

class TestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $seed = '';

    protected function createTestUser(string $type = 'company', array $overrides = []): array
    {
        $db = \Config\Database::connect();
        $data = array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test_' . uniqid() . '@rooibok.co.ug',
            'username' => 'testuser_' . uniqid(),
            'password' => password_hash('Test1234!', PASSWORD_BCRYPT),
            'user_type' => $type,
            'company_id' => 1,
            'is_active' => 1,
            'user_role_id' => 1,
        ], $overrides);
        $db->table('ci_erp_users')->insert($data);
        $data['user_id'] = $db->insertID();
        return $data;
    }
}
