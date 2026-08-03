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

## TenantResolver (how it works — minimal rewrite)
A filter reads the `Host` header and sets request context:
`admin.*` → super-admin · root/`www` → landing · `api.*` → API · else → look up
company by **subdomain slug** or **`custom_domain`** in `ci_erp_company_settings`,
set active `company_id`, 404 on unknown, and **enforce** the logged-in user
belongs to that company. Reuses all existing `company_id` scoping — this is a
routing/filter change, not a data migration.

## Data model
- `ci_erp_users` (or company settings): `company_slug` (unique), `custom_domain`
  (unique, nullable), `custom_domain_verified` (bool).
- One PostgreSQL, shared schema, `company_id` partition — unchanged.

## Phased rollout (each non-breaking)
1. `company_slug` + `custom_domain` columns (backfill slugs); `TenantResolver`
   filter (host optional → falls back to today's `/erp` behavior).
2. `admin.` → SuperAuth; tenant hosts gated to their `company_id`; slug auto-gen
   wired into registration (`Home::register_company`).
3. Per-host `base_url` + **cookie domain** (key gotcha) + email/asset link hosts.
4. Traefik: wildcard cert, `admin`/`api`/tenant routers, custom-domain router +
   verification, Basic-Auth middleware on `admin`.
5. (Later) split `api.` into a dedicated backend service.

## Gotchas
- **Cookie domain per host** (never a shared `.localhost`) — else isolation leaks.
- Absolute asset/`base_url` and email links must use the **request host**.
- CSRF/session config per host; `*.localhost` auto-resolves to 127.0.0.1 on
  Linux/Chrome (no hosts edit) — Windows may need dnsmasq/hosts.
- Reserved slugs: `admin`, `api`, `www`, `app`, `mail`, etc.

## Consequences
Best-fit for white-label + custom domains + the Traefik deploy; the whole change
is incremental and reuses the existing multi-tenant scoping. Start at Phase 1.
