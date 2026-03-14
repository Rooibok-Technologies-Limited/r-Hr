<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class DemoMode implements FilterInterface
{
    /**
     * Block POST requests for demo sessions (read-only browsing only).
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = \Config\Services::session();

        if ($session->get('is_demo_session') === true && $request->getMethod() === 'post') {
            return \Config\Services::response()
                ->setStatusCode(403)
                ->setJSON([
                    'error' => 'This is a demo — sign up for a free trial to save changes',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed.
    }
}
