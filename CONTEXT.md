# Project Context
Last updated: 2026-08-03

## Current task
F2 disbursement — **wallet phases 3–4 + full security audit** — VERIFIED (2026-08-03).
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
