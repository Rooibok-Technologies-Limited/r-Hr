<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class ApiBaseController extends Controller
{
    protected $helpers = ['main'];

    /**
     * Return a JSON response with the given data and HTTP status code.
     */
    protected function jsonResponse($data, int $statusCode = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setJSON($data);
    }

    /**
     * Return a JSON error response.
     */
    protected function errorResponse(string $message, int $statusCode = 400): ResponseInterface
    {
        return $this->jsonResponse(['error' => $message], $statusCode);
    }

    /**
     * Get the authenticated user's ID from the JWT payload.
     */
    protected function jwtUserId(): ?int
    {
        return $this->request->jwt->user_id ?? null;
    }

    /**
     * Get the authenticated user's company ID from the JWT payload.
     */
    protected function jwtCompanyId(): ?int
    {
        return $this->request->jwt->company_id ?? null;
    }

    /**
     * Get the authenticated user's type from the JWT payload.
     */
    protected function jwtUserType(): ?string
    {
        return $this->request->jwt->user_type ?? null;
    }
}
