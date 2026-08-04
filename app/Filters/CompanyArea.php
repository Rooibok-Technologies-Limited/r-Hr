<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CompanyArea — composes CheckLogin + CompanyAuth into ONE filter alias.
 *
 * CI4 4.1.3's route `filter` option is a single string (Router::getFilter()
 * returns a string), so `'filter' => 'checklogin,companyauth'` was parsed as one
 * bogus alias and every route using it 500'd with "filter must have a matching
 * alias defined". This runs both checks in order, short-circuiting on the first
 * that returns a response.
 */
class CompanyArea implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $r = (new CheckLogin())->before($request, $arguments);
        if ($r) {
            return $r;
        }
        return (new CompanyAuth())->before($request, $arguments);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
