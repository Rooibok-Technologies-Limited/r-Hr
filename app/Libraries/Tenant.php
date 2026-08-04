<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

/**
 * Tenant context for host-based multi-tenancy (ADR-003). Populated once per
 * request by the TenantResolver filter from the Host header, then read anywhere
 * via service('tenant'). Phase 1 only RESOLVES + exposes context; enforcement
 * (pinning a request to its company) comes in Phase 2.
 *
 * context ∈ { landing, admin, api, tenant }
 */
class Tenant
{
    private string $context   = 'landing';
    private string $host      = '';
    private ?string $slug     = null;
    private ?int $companyId   = null;
    private ?string $companyName = null;
    /** How the tenant was resolved: 'host' (subdomain/custom domain) | 'path' | 'none'. */
    private string $source    = 'none';

    public function set(string $context, string $host, ?string $slug = null, ?int $companyId = null, ?string $companyName = null): void
    {
        $this->context     = $context;
        $this->host        = $host;
        $this->slug        = $slug;
        $this->companyId   = $companyId;
        $this->companyName = $companyName;
    }

    public function setSource(string $source): void { $this->source = $source; }

    public function context(): string { return $this->context; }
    public function host(): string { return $this->host; }
    public function slug(): ?string { return $this->slug; }
    public function companyId(): ?int { return $this->companyId; }
    public function companyName(): ?string { return $this->companyName; }
    public function source(): string { return $this->source; }

    public function isLanding(): bool { return $this->context === 'landing'; }
    public function isAdmin(): bool { return $this->context === 'admin'; }
    public function isApi(): bool { return $this->context === 'api'; }
    public function isTenant(): bool { return $this->context === 'tenant'; }
}
