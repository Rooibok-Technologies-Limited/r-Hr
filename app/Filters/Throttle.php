<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Throttle implements FilterInterface
{
    /**
     * Rate-limit incoming requests by IP address.
     *
     * Filter arguments (from route config):
     *   'throttle:60,1' => 60 requests per 1 minute
     *
     * @param RequestInterface|\CodeIgniter\HTTP\IncomingRequest $request
     * @param array|null                                         $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = Services::throttler();

        // Default: 60 requests per minute
        $maxRequests = 60;
        $minutes     = 1;

        if (!empty($arguments[0])) {
            $maxRequests = (int) $arguments[0];
        }
        if (!empty($arguments[1])) {
            $minutes = (int) $arguments[1];
        }

        // Throttle by IP address
        $key = 'api_throttle_' . $request->getIPAddress();

        if ($throttler->check($key, $maxRequests, $minutes * MINUTE) === false) {
            return Services::response()
                ->setStatusCode(429)
                ->setContentType('application/json')
                ->setJSON(['error' => 'Too many requests. Please try again later.']);
        }
    }

    /**
     * Nothing to do after the response.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
