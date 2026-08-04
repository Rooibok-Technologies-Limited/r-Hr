<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# ADR-003 — Host-based multi-tenant routing

Status: **Accepted** (2026-08-03) · Supersedes the path-based idea and the
subdomain notes in the retired PLAN.md.

## Context
Rooibok HR is a white-label SaaS (one CI4 app, one PostgreSQL, per-tenant
branding already shipped — see [[hr-white-label-branding]]). We need a routing
model for: public landing, super-admin console, isolated per-company workspaces,
and a separated backend/API — aligned with the shared-Traefik deployment
(one host per concern, wildcard TLS).

## Decision
**Hybrid tenancy — host-primary with path fallback** (owner decision 2026-08-03),
resolved by a `TenantResolver` filter on a single CI4 app + single Postgres
(shared schema, `company_id` partition). Resolution order:
1. **Host** — `{slug}.PLATFORM_HOST` or a verified `custom_domain` (preferred;
   gives white-label URL + isolated cookie).
2. **Path fallback** — a leading `/{slug}/…` segment on the platform host
   (`localhost/acme-corp/…`), for local dev without subdomain setup.

Both resolve to the same `company_id` and controllers. Because the path form
shares the platform cookie, **Phase-2 enforcement pins every request to the
resolved company_id and rejects cross-tenant** — security does not rely on the
URL form. Generated links/emails use ONE **canonical** form (env-driven:
subdomain in prod, path in dev); legacy `/erp/*` redirects to canonical.

| Host (dev → prod) | Serves | Auth |
|---|---|---|
| `localhost:12000` / `www.` → `rooibok.co.ug` | Landing (public) | none |
| `admin.localhost:12000` → `admin.rooibok.co.ug` | Super-admin | **Traefik Basic-Auth gate** + `SuperAuth` |
| `{slug}.localhost:12000` → `{slug}.rooibok.co.ug` **+ custom `hr.acme.com`** | Tenant workspace | `CompanyAuth`, pinned to resolved `company_id` |
| `api.localhost:12000` → `api.rooibok.co.ug` | Backend / API | `JwtAuth` |

### Confirmed options (owner decisions, 2026-08-03)
1. **Slug — auto.** Generated from the company name at registration
   (`Acme Corp` → `acme-corp`, uniqueness-suffixed), admin-editable later.
2. **Custom domains — in scope now.** `custom_domain` per tenant; verified +
   wildcard/SNI TLS via Traefik; CNAME to the platform.
3. **API subdomain — now.** `api.` routes to the existing CI4 `Api/V1` today;
   a later split to a dedicated FastAPI backend service stays possible (the
   resolver + JWT boundary make it a drop-in).
4. **Admin gate — yes.** Traefik HTTP Basic-Auth in front of `admin.` in
   addition to app-level `SuperAuth`.

## Alternatives (rejected)
- **Path-based** (`/companyname/…`) — simpler local dev, but one shared cookie
  for all tenants+admin (weak isolation, every request must guard slug==company),
  and the URL never shows the tenant's brand (fights white-label) and complicates
  custom domains.
- **DB-per-tenant / schema-per-tenant** — strong isolation, heavy ops; over-
  engineering at this scale and fights the pooled-wallet aggregator model. The
  resolver keeps a future move possible without committing now.

## TenantResolver (how it works)
`App\Libraries\TenantBootstrap::boot()` reads the `Host` header and sets request
context into `service('tenant')`: `admin.*` → super-admin · root/`www` → landing ·
`api.*` → API · else → look up company by **subdomain slug** or verified
**`custom_domain`** in `ci_erp_users`, set active `company_id`. It also resolves
the **path fallback** (`HOST/{slug}/…`) on the platform host. Reuses all existing
`company_id` scoping — a routing change, not a data migration.

**Runs at the `pre_system` event, not as a filter.** CI4 4.1.3 resolves the route
BEFORE before-filters execute (`tryToRouteIt` precedes `filters->run('before')`),
so a filter cannot rewrite the routed path. `pre_system` fires after the request
object exists and before routing — the only correct hook for URL rewriting.

### Clean, erp-less tenant URLs (owner decision 2026-08-04)
Tenant URLs hide the `erp/` route prefix: `{slug}.host/staff-list` (not
`…/erp/staff-list`). The bootstrap **transparently prepends `erp/`** to the routed
path for tenant contexts, so the existing `erp/*` controllers are reached
unchanged; root maps to `erp/desk`. Generated links drop the prefix via
`tenant_url()`. Legacy `/erp/*` still serves (non-breaking); a canonical 301 to
the clean form is opt-in (`TENANT_CANONICAL_REDIRECT`). Chosen over a global
de-prefix (rewriting every route + `site_url()` + JS endpoint) to avoid the
audited route/JS drift and its regression risk.

### Enforcement — TenantGuard (global filter)
Runs after routing, acts only on tenant/admin contexts. A user's **effective
company_id** is their own `user_id` when `user_type='company'` (owners carry
`company_id=0` and ARE the company), else their `company_id`. Rules:
- tenant context + effective company ≠ resolved company → cross-tenant: audited
  (`tenant.cross_access_denied`) + redirect to on-host login (session kept);
- `super_user` on a tenant host → redirect to `admin.` host;
- `admin.` host + non-super → redirect to landing.
Must be global (not a `erp/*` URI-pattern filter): pattern matching uses the
ORIGINAL path, which the bootstrap has already rewritten to `erp/*`.

## Data model
- `ci_erp_users` (or company settings): `company_slug` (unique), `custom_domain`
  (unique, nullable), `custom_domain_verified` (bool).
- One PostgreSQL, shared schema, `company_id` partition — unchanged.

## Phased rollout (each non-breaking)
1. ✅ **DONE** (42600c8) — `company_slug` + `custom_domain` columns (backfill
   slugs); host-based context resolution; slug auto-gen in registration.
2. ✅ **DONE** (Phase 2, 2026-08-04) — resolution moved to `pre_system`
   (`TenantBootstrap`); **path fallback** (`HOST/{slug}/…`); **clean erp-less
   tenant URLs**; per-host `base_url` + **cookie domain**; `tenant_url()` helper;
   **TenantGuard** pins every request to its company (cross-tenant blocked +
   audited), `super_user`→`admin.`, non-super off `admin.`. Verified live across
   all host/path forms. (Folds in the old Phase-3 base_url/cookie work.)
3. **CONFIG AUTHORED (2026-08-04), not yet deployed** — Traefik prod overlay for
   the shared edge. Prod apex is **`rooibok.tech`** (VPS `195.110.59.36`); the
   `rooibok.co.ug` used elsewhere in this ADR is an illustrative placeholder.
   `docker-compose.prod.yml` routes the single nginx backend for landing (apex +
   `www`), `admin.` (Traefik Basic-Auth + app SuperAuth), `api.`, and every
   `*.rooibok.tech` tenant (HostRegexp, low priority). TLS = **one DNS-01 wildcard
   cert** (`rooibok.tech` + `*.rooibok.tech`, resolver `le-dns`, **Hostinger**
   provider — HTTP-01 can't issue wildcards); custom domains use per-domain
   HTTP-01 (`le`). Full procedure (shared-Traefik resolver, the single `*` A
   record, prod env, deploy, verify, rollback) in `docs/DEPLOY-phase3.md`. Deploy
   is a deliberate, owner-run step (touches live DNS + shared infra).
4. (Later) split `api.` into a dedicated backend service; automate custom-domain
   verification → Traefik file-provider router.

## Gotchas
- **Cookie domain per host** (never a shared `.localhost`) — else isolation leaks.
- Absolute asset/`base_url` and email links must use the **request host**.
- CSRF/session config per host; `*.localhost` auto-resolves to 127.0.0.1 on
  Linux/Chrome (no hosts edit) — Windows may need dnsmasq/hosts.
- Reserved slugs: `admin`, `api`, `www`, `app`, `mail`, etc.

## Consequences
Best-fit for white-label + custom domains + the Traefik deploy; the whole change
is incremental and reuses the existing multi-tenant scoping. Start at Phase 1.
