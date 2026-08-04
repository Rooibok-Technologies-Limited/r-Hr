<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UsersModel;

/**
 * TenantGuard (ADR-003, Phase 2) — enforces the resolved tenant boundary.
 *
 * Runs globally, right after TenantResolver, and acts only on tenant/admin
 * contexts (landing + api are no-ops; api uses JwtAuth). It must be global —
 * URI-pattern filters are matched against the ORIGINAL path, but the resolver
 * rewrites tenant paths to `erp/*` afterwards, so a pattern would miss clean
 * tenant URLs. Deciding on context() side-steps that entirely.
 *
 * Rules (owner decisions 2026-08-03):
 *  - Tenant host/path + logged-in user whose effective company_id ≠ the resolved
 *    company_id → cross-tenant: flash + redirect to login (session kept), audited.
 *  - super_user on a tenant host → redirect to the admin host (they belong there).
 *  - admin host + logged-in non-super_user → redirect to landing.
 *  - No session → no-op (CheckLogin/SuperAuth own the unauthenticated path).
 *
 * A user's effective company_id is their own user_id when user_type = 'company'
 * (owners carry company_id = 0 and ARE the company), else their company_id.
 */
class TenantGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tenant = service('tenant');
        if ($tenant->isLanding() || $tenant->isApi()) {
            return;
        }

        $session  = \Config\Services::session();
        $usession = $session->get('sup_username');
        if (! is_array($usession) || empty($usession['sup_user_id'])) {
            return; // unauthenticated — auth filters handle it
        }

        $user = (new UsersModel())->where('user_id', $usession['sup_user_id'])->first();
        if (empty($user)) {
            return;
        }
        $isSuper = ($user['user_type'] ?? '') === 'super_user';

        if ($tenant->isAdmin()) {
            if (! $isSuper) {
                return redirect()->to($this->platformUrl($request));
            }
            return;
        }

        // Tenant context.
        if ($isSuper) {
            return redirect()->to($this->adminUrl($request));
        }

        $effective = ($user['user_type'] ?? '') === 'company'
            ? (int) $user['user_id']
            : (int) ($user['company_id'] ?? 0);

        if ($effective !== (int) $tenant->companyId()) {
            try {
                service('audit')->record('tenant.cross_access_denied', [
                    'entity_type' => 'company',
                    'entity_id'   => (int) $tenant->companyId(),
                    'summary'     => 'Blocked cross-tenant access by user ' . (int) $user['user_id']
                        . ' (company ' . $effective . ') on tenant ' . (int) $tenant->companyId(),
                ]);
            } catch (\Throwable $e) {
                // auditing must never block the guard
            }
            $session->setFlashdata('err_not_logged_in', 'You are not authorized on this workspace.');
            return redirect()->to(site_url('erp/login'));
        }
    }

    /** Absolute URL of the platform landing host (scheme + PLATFORM_HOST + port). */
    private function platformUrl(RequestInterface $request): string
    {
        return $this->hostUrl($request, (string) (getenv('PLATFORM_HOST') ?: 'localhost'));
    }

    /** Absolute URL of the super-admin host (admin.PLATFORM_HOST). */
    private function adminUrl(RequestInterface $request): string
    {
        return $this->hostUrl($request, 'admin.' . (string) (getenv('PLATFORM_HOST') ?: 'localhost'));
    }

    /** Build scheme://<host>[:port]/ preserving the current request's port. */
    private function hostUrl(RequestInterface $request, string $bareHost): string
    {
        $scheme  = $request->isSecure() ? 'https' : 'http';
        $rawHost = (string) $request->getServer('HTTP_HOST');
        $port    = '';
        if (preg_match('/:(\d+)$/', $rawHost, $m)) {
            $port = ':' . $m[1];
        }
        return $scheme . '://' . $bareHost . $port . '/';
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
