<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Router\Exceptions\RedirectException;

/**
 * TenantBootstrap (ADR-003, Phase 2) — resolves the tenant context and presents
 * the clean, erp-less URL surface. Invoked from the `pre_system` event so it can
 * rewrite the routed path BEFORE the router resolves it (a before-filter is too
 * late — CI4 routes the request before before-filters run).
 *
 * Responsibilities:
 *  - Resolve context from Host (subdomain/custom domain) or path fallback
 *    (`localhost/{slug}/…`) and populate service('tenant').
 *  - Align baseURL + cookie domain to the request host (per-host isolation).
 *  - For a resolved tenant, transparently prepend `erp/` so clean URLs
 *    (`{slug}.host/staff-list`) reach the existing `erp/*` controllers; root maps
 *    to the dashboard. Legacy `/erp/*` still serves; a canonical 301 to the clean
 *    form is opt-in via TENANT_CANONICAL_REDIRECT=true.
 *
 * Enforcement (pinning a user to the resolved company) lives in TenantGuard.
 */
class TenantBootstrap
{
    /** First URI segments that are never a tenant slug and never get `erp/` prepended. */
    private const RESERVED = ['erp', 'api', 'admin', 'assets', 'uploads', 'public', 'index.php', 'favicon.ico', 'robots.txt'];

    public static function boot($request = null): void
    {
        $request = $request ?? service('request');
        if (! $request instanceof IncomingRequest) {
            return; // CLI / spark — nothing to resolve
        }

        helper('main');
        $tenant = service('tenant');

        // CI4 4.1.3's Router never trims a trailing slash, so "/erp/" matches no
        // route ("erp") and 404s. Normalize once here, before any resolution.
        $p = $request->getPath();
        if ($p !== '' && $p !== '/' && str_ends_with($p, '/')) {
            $request->setPath(rtrim($p, '/'));
        }

        $rawHost  = strtolower((string) $request->getServer('HTTP_HOST') ?: '');
        $host     = preg_replace('/:\d+$/', '', $rawHost);
        $platform = strtolower((string) (getenv('PLATFORM_HOST') ?: 'localhost'));

        (new self())->resolve($request, $tenant, $rawHost, $host, $platform);
    }

    private function resolve($request, $tenant, string $rawHost, string $host, string $platform): void
    {
        // Align baseURL to the request host so links/emails/assets use it.
        $this->applyBaseUrl($request, $rawHost);

        // Landing / platform root — try a path slug before falling back to landing.
        if ($host === '' || $host === $platform || $host === 'www.' . $platform || $host === '127.0.0.1') {
            if ($this->resolveByPath($request, $tenant)) {
                return;
            }
            $tenant->set('landing', $host);
            $tenant->setSource('none');
            return;
        }

        // Reserved sub-hosts (own route trees / SuperAuth in TenantGuard) — no rewrite.
        foreach (['admin' => 'admin', 'api' => 'api'] as $label => $ctx) {
            if ($host === $label . '.' . $platform) {
                $tenant->set($ctx, $host);
                $tenant->setSource('host');
                return;
            }
        }

        // Subdomain of the platform host → tenant slug.
        $slug = null;
        if (str_ends_with($host, '.' . $platform)) {
            $slug = substr($host, 0, -strlen('.' . $platform));
            if ($slug === 'www') { $tenant->set('landing', $host); $tenant->setSource('none'); return; }
        }

        $row = $this->lookupCompany($slug, $host);
        if ($row) {
            $tenant->set('tenant', $host, $row['company_slug'] ?? $slug, (int) $row['user_id'], $row['company_name'] ?? null);
            $tenant->setSource('host');
            $this->cookieDomain($host);
            $this->rewriteToErp($request, null);
            return;
        }

        // Unknown host — non-breaking landing.
        $tenant->set('landing', $host);
        $tenant->setSource('none');
    }

    /**
     * Path-fallback: on the platform host, if segment 1 is a known, non-reserved
     * slug, resolve that tenant and strip the segment. Returns true if resolved.
     */
    private function resolveByPath($request, $tenant): bool
    {
        $path = trim($request->getPath(), '/');
        if ($path === '') {
            return false;
        }
        $first = explode('/', $path)[0];
        if (in_array($first, self::RESERVED, true)) {
            return false;
        }
        $row = $this->lookupCompany($first, null);
        if (! $row) {
            return false;
        }
        $tenant->set('tenant', strtolower((string) $request->getServer('HTTP_HOST')), $row['company_slug'] ?? $first, (int) $row['user_id'], $row['company_name'] ?? null);
        $tenant->setSource('path');
        // Path fallback shares the platform cookie — isolation via TenantGuard.
        $this->rewriteToErp($request, $first);
        return true;
    }

    /** Look up a company by slug, then by verified custom domain. */
    private function lookupCompany(?string $slug, ?string $host): ?array
    {
        $db = \Config\Database::connect();
        if ($slug !== null && $slug !== '') {
            $row = $db->table('ci_erp_users')->select('user_id, company_name, company_slug')
                ->where('user_type', 'company')->where('company_slug', $slug)->get()->getRowArray();
            if ($row) {
                return $row;
            }
        }
        if ($host !== null && $host !== '') {
            $row = $db->table('ci_erp_users')->select('user_id, company_name, company_slug')
                ->where('user_type', 'company')->where('custom_domain', $host)
                ->where('custom_domain_verified', 1)->get()->getRowArray();
            if ($row) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Present the clean tenant URL: strip an optional leading slug segment, then
     * prepend `erp/` unless the path is already `erp/*` or a reserved passthru.
     * Root maps to the tenant dashboard. May throw RedirectException (opt-in 301).
     *
     * @param string|null $slugToStrip Path-fallback slug to remove from the front.
     */
    private function rewriteToErp($request, ?string $slugToStrip): void
    {
        $path = trim($request->getPath(), '/');

        if ($slugToStrip !== null) {
            $path = ($path === $slugToStrip) ? '' : substr($path, strlen($slugToStrip) + 1);
            $path = trim($path, '/');
        }

        if ($path === '') {
            $request->setPath('erp/desk');
            return;
        }

        $first = explode('/', $path)[0];

        if ($first === 'erp') {
            // Legacy form — serve as-is; optional canonical 301 (GET, non-AJAX).
            if (getenv('TENANT_CANONICAL_REDIRECT') === 'true'
                && strtoupper($request->getMethod()) === 'GET' && ! $request->isAJAX()) {
                $clean = ltrim(preg_replace('#^erp/?#', '', $path), '/');
                throw new RedirectException($clean, 301);
            }
            $request->setPath($path);
            return;
        }

        if (in_array($first, self::RESERVED, true)) {
            $request->setPath($path);
            return; // api/assets/uploads — no erp/ prefix.
        }

        $request->setPath('erp/' . $path);
    }

    /** Set baseURL from the request host so generated URLs use the right host. */
    private function applyBaseUrl($request, string $rawHost): void
    {
        if ($rawHost === '') {
            return;
        }
        $scheme = $request->isSecure() ? 'https' : 'http';
        $app = config('App');
        $app->baseURL = $scheme . '://' . $rawHost . '/';
        // Re-sync the request URI host/scheme with the new baseURL.
        $request->setPath($request->getPath(), $app);
    }

    /** Bind the session cookie to this exact host (never a shared parent domain). */
    private function cookieDomain(string $host): void
    {
        $app = config('App');
        if (property_exists($app, 'cookieDomain')) {
            $app->cookieDomain = $host;
        }
    }
}
