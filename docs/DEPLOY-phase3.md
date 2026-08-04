<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# ADR-003 Phase 3 — Traefik production routing (runbook)

Puts the CI4 monolith behind the shared Traefik edge on the VPS, with one
DNS-01 **wildcard** cert for tenant subdomains. **Nothing here is applied yet —
this is the deploy procedure.** Prod domain is **`rooibok.tech`** (VPS
`195.110.59.36`). `admin`/`api`/`www`/apex A-records already point at the VPS;
only the wildcard is missing.

Order is fixed: **Traefik resolver → DNS → env/secrets → app TLS config → deploy →
verify.** Do not skip the resolver step — the wildcard cert cannot issue without it.

---

## 0. Prerequisites (shared Traefik, one-time)

The shared Traefik (`/opt/infra/traefik`) must expose two ACME cert resolvers:

- **`le`** — HTTP-01 (already used by other projects; serves custom domains here).
- **`le-dns`** — **new**, DNS-01 via Hostinger, required for the `*.rooibok.tech`
  wildcard (Let's Encrypt never issues wildcards over HTTP-01).

Add to Traefik's **static** config (`traefik.yml`) and restart Traefik:

```yaml
certificatesResolvers:
  le-dns:
    acme:
      email: rooiboktechltd@gmail.com
      storage: /letsencrypt/acme-dns.json
      dnsChallenge:
        provider: hostinger          # lego Hostinger provider
        resolvers:
          - "1.1.1.1:53"
          - "8.8.8.8:53"
```

Pass the Hostinger API token to the Traefik container (env or secret):

```yaml
# docker-compose for the shared Traefik
    environment:
      - HOSTINGER_API_TOKEN=${HOSTINGER_API_TOKEN}   # DNS-edit scope
```

> The token only needs DNS-record write on the `rooibok.tech` zone. Keep it in
> the Traefik host's `.env`, never in this repo. If your Traefik/lego build
> predates the Hostinger provider, either upgrade Traefik or fall back to
> per-tenant HTTP-01 (drop the wildcard router; each tenant subdomain then needs
> its own cert + a resolvable A record before first hit).

The external edge network already exists (owned by Traefik):

```bash
docker network inspect web >/dev/null 2>&1 || docker network create web
```

---

## 1. DNS (Hostinger zone `rooibok.tech`)

Add ONE record. **Do not touch** the Brevo DKIM/DMARC/TXT records (email breaks).

| name | type | value | ttl |
|---|---|---|---|
| `*` | A | `195.110.59.36` | 300 |

`@`, `www`, `admin`, `api` already point at the VPS — leave them. The wildcard
makes every `{slug}.rooibok.tech` resolve; DNS-01 validates against this zone.

---

## 2. Secrets + prod env (VPS `/opt/rooibok/.env`)

```dotenv
# Tenancy (ADR-003)
PLATFORM_HOST=rooibok.tech
TENANT_URL_MODE=subdomain
TENANT_CANONICAL_REDIRECT=true          # 301 legacy /erp/* -> clean, now that hosts are live

# Admin Basic-Auth gate (Traefik middleware). Value = "user:APR1HASH".
# Generate on the VPS (htpasswd -nb, or openssl). Do NOT escape $ here — this is
# a .env value read by compose interpolation, not an inline compose label.
ADMIN_BASICAUTH_USERS=bodo:$apr1$....hash....
```

Generate the hash:

```bash
htpasswd -nbB bodo 'STRONG_PASS'        # bcrypt; or:
printf 'bodo:%s\n' "$(openssl passwd -apr1 'STRONG_PASS')"
```

---

## 3. App TLS/cookie hardening (prod `.env`, CI4 env-mapped)

```dotenv
app.forceGlobalSecureRequests=true      # emit https:// + upgrade
app.cookieSecure=true                   # session cookie only over TLS
app.CSPEnabled=false                    # (leave as-is unless already tuned)
```

Per-host `baseURL` and cookie **domain** are set at runtime by
`App\Libraries\TenantBootstrap` from the request host — no per-tenant config
needed. `forceGlobalSecureRequests` makes the framework's own URL/cookie layer
treat every host as https.

---

## 4. Deploy

```bash
cd /opt/rooibok
git fetch origin && git checkout -B main origin/main   # confirm branch+tracking
git pull
git log --oneline -1                                   # verify the new commit landed
docker compose -f compose.yml -f docker-compose.prod.yml up -d --build
```

`docker compose config` (prod render) starts only: app, nginx, postgres,
postgres_archive, redis, beanstalkd. `pgadmin`/`mailhog` are `profiles: [dev]`
and stay down. nginx publishes **no** host ports; Traefik routes it on `web`.

> **nginx.conf change later?** A single-file bind-mount goes stale on `git pull`
> (atomic rename → new inode; the container keeps the old file). `reload`/
> `restart` won't help — `up -d --force-recreate nginx`.

---

## 5. Verify (per host)

```bash
for H in rooibok.tech www.rooibok.tech api.rooibok.tech acme-corp.rooibok.tech; do
  printf '%-28s ' "$H"; curl -sS -o /dev/null -w '%{http_code}\n' "https://$H/"
done
# admin is gated — expect 401 without creds, 200/302 with:
curl -sS -o /dev/null -w '%{http_code}\n'            https://admin.rooibok.tech/   # 401
curl -sS -o /dev/null -w '%{http_code}\n' -u bodo:PW https://admin.rooibok.tech/   # through
```

- First HTTPS hit to a new host triggers ACME; the wildcard (DNS-01) may take
  ~1–2 min on first issue. Retry after ~15s on a TLS handshake error.
- A tenant subdomain landing on the login page = success (root → `erp/desk` →
  `erp/login`, all on the tenant host).
- Cross-tenant + `super_user`/`admin` redirects are enforced by `TenantGuard`
  (ADR-003 Phase 2) — no infra action needed.

---

## 6. Custom domains (per verified tenant)

Wildcard covers only `*.rooibok.tech`. A tenant's own domain (`hr.acme.com`)
needs: (a) tenant sets `custom_domain` + a CNAME/A to the VPS, (b) you verify it,
(c) a router pair using the **`le`** HTTP-01 resolver. Uncomment + copy the
`cd-<slug>` template in `docker-compose.prod.yml`, or move custom-domain routers
to a Traefik **file provider** (`/opt/infra/traefik/dynamic/rooibok-cd.yml`) so
new domains don't require a project redeploy. Automating (b)+(c) is a later task.

---

## Rollback

```bash
cd /opt/rooibok
docker compose -f compose.yml -f docker-compose.prod.yml down
git checkout <previous-good-sha>
docker compose -f compose.yml -f docker-compose.prod.yml up -d --build
```

DNS: removing the `*` A-record stops new tenant hosts resolving; existing
apex/admin/api are unaffected. The wildcard cert in Traefik storage is harmless
if left.
