<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# Project Context
Last updated: 2026-08-04 (eve)

## Current task
**Public-launch deployment-readiness push (2026-08-04, in progress).** Ran a 6-agent
audit then worked the security backlog to a stopping point. All fixed + pushed to main:
super-admin **privilege escalation** (42 routes→`superauth`), **forgeable id tokens**
(HMAC), Class-A save 500s (array-input crash, 15 controllers `79fcf00`), **tenant
scoping WRITE surface** (86 sites + Employees `ownsEmployee`, `ce0be9d`/`173cf76`),
**XSS + Auth is_active** (org_chart esc-js, `hex_color` theme guard, `9653cfb`),
**reset anti-enumeration + register throttle** (`d4ba521`), **read-surface IDOR**
(5 Employees lists + Timesheet + ArchiveExport full-tenant-dump, `743524e`), and
**notification/email/search hardening** (notif IDOR, global-search fail-closed,
email-template superauth lockdown, Notifier try/catch, `71f2ea3`). Search audit found
**0 SQL injection** app-wide.

**▶ RESUME = `docs/security/security-backlog-2026-08.md` (P1–P9).** Top items:
**P1 [HIGH] dialog_* modal stored-XSS + object-level authz sweep** (cloned scaffold →
scoped fetch + `esc()`, then sweep), **P2 [HIGH] Mailbox reply/send IDOR**. Then P3–P9,
branded 404/500 + legal pages, then the live Playwright suite. **PENDING USER: the
employee-ID auto-gen format image.** Full detail: that backlog doc + `.claude`-memory
`hr-audit-findings-2026-08-04` + `hr-deploy-readiness-2026-08`.
_(User pausing 2026-08-04 eve — continues tomorrow after limit reset.)_

### Prior: ADR-003 **Phase 2 — tenant enforcement + clean erp-less URLs** — DONE (2026-08-04).
Resolution moved from a filter to the `pre_system` event (`App\Libraries\TenantBootstrap`)
because CI4 4.1.3 routes before before-filters run — a filter can't rewrite the routed
path. Adds: **path fallback** (`HOST/{slug}/…` on the platform host), **clean erp-less
tenant URLs** (transparently prepends `erp/`; root→`erp/desk`; legacy `/erp/*` still
serves; canonical 301 opt-in via `TENANT_CANONICAL_REDIRECT`), per-host `baseURL` +
**cookie domain**, and `tenant_url()` link helper. Enforcement via new global filter
**`TenantGuard`**: pins every request to its resolved `company_id` (effective company =
`user_type==='company' ? user_id : company_id`), cross-tenant → audited
(`tenant.cross_access_denied`) + on-host login; `super_user`→`admin.`; non-super off
`admin.`→landing. Verified live across all host/path forms (own=200, cross host+path
blocked, audit rows written, super→admin, admin→landing). Env knobs: `PLATFORM_HOST`,
`TENANT_URL_MODE`, `TENANT_CANONICAL_REDIRECT`. **Next = Phase 3 (Traefik wildcard cert
+ routers + admin Basic-Auth + custom-domain verify).**

### Prior: F2 — PesaPal aggregator + live browser walkthrough + docs consolidation —
DONE (2026-08-03). Added PesaPal (API 3.0) as a collections-only funding gateway
alongside Flutterwave: `App\Libraries\Collections\*` (interface + `PesapalCollections`
+ `Collections` resolver keyed on `collections_provider`), `service('collections')`,
migration `2026-08-03-000001_AddPesapalSettings`, `Webhooks::pesapal()` (server-side
`GetTransactionStatus`, idempotent credit), `spark pesapal:setup` (IPN registration),
PesaPal settings tab. Live-verified against PesaPal **production** (auth, RegisterIPN,
SubmitOrderRequest all 200; order created, unpaid-webhook correctly did NOT credit).
Consolidated PLAN/UPGRADE/ROADMAP/NOTIFICATIONS into a single `README.md` (ADRs kept).
Ran a full security audit (0 critical, 3 high, 4 medium, 4 low — see Known issues).
Prior F2 wallet/audit state below.

### Prior: wallet phases 3–4 + security audit — VERIFIED (2026-08-03).
Flutterwave aggregator driver (payouts + collections top-up + master-float
balance, degrades to Null until creds), unified `charge/transfer` webhook
(verif-hash, server-side re-verify, fail-closed in production), wallet UI
(company self-service + super-admin oversight + float reconcile), disbursement
batch dashboard (build→approve→process→reconcile + line drill-in), Flutterwave
settings tab. Two audit agents (security + correctness) ran; ALL critical/high
findings fixed and re-tested green (see decisions). Prior wallet core below.

### F2 wallet & funding model (ADR-002) — DONE 2026-08-03.
Aggregator-backed pooled wallet with a per-company virtual ledger:
`service('wallet')` (credit/reserve/release/settle/feeFor, advisory-locked,
idempotent credit + reserve), `ci_company_wallets` + `ci_wallet_transactions`,
engine reserves principal+fee before each transfer and settles/releases on the
terminal outcome, and an oversight surface `Erp/Wallet` (balance/statement/topup,
company-scoped; super-admin read-only over any company). Fee model configurable
via `disbursement_fee_flat` + `disbursement_fee_percent`. Migrations 000005/000006
applied; credit/reserve/settle/release + fee math + idempotent-reserve all
smoke-verified green. Next (keyed): Flutterwave driver + self-serve top-up webhook.
(Prior: notification engine VERIFIED GREEN 2026-08-01; PHP 8.2 green-up complete.)

## Stack
- CodeIgniter 4 (PHP) HR/ERP system — `spark`, `system/`, `app/`
- nginx (fronts PHP app) + PostgreSQL 16 + Redis 7 + beanstalkd (queue)
- Docker Compose (`compose.yml`), dev profile adds pgAdmin + MailHog

## Ports (lane 12000)
Host-published app ports mapped to the 12000 lane. Container-side ports unchanged.

| Service            | Host port | Container | Notes                                  |
|--------------------|-----------|-----------|----------------------------------------|
| nginx (app web)    | 12000     | 80        | primary dev HTTP entry (fronts PHP app) |
| pgAdmin (tooling)  | 12020     | 80        | dev profile, project-owned tooling      |
| nginx TLS (prod)   | 443       | 443       | LEFT — production TLS binding, untouched |

Source fallback `app/Config/App.php` `$baseURL` → `http://localhost:12000`.

### Shared services used (NOT remapped — intended reuse)
- MailHog/Mailpit UI — `8026:8025` (shared dev mail catch-all) — LEFT as-is
- Internal container wiring (unchanged): postgres:5432, redis (redis_host), beanstalkd
  — service-name refs on container ports, not host-published

## Recent decisions
- 2026-08-03: **Backlog exec (partial) — auth flows + white-label + Phase 1/2 UI.**
  Registration was BROKEN (form posted to non-existent `keyhrm/setup_trial`, 404);
  built `Home::register_company` (route `register-company`) — creates company +
  first admin + trial plan + per-tenant settings defaults (UGX/Kampala), success
  banner, provides all NOT-NULL cols. Verified live end-to-end with gmail AND
  yahoo (universal email — `valid_email`/RFC, no allowlist), then signed in.
  Login page now shows Rooibok logo (was hrsale signin logo). White-label:
  `brand_logo_html()` (super→Rooibok, tenant→own logo/name) + company logo/favicon
  upload on Theme Settings ([[hr-white-label-branding]]). Phase 1 (design tokens,
  dark mode, tables/forms) + Phase 2 (HR KPI dashboard row, rkChart wrapper) done.
  Cache-busting via `asset_v()`. app:init seeds uploads+archive. STILL PENDING
  (next session): backlog #25 team-member CREATE flow full test (infra present,
  pages green, add_employee is a POST modal endpoint), #26 zero-hardcoding audit,
  Phase 3 (core-HR UX depth). Test companies created: Lira Digital (id10), + Acme
  Corp rename. superadmin/`superadmin` pw = Admin1234 (dev).
- 2026-08-03: **Full-site Playwright test + fixes (landing + company + superadmin).**
  Landing was fully broken (jQuery/waypoints 404 → all JS dead) — vendored them;
  fixed favicon, logo/avatar paths, kiosk logo, decorative placeholders. Swept
  ~20 company sidebar pages + all superadmin pages (via superadmin login). Bugs
  fixed: company-view null crash ([[project-hrsale-rooibok]]), leave_status.js
  chart noise (guarded), expenses DataTable/delete calling dead routes (JS→real
  routes; auto-routing is off), super-admin dashboard + Archive Portal 500 (archive
  DB pointed at non-existent `postgres_archive` → fall back to main DB + loaded
  `docker/postgres/archive_schema.sql`), forgot-password title raw lang key.
  Deployment gaps noted: `uploads_data` volume + archive schema need first-boot
  seeding. UI/UX upgrade plan in `docs/UX-UPGRADE-PLAN.md`. superadmin/`superadmin`
  password reset to a test value this session (temp `user:setpass` command removed).
- 2026-08-03: **PesaPal added as collections/top-up gateway (ADR-002).** PesaPal
  is collections-only (hosted checkout in; no payouts), so it powers wallet
  top-ups; payouts stay on Flutterwave/direct MTN-Airtel. `collections_provider`
  setting selects the funding gateway. Two real CI4 gotchas found + fixed in ALL
  aggregator drivers (PesaPal + both Flutterwave): (1) `curlrequest` merges a
  leading-`/` path against `baseURI` and DROPS the `/v3` segment → 404 — fixed by
  passing absolute URLs; (2) config-level `headers` are dropped on these calls →
  `Authorization` never sent → 401 — fixed by passing headers per-request.
  Also fixed `SystemModel::$allowedFields` (Flutterwave + PesaPal columns were
  missing → CI4 silently stripped them on settings save).
- 2026-08-03: **Company dashboard / company-view crash fixed.** `erp_company_settings()`
  returned null for a company with no `ci_company_settings` row (e.g. the demo
  company id 8), and every company view dereferenced it → 500. Fixed the helper to
  fall back to the seeded default (company_id=2) + a guard in `company_dashboard.php`.
  Surfaced via a live Playwright walkthrough of the demo tenant (`GET /demo`).
- 2026-08-03: **Security audit (post-PesaPal): 0 critical, 3 high, 4 med, 4 low.**
  F2 money engine rated well-built; open items are AROUND it — see Known issues.
- 2026-08-03: **F2 security audit (two agents) — all critical/high fixed.**
  Findings and fixes (each re-tested):
  - C1 (CRITICAL, float theft): a company admin could mint unbacked balance via
    `wallet/topup`. Fix: manual top-up is super-admin only; companies fund solely
    via `wallet/fund` → hosted checkout → verified `charge.completed` webhook.
  - H1 (IDOR/tenancy): disbursement endpoints weren't company-scoped. Fix:
    `companyScope()`/`ownsBatch()` pin non-super admins to their own batches;
    list/lines filtered; build/build-payroll pin company_id; maker-checker now
    same-company (approver≠preparer within one company).
  - H2 (double-settle race): concurrent webhook+reconcile could settle twice.
    Fix: `settle()`/`release()` idempotent on reference (refExists under the
    per-wallet advisory lock).
  - Rollback safety: `WalletService::tx()` now honours `transStatus()` — a
    rolled-back reserve returns ok:false (was reporting success → overspend).
  - Reserve leak: unconfigured gateway now FAILS the line before reserving
    (Null driver used to leave it pending forever); `applyTerminal` settles on
    an OPEN reserve (`hasOpenReserve`), not the literal 'pending' status, closing
    the created→pending webhook race.
  - Webhooks fail-closed in production (no secret ⇒ 400) + transfer status is
    re-verified server-side, never trusted from the body.
  - XSS: all view innerHTML interpolations HTML-escaped; `source` allowlisted,
    `period` validated `^\d{4}-\d{2}$`.
  - Backstops: CHECK(balance>=0, reserved>=0); getOrCreate ON CONFLICT DO NOTHING;
    all-failed batch → status 'failed'; bank payout without bank_code fails clean.
  - Deferred (noted): per-endpoint rate-limiting (auth fixes close the vector;
    CI4 4.1.3 single-filter limitation) and integer-minor-unit money (NUMERIC(16,2)
    is adequate at UGX scale).
- 2026-08-03: **Funding model = aggregator-backed pooled wallet, per-company
  virtual ledger (ADR-002, Model C).** The real float sits in a licensed
  aggregator under one master Rooibok merchant account; each company gets a
  virtual balance in-app. Avoids Rooibok needing a BoU PSP/e-money licence
  (NPS Act 2020). Rejected per-company BYO creds (friction) and Rooibok-owned
  pooled bank account (licence). Payouts reserve principal+fee → settle/release
  on outcome; fees are the money-movement revenue line. See
  `docs/adrs/ADR-002-wallet-and-funding.md`.
- 2026-08-01: PHP 8.2 green-up (CI4 4.1.3 predates 8.2). Fixes, all committed to
  source so they survive a rebuild:
  1. `FILTER_SANITIZE_STRING` → `FILTER_SANITIZE_FULL_SPECIAL_CHARS` in 4 bundled
     `system/` files (CI4's own 4.2 change) — unblocks class loads.
  2. `spark` hardcoded `error_reporting(-1)` → masks `E_DEPRECATED`/`E_USER_
     DEPRECATED`, so CLI commands no longer abort on framework deprecations.
  3. `docker/php/php.ini`: added `output_buffering = 4096` + `implicit_flush =
     Off` — the container loads no main php.ini, so the web SAPI was inheriting
     CLI defaults (ob=0) and leaking bytes before headers.
  4. compose `app.user: "1000:1000"` — run php-fpm as the host bind-mount owner
     so the framework can write `writable/` (session/cache/logs). Root cause of
     the blank HTTP 500: fpm ran as www-data, could not write the uid-1000 tree,
     died on the first session write, and could not even log it.
  5. `Services::smsProvider()` now `helper('main')` before `system_setting()` —
     the CLI QueueWorker has no BaseController to autoload it (would fatal).
  6. Baselined the pre-existing `Add2faSupport` migration (its columns already
     exist from init.sql) by inserting its history row, then ran `migrate`
     (adds prefs table + `idx_notifications_user_read_created`, batch 2).
  Rebuilt the app image + recreated the container. nginx (12000) brought up.
- 2026-07-31: Built unified notification engine on top of existing beanstalkd
  Queue + QueueWorker (did NOT rebuild). New: `app/Libraries/Notifier.php`
  (service('notifier')), `app/Libraries/Sms/*` (interface + Africa's Talking +
  Null drivers, service('smsProvider')), `sms` worker tube, migration
  `2026-07-31-000001_CreateNotificationPrefs` (ci_user_notification_prefs +
  idx_notifications_user_read_created). Wired the broadcast SMS stub to the real
  provider. Deleted dead `NotificationsModel` (0 refs). New schema goes via CI4
  migrations (init.sql only runs on fresh DBs).
- 2026-07-25: Normalized dev host ports to lane 12000. nginx dev HTTP 8080→12000,
  pgAdmin 5050→12020. Left 443:443 (prod), Mailhog 8026:8025 (shared), and all
  container-internal service refs untouched.

## Known issues
- **Audit findings (2026-08-03, post-PesaPal) — ALL FIXED this session:**
  - [HIGH] `PayoutMethods` cross-tenant IDOR → FIXED: `targetEmployee()`/`canActOnMethod()`
    confine staff to self, company admins to their own company (super exempt); service
    exposes `ownerOf()`/`companyForEmployee()`. All 5 endpoints gated.
  - [HIGH] `Auth::verified_password` hardcoded-constant reset → FIXED: single-use, 30-min,
    hashed reset token (migration `…000002_AddPasswordResetToken`), token carried in the
    link as `email|token`, random password on success, token consumed. Login throttle now
    keyed per IP+identity (was global `'auth'`).
  - [HIGH] `Finance` (4 sites) + `Profile` avatar uploads → FIXED: `ext_in` allowlist +
    `getRandomName()` (no client filename/extension reaches disk).
  - [MED] `buildBatch` now rejects employees not in the funding company; `process()` takes
    a per-batch `pg_try_advisory_lock` (double-dispatch guard); ZKTeco webhook requires a
    device secret (fail-closed in prod).
  - [LOW] Stripe webhook idempotent on invoice id; `secret_key()` throws instead of using a
    public default; MoMo/Airtel token-failure logs redacted to http status.
  - Follow-up (not blocking): ZKTeco device→company binding for a scoped user lookup;
    nginx rule to deny PHP execution under `public/uploads/` (defense-in-depth atop ext_in).
- **PesaPal is wired to LIVE production keys** (`collections_provider=pesapal`,
  `pesapal_active=1`, `pesapal_environment=production`, keys encrypted in
  `ci_erp_settings`). Live top-up verification is PENDING a real payment (user opted
  out of spending real money). Order creation is free; the webhook→credit path is
  proven except the final COMPLETED→credit leg.
- SMS creds (sms_username/sms_api_key/sms_sender_id/sms_active/sms_gateway) must
  be set in Super Admin → Settings → SMS before SMS actually sends; until then
  smsProvider degrades to NullSmsProvider (no-op).
- Three in-app write paths still coexist (Notifier→NotificationModel::notify,
  the create_notification() helper, and legacy direct inserts). Notifier is the
  path forward; migrate call sites opportunistically.
- `Notifier::send($to,$event,$data,$channels)`: `$channels` is a LIST of enabled
  channel names (e.g. `['inapp']`), NOT an assoc map. Omit it to honour prefs.
- No PHPUnit in the app container (dev deps not installed); `tests/` can't run
  there without `composer install --dev` (needs `--ignore-platform-reqs` on 8.2).

## Next steps
- Run `php spark migrate` on each environment (adds prefs table + index).
- Adopt service('notifier')->send() in new dispatch points (leave, payroll, tasks).
  (Done: Erp/Companies registration + subscription events.)
- Add a preferences UI (My Profile) writing ci_user_notification_prefs.
- **Feature roadmap:** `docs/ROADMAP.md` — 14 planned features, dependency-ordered
  (audit → disbursement → expenses/loans/FX; notifier→WhatsApp/expiry; API→PWA/
  clock-in; people-ops; statutory PAYE/NSSF/LST; staff ID cards). Money-movement
  architecture in `docs/adrs/ADR-001-payroll-disbursement.md`.
- **Recommended first sprint:** F1 audit log → F2 disbursement (sandbox) → F3
  Uganda statutory.

## Build progress (roadmap)
- **F1 Audit log — DONE (2026-08-01).** `ci_audit_log` (hash-chained, append-only),
  `service('audit')->record()` (fail-safe), tamper-evident `verifyChain()`,
  super-admin viewer `Erp/AuditLog` (`erp/audit-log` — filters, CSV export,
  integrity check) + sidebar link. Instrumented company.created + subscription
  .updated. Verified: record + chain + tamper-detection + controller. Next
  (phase 2): instrument auth login/logout, user/role changes, and every F2
  disbursement transition. Prod hardening: grant app role INSERT+SELECT only on
  ci_audit_log.
- **F2 disbursement — phase 1 DONE (2026-08-01).** `ci_employee_payout_methods`
  (encrypted destination, last4 in clear); driver abstraction
  `App\Libraries\Disbursement\*` (interface + MTN MoMo + Airtel + Null +
  `service('disbursement')->for($type)` factory, degrades to Null until creds).
  `service('payoutMethods')`: add → verify (provider name-lookup + one-time code
  to the MSISDN, no money moved) → confirm → set-primary, all audited (F1).
  Controller `Erp/PayoutMethods` (erp/payout-methods/*) sends the code via SMS,
  never returns it. Wired `Config\Encryption::$key` from `ENCRYPTION_KEY` (was
  unset → encrypter threw / system_setting silently fell back to raw). Verified
  end-to-end against the Null/sandbox driver.
- **F2 disbursement — phase 2 DONE (2026-08-01).** `ci_disbursement_batches` +
  `ci_disbursements` (state machine created→pending→successful|failed, `reference`
  UUID unique = idempotency key). `service('disbursementEngine')`: buildBatch
  (skips employees without a verified method), approve (**maker-checker**:
  approver≠preparer), process (transfer with the reference persisted first),
  applyTerminal (**write-once, idempotent** — the double-credit guard), reconcile
  (status poll), handleCallback. `spark disbursements:reconcile [batch_id]` cron
  backstop. Webhooks `Api/V1/Webhooks::mtn|airtel` wired (raw-store, HMAC verify
  when a *_callback_secret is set, idempotent apply, always 200). Maker-checker
  controller `Erp/Disbursements` (list/build/approve/process/reconcile). Fixed a
  latent `->insert($row, true)` bug (2nd arg is escape, not return-id) in
  addMethod + buildBatch — now `insertID()`. Verified end-to-end incl. duplicate
  webhook = no-op.
- **F2 disbursement — phase 3 DONE (2026-08-01).** Payroll-period batch builder
  `buildFromPayroll()` (reads `ci_payslips` net, dedups already-paid/in-flight for
  the period), `build-payroll` endpoint, and safety caps (per-txn/per-run/per-day
  from settings, refused before any money moves). Columns added to
  `ci_erp_settings` (migration 000004): provider base URLs, callback secrets, caps.
- **F2 disbursement — wallet & funding DONE (2026-08-03, ADR-002).**
  `WalletService` (`service('wallet')`): credit (idempotent on reference), reserve
  (idempotent on reference), release, settle, feeFor, balance, transactions —
  each mutation advisory-locked per wallet (`pg_advisory_xact_lock`) so concurrent
  batches can't overspend. Tables `ci_company_wallets` (balance/reserved) +
  append-only `ci_wallet_transactions` (signed, running balance_after, unique
  `(type,reference)`) — migration 000005; fee knobs — migration 000006. Engine
  integration: `process()` reserves amount+fee before each transfer (insufficient
  funds ⇒ line fails, no provider call), releases on immediate rejection;
  `applyTerminal()` settles on success / releases on failure, gated to lines that
  actually held a reserve (status=pending). Oversight controller `Erp/Wallet`
  (erp/wallet/balance|statement|topup) — company admin scoped to own wallet,
  super-admin read-only over any company + can record a top-up. All audited (F1).
  Fixed a `$companyId` undefined-in-closure crash in credit()'s audit call.
  Verified: credit/reserve/settle/release + fee math + idempotent replay green.
  Next (keyed): Flutterwave driver (collections top-up + payouts + balance) behind
  the existing `DisbursementProviderInterface`; self-serve top-up webhook + wallet UI.
  Also still open: MoMo/Airtel **sandbox** creds → real transfer()/status()
  round-trip; batch dashboard UI; payments-skill KYC/go-live checklist; add a
  payout-methods panel to the employee profile (F2 phase-1 UI).
