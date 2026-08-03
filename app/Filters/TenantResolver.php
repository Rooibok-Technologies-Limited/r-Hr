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
 * TenantResolver (ADR-003, Phase 1) — resolves the tenant context from the Host
 * header and populates service('tenant'). Reserved sub-hosts: admin, api, www.
 * A `{slug}.PLATFORM_HOST` or a matching `custom_domain` resolves to a company;
 * anything else (plain platform host, unknown host) stays 'landing' so nothing
 * breaks. Enforcement (pinning requests to the resolved company) is Phase 2.
 *
 * PLATFORM_HOST comes from env (default 'localhost'); the port is ignored.
 */
class TenantResolver implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('main');
        $tenant = service('tenant');

        // Host without port, lowercased.
        $host = strtolower((string) $request->getServer('HTTP_HOST') ?: '');
        $host = preg_replace('/:\d+$/', '', $host);
        $platform = strtolower((string) (getenv('PLATFORM_HOST') ?: 'localhost'));

        if ($host === '' || $host === $platform || $host === 'www.' . $platform || $host === '127.0.0.1') {
            $tenant->set('landing', $host);
            return;
        }

        // Reserved sub-hosts.
        foreach (['admin' => 'admin', 'api' => 'api'] as $label => $ctx) {
            if ($host === $label . '.' . $platform) {
                $tenant->set($ctx, $host);
                return;
            }
        }

        // Subdomain of the platform host → tenant slug.
        $slug = null;
        if (str_ends_with($host, '.' . $platform)) {
            $slug = substr($host, 0, -strlen('.' . $platform));
            if ($slug === 'www') { $tenant->set('landing', $host); return; }
        }

        $db  = \Config\Database::connect();
        $row = null;
        if ($slug !== null && $slug !== '') {
            $row = $db->table('ci_erp_users')->select('user_id, company_name, company_slug')
                ->where('user_type', 'company')->where('company_slug', $slug)->get()->getRowArray();
        }
        if (! $row) {
            // Maybe a verified custom domain.
            $row = $db->table('ci_erp_users')->select('user_id, company_name, company_slug')
                ->where('user_type', 'company')->where('custom_domain', $host)
                ->where('custom_domain_verified', 1)->get()->getRowArray();
        }

        if ($row) {
            $tenant->set('tenant', $host, $row['company_slug'] ?? $slug, (int) $row['user_id'], $row['company_name'] ?? null);
            return;
        }

        // Unknown host — leave as landing (non-breaking); Phase 2 may 404 tenant hosts.
        $tenant->set('landing', $host);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
