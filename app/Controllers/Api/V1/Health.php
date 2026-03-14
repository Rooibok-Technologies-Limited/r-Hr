<?php

namespace App\Controllers\Api\V1;

class Health extends ApiBaseController
{
    /**
     * GET /api/v1/health
     * Public health-check endpoint (no JWT required).
     */
    public function index()
    {
        return $this->jsonResponse([
            'status'    => 'ok',
            'version'   => '1.0',
            'timestamp' => date('c'),
        ]);
    }
}
