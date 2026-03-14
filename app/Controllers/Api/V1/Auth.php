<?php

namespace App\Controllers\Api\V1;

use App\Libraries\JwtAuth;
use App\Models\UsersModel;

class Auth extends ApiBaseController
{
    /**
     * POST /api/v1/auth/token
     *
     * Accepts JSON body with:
     *   - email    (string, required)
     *   - password (string, required)
     *
     * Returns a JWT on success.
     */
    public function token()
    {
        $json = $this->request->getJSON(true);

        $email    = $json['email']    ?? '';
        $password = $json['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->errorResponse('Email and password are required', 400);
        }

        $usersModel = new UsersModel();
        $user = $usersModel->where('email', $email)->first();

        if (!$user) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        // Verify password (stored as MD5 in legacy system, or password_hash)
        $passwordValid = false;
        if (password_verify($password, $user['password'])) {
            $passwordValid = true;
        } elseif (md5($password) === $user['password']) {
            $passwordValid = true;
        }

        if (!$passwordValid) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        // Check if user is active
        if (isset($user['is_active']) && $user['is_active'] != 1) {
            return $this->errorResponse('Account is inactive', 403);
        }

        try {
            $jwt   = new JwtAuth();
            $token = $jwt->encode([
                'sub'        => (int) $user['user_id'],
                'company_id' => (int) $user['company_id'],
                'user_type'  => $user['user_type'],
            ]);

            return $this->jsonResponse([
                'token'      => $token,
                'expires_in' => $jwt->getTtl(),
                'user'       => [
                    'id'         => (int) $user['user_id'],
                    'company_id' => (int) $user['company_id'],
                    'type'       => $user['user_type'],
                ],
            ]);
        } catch (\RuntimeException $e) {
            return $this->errorResponse('Authentication service unavailable', 500);
        }
    }
}
