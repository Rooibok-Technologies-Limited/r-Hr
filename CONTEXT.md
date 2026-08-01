# Project Context
Last updated: 2026-08-01

## Current task
Notification engine (Phase I) — unified in-app + email + SMS dispatch — VERIFIED
GREEN end-to-end (2026-08-01). `Notifier` service, Africa's Talking SMS driver,
`sms` queue tube, per-user prefs table, dedup of notification models. Migration
applied; notifier in-app insert + `{token}` render confirmed; QueueWorker boots
and watches the `sms` tube; smsProvider degrades to NullSmsProvider (no creds).
See docs/NOTIFICATIONS.md. The whole app now boots and serves on PHP 8.2 (was
blocked): `/`, `/erp/login`, `/api/v1/health` all return HTTP 200.
(Prior tasks: dev port normalization into lane 12000; PHP 8.2 green-up — complete.)

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
  Phase 2 next: disbursement batches (`ci_disbursement_batches`/`ci_disbursements`),
  maker-checker approval, transfer via the driver against MoMo/Airtel **sandbox**,
  webhook (`Api/V1/Webhooks`) + status-poll reconciliation, idempotency keys.
  Go-live: set mtn_*/airtel_* creds in Settings; follow the payments skill KYC
  checklist. UI: add a payout-methods panel to the employee profile.
