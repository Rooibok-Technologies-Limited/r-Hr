<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JwtAuth as JwtLibrary;
use RuntimeException;

class JwtAuth implements FilterInterface
{
    /**
     * Extract and validate the Bearer token from the Authorization header.
     * On success, store decoded payload data on the request for downstream use.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorizedResponse('Authorization token required');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Authorization token required');
        }

        $token = $matches[1];

        try {
            $jwt     = new JwtLibrary();
            $payload = $jwt->decode($token);
        } catch (RuntimeException $e) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        // Store decoded JWT data so controllers can access it
        $request->jwt = (object) [
            'user_id'    => $payload['sub']        ?? null,
            'company_id' => $payload['company_id'] ?? null,
            'user_type'  => $payload['user_type']  ?? null,
            'payload'    => $payload,
        ];

        return null;
    }

    /**
     * Nothing to do after the response.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }

    /**
     * Build a 401 JSON response.
     */
    private function unauthorizedResponse(string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON(['error' => $message])
            ->setContentType('application/json');
    }
}
