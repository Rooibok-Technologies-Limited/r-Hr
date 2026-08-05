<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Models;

use CodeIgniter\Model;

class IdCardModel extends Model
{
    protected $table      = 'ci_employee_id_cards';
    protected $primaryKey = 'card_id';

    protected $allowedFields = [
        'company_id', 'user_id', 'card_number', 'verify_token', 'status', 'orientation',
        'issued_at', 'expiry_date', 'revoked_at', 'revoked_by',
        'last_generated_at', 'created_at', 'updated_at',
    ];

    protected $useTimestamps = false;

    /** Card for an employee within a tenant (or null). */
    public function forEmployee(int $companyId, int $userId): ?array
    {
        return $this->where('company_id', $companyId)->where('user_id', $userId)->first();
    }

    /** Public lookup by the non-guessable verification token. */
    public function byToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        return $this->where('verify_token', $token)->first();
    }
}
