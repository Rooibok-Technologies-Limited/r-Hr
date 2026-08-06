<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->

# Rooibok HR

A multi-tenant HR / payroll / ERP SaaS for Uganda, built on the HRSALE
(CodeIgniter 4) base and extended with a money-movement platform: audit trail,
employee payout methods, a maker-checker disbursement engine, and an
aggregator-backed company wallet that funds payouts via **PesaPal** or
**Flutterwave**.

> One company admin manages their own tenant; a Rooibok super admin has
> oversight across all companies. Every money and identity action is audited.

---

## Stack

| Layer            | Choice                                                                                                                                                        |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Framework        | CodeIgniter 4.1.3 · PHP 8.2                                                                                                                                  |
| Database         | PostgreSQL 16 (UUID references on money rows, soft deletes)                                                                                                   |
| Cache / sessions | Redis 7                                                                                                                                                       |
| Queue            | beanstalkd (tubes:`payroll`, `emails`, `sms`, `payments`, `broadcasts`, `archive_vault`) via `App\Libraries\Queue` + `php spark queue:worker` |
| Web              | nginx → PHP-FPM                                                                                                                                              |
| Orchestration    | Docker Compose (`compose.yml`); dev profile adds pgAdmin + MailHog                                                                                          |
| Payments         | PesaPal (API 3.0) + Flutterwave (collections + transfers); Stripe (SaaS subscription billing); MTN MoMo + Airtel Money (direct payout rails)                  |

---

## Quick start (dev)

```bash
docker compose up -d            # app, nginx, postgres, redis, beanstalkd
docker exec -w /var/www/html rhr_app php spark migrate       # schema
docker exec -u 0 -w /var/www/html rhr_app php spark app:init # seed default assets + archive schema (idempotent)
```

`app:init` closes two first-boot gaps: it seeds the empty `uploads_data` volume
with default assets (logos, favicon, avatar) from `docker/seed/uploads`, and
creates the `arc_*` archive tables from `docker/postgres/archive_schema.sql`.
Run it after `up`; it is safe to re-run.

- App: **http://localhost:12000** (host port lane **12000**; container port 80)
- pgAdmin (dev profile): http://localhost:12020
- MailHog (shared dev mail catch-all): http://localhost:8026

### Logins (seeded)

| Role        | Username                        | Notes                                                   |
| ----------- | ------------------------------- | ------------------------------------------------------- |
| Super admin | `superadmin` (`admin@.com`) | full platform oversight                                 |
| Company     | `demo` — Demo Corp           | `GET /demo` auto-logs in as this tenant (no password) |

Schema comes from `docker/postgres/init.sql` on a fresh DB; **all subsequent
schema changes go through CI4 migrations** (`app/Database/Migrations/`), never
init.sql.

---

## Architecture

### Multi-tenancy

`company_id` scopes every tenant table and query. A company admin is pinned to
their own `company_id` (their `user_id` **is** their `company_id` in this
codebase); super admins may target any company explicitly. Filters:
`CheckLogin`, `CompanyAuth`, `SuperAuth`, `JwtAuth`, `DemoMode`, `Throttle`.

### Funding model — aggregator-backed pooled wallet (ADR-002)

Rooibok holds one **master merchant account** at a licensed aggregator; each
company gets a **virtual wallet** (`ci_company_wallets` balance/reserved +
append-only `ci_wallet_transactions`). This avoids Rooibok needing a Bank of
Uganda PSP/e-money licence under the NPS Act 2020.

```
Company funds wallet  ──►  PesaPal / Flutterwave hosted checkout  ──►  charge verified server-side  ──►  wallet credited
Payroll batch  ──►  reserve principal+fee  ──►  provider transfer  ──►  settle / release on terminal status
```

- **Collections (top-up):** `service('collections')` resolves the funding gateway
  from the `collections_provider` setting (PesaPal or Flutterwave), degrading to
  "not configured" when no keys are set.
- **Payouts:** `service('disbursement')->for($type)`. When
  `disbursement_aggregator = flutterwave`, all payouts pool through Flutterwave's
  master float; otherwise each type routes to its direct rail (MTN / Airtel).
  **PesaPal is collections-only** — it never does payouts.

See `docs/adrs/ADR-001-payroll-disbursement.md` and
`docs/adrs/ADR-002-wallet-and-funding.md` for the full rationale.

### Money golden rules (enforced across the codebase)

- Amount, currency and payee are **server-authoritative**; a charge is always
  re-verified server-side and the wallet is credited from the *verify response*,
  never the webhook/IPN body.
- Every outbound transfer carries a caller-generated UUID `reference`, persisted
  **before** the provider call, used as the idempotency key.
- Every state change is idempotent on `(type, reference)`; a replayed
  webhook/poll applies exactly once. Terminal states are write-once.
- Wallet mutations run under a per-wallet `pg_advisory_xact_lock`; a rolled-back
  reserve returns `ok:false` so the engine never moves money it didn't hold.
- Disbursements require **maker-checker** approval (approver ≠ preparer, same
  company) and never run straight from a webhook.
- Webhooks **fail closed in production** — no signature/secret configured ⇒ the
  money event is refused (400/401).

---

## Payments configuration

Super Admin → **Settings** exposes a tab per provider (Stripe, MTN MoMo, Airtel,
Flutterwave, PesaPal, SMS). Secret keys are **encrypted at rest** (AES via
`ENCRYPTION_KEY`) and decrypted on read by `system_setting()`.

### PesaPal (wallet top-ups)

1. Settings → **PesaPal**: enter Consumer Key + Consumer Secret, set environment
   (sandbox `cybqa.pesapal.com/pesapalv3` / production `pay.pesapal.com/v3`),
   choose **Wallet funding provider = PesaPal**, save.
2. Register the IPN once (stores the returned `ipn_id`):
   ```bash
   php spark pesapal:setup            # defaults to <site>/api/v1/webhooks/pesapal
   ```
3. A company funds its wallet at **Wallet → Pay online** → PesaPal hosted
   checkout → on completion the IPN triggers a server-side `GetTransactionStatus`
   → the wallet is credited. PesaPal's IPN carries **no status**, so the status
   fetch is mandatory (and makes the notification unforgeable).

### Webhooks / callbacks

| Endpoint                              | Provider                | Auth                               |
| ------------------------------------- | ----------------------- | ---------------------------------- |
| `POST /api/v1/webhooks/stripe`      | Stripe billing          | `Stripe-Signature`               |
| `POST /api/v1/webhooks/mtn`         | MoMo payout callback    | HMAC (`mtn_callback_secret`)     |
| `POST /api/v1/webhooks/airtel`      | Airtel payout callback  | HMAC (`airtel_callback_secret`)  |
| `POST /api/v1/webhooks/flutterwave` | Collections + transfers | `verif-hash`                     |
| `POST\|GET /api/v1/webhooks/pesapal` | PesaPal IPN             | server-side status fetch           |
| `POST /api/v1/webhooks/zkteco`      | Biometric attendance    | *(device secret — see roadmap)* |

Reconciliation backstop (webhooks are best-effort):
`php spark disbursements:reconcile [batch_id]` (cron every ~10 min).

---

## Notification engine

One call fans a logical event across in-app + email + SMS:

```php
service('notifier')->send($userId, 'leave.approved', [
    'title' => 'Leave approved',
    'body'  => 'Your leave from 1–3 Aug is approved.',
    'link'  => 'erp/leave',
]); // omit the channels arg to honour ci_user_notification_prefs
```

- **in-app** — synchronous insert into `ci_notifications`.
- **email** — queued on `emails`; template `ci_email_template.template_code = event`.
- **sms** — queued on `sms`; template `ci_sms_template.subject = event`.

A channel with no matching template is skipped silently. `service('smsProvider')`
selects the SMS gateway from settings (Africa's Talking driver; degrades to a
no-op `NullSmsProvider` when inactive or missing credentials).

---

## Build status

| Feature                                       | Status                                                                                                                                         |
| --------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| **F1 — Audit log**                     | ✅`ci_audit_log` hash-chained append-only, `service('audit')->record()`, tamper-evident `verifyChain()`, super-admin viewer + CSV export |
| **F2 — Payout methods + verification** | ✅ encrypted destinations, provider name-lookup + OTP-to-MSISDN confirm                                                                        |
| **F2 — Disbursement engine**           | ✅ batches, maker-checker, reserve→settle/release, reconciliation, safety caps (per-txn/run/day)                                              |
| **F2 — Wallet + funding (ADR-002)**    | ✅ advisory-locked ledger, idempotent credit/reserve/settle/release, fee model, super-admin float reconcile                                    |
| **F2 — Aggregators**                   | ✅ Flutterwave (collections + transfers + balance) · PesaPal (collections/top-up) with hosted checkout + IPN                                  |
| Notification engine                           | ✅ in-app + email + SMS over beanstalkd                                                                                                        |

### Roadmap (dependency-ordered)

**Wave 1 — money foundation:** F1 audit → **F2 disbursement** → F3 Uganda
statutory (PAYE/NSSF/LST).
**Wave 2 — reuse the rails:** F4 expense reimbursements · F5 staff loans/advances
· F6 multi-currency.
**Wave 3 — employee experience:** F7 PWA self-service (offline-first, installable)
· F8 WhatsApp channel · F9 geofenced/selfie clock-in.
**Wave 4 — people ops:** F10 performance/OKRs · F11 on/offboarding · F12 shift
rostering · F13 document-expiry alerts · F14 staff ID-card generator.

Cross-cutting for every feature: `company_id` scoping, CI4 migrations, audited +
rate-limited money/identity actions, encrypted secrets/destinations, notifier for
new dispatch points. Longer-term platform goals: PWA offline caching + install,
per-company subdomains / custom domains (`companyname.rooibok.co.ug`).

---

## Security posture

The F2 money engine follows the golden rules above (server-authoritative amounts,
ledger idempotency, advisory-locked balances, fail-closed webhooks, maker-checker).
See the latest audit in `CONTEXT.md` for the current open-findings list and the
`security` / `payments` skills for the standing checklists. Production hardening:
grant the app DB role INSERT+SELECT only on `ci_audit_log`; store secrets as env
vars; HTTPS callback URLs; alert on FAILED-rate spikes and stuck-PENDING rows.

---

## Conventions

- **Schema:** CI4 migrations only (init.sql runs on fresh DBs).
- **Ports:** one contiguous host lane (12000) per project; container-internal
  ports unchanged.
- **Secrets:** `system_setting('key')` reads decrypt sensitive keys; blank form
  submissions keep the stored secret (never re-rendered).
- **Money:** UUID references, soft deletes, raw provider payload stored verbatim
  before parsing.
- Working state and decision history live in `CONTEXT.md`.
