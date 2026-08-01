# Rooibok HR — Feature Roadmap

Status: planning · Owner: Bodo Desderio · Last updated: 2026-08-01

This roadmap plans 13 features on top of the existing HRSALE/CI4 base. It is
**dependency-ordered**: shared rails are built once and reused, so the sequence
matters more than the list. Each feature carries a data model, the surfaces it
touches, its verification/security stance, phases, dependencies, and a rough
size (S ≈ 1–2d, M ≈ 3–5d, L ≈ 1–2wk of focused work).

---

## 0. What already exists (do not rebuild)

- **Payroll calculation** — `ci_payslips` (+ allowances / commissions / other /
  statutory-deductions), `ci_advance_salary`, `Erp/Payroll`. Numbers are
  produced; nothing pays them out.
- **Notification engine** — `service('notifier')->send()` fans in-app + email +
  SMS over beanstalkd; prefs table; Africa's Talking driver. See
  `docs/NOTIFICATIONS.md`.
- **JSON API** — `app/Controllers/Api/V1/*` (Auth/JWT, Employees, Attendance,
  Visitors, Subscription, Webhooks, Health) — the PWA/mobile surface.
- **Queue/worker** — `App\Libraries\Queue` + `App\Commands\QueueWorker` (tubes:
  payroll, emails, sms, payments, broadcasts, archive_vault).
- **Filters** — CheckLogin, CompanyAuth, SuperAuth, JwtAuth, DemoMode, Throttle.

## Shared rails (built in Wave 1, reused everywhere)

| Rail | Built by | Reused by |
|------|----------|-----------|
| **Audit log** | F1 | every money feature, user/role changes |
| **Disbursement core** (payout methods + verification + engine + reconciliation) | F2 | F4 expenses, F5 loans, F6 multi-currency |
| **Notifier channels + scheduler** | F8 | F13 expiry alerts, all approvals |
| **Api/V1 self-service surface** | F7 | F9 clock-in, employee views of F2/F4/F5 |

---

# Wave 1 — Money-movement foundation

> Gate: **no real disbursement runs until F1 (audit) + verification (F2) + a
> maker-checker approval path are all live.** This is money; correctness and
> traceability come before convenience.

## F1 · Audit log & compliance trail — **S/M** · depends on: none
**Goal.** Immutable who-did-what for sensitive actions (payroll edits,
disbursement approve/run, payout-method changes, user/role/permission changes,
login/logout).

- **Data:** `ci_audit_log` (id, actor_user_id, actor_type, company_id, action,
  entity_type, entity_id, before_json, after_json, ip, user_agent, created_at).
  Append-only; no update/delete grants for app role.
- **Surfaces:** a small `Audit` library with `Audit::record($action, $entity,
  $before, $after)`; hook into payroll/disbursement/user controllers. Super-admin
  read-only viewer with filters + CSV export (`Erp/AuditLog`).
- **Security:** write-only from app; retention policy; PII minimised in
  before/after (store field names + hashes for secrets).
- **Phases:** (1) table + library + viewer, (2) instrument money + identity
  actions, (3) tamper-evidence (hash-chain each row to the previous).

## F2 · Payroll Disbursement (MoMo / Airtel / Bank) — **L** · depends on: F1
**Goal.** Pay `ci_payslips` net amounts to each employee's verified destination.

- **Data:**
  - `ci_employee_payout_methods` (id, employee_id, type `momo|airtel|bank`,
    msisdn/account_no **(encrypted)**, account_name, provider, is_primary,
    is_active, verified_at, verification_ref, created_at).
  - `ci_disbursement_batches` (id, company_id, period, source `payroll`,
    status `draft|approved|processing|completed|failed`, prepared_by,
    approved_by, totals, created_at).
  - `ci_disbursements` (id, batch_id, employee_id, method_id, amount, currency,
    provider, provider_txn_id, idempotency_key **(unique)**, status
    `pending|processing|success|failed`, attempts, failure_reason, settled_at).
- **Verification (mandatory before payable):**
  - *Mobile money* — provider **account-holder name lookup** (MTN MoMo *Validate
    Account Holder Status* / Airtel KYC), then a **micro-payout + code confirm**
    (send a token amount; employee confirms the reference) to prove control.
  - *Bank* — name-match / penny-drop where supported, else **maker-checker**
    manual verification by HR with evidence upload.
  - A method with `verified_at = NULL` is never included in a batch.
- **Engine:** batch built from a payroll period → **maker-checker approval**
  (preparer ≠ approver, dual control) → jobs pushed to the `payments` tube →
  provider call with **idempotency key** → status via **provider callback**
  (`Api/V1/Webhooks`, signature-verified) with a **poll fallback**. Failed items
  retry with backoff; a batch never double-pays (idempotency + unique key).
- **Reconciliation:** each disbursement posts to a ledger; daily reconcile
  provider statement vs `ci_disbursements`; surfaced in a batch dashboard.
- **Security/compliance:** encrypted destinations; float/balance pre-check;
  per-run and per-day caps; every state change audited (F1); MoMo/Airtel
  **sandbox → KYC/go-live** checklist (use the `payments` skill).
- **Phases:** (1) payout methods + verification, (2) batch build + approval +
  engine against **sandbox**, (3) callbacks + reconciliation + retries,
  (4) dashboards + caps + go-live. See `docs/adrs/ADR-001-payroll-disbursement.md`.

## F3 · Uganda statutory — PAYE / NSSF / LST — **M** · depends on: payroll calc
**Goal.** Correct statutory lines on every payslip + filing-ready exports.

- **Data:** `ci_tax_bands` (PAYE brackets, effective-dated), `ci_statutory_config`
  (NSSF employee 5% / employer 10%, LST bands, effective dates). Reuse
  `ci_payslip_statutory_deductions` for per-payslip lines.
- **Logic:** a `Statutory` service computing PAYE (progressive bands), NSSF
  (employee + employer), and LST (annual, apportioned) — **effective-dated** so
  historical payslips stay correct when rates change.
- **Surfaces:** config UI under Settings; auto-applied in payroll generation;
  **URA/NSSF-shaped exports** (CSV/return templates) under `Erp/Reports`.
- **Phases:** (1) config + bands, (2) calculation into payslips, (3) return
  exports + employer-cost reporting.

---

# Wave 2 — Reuse the disbursement rails

## F4 · Expense claims + reimbursement payouts — **M** · depends on: F2
- **Data:** `ci_expense_claims` (employee, category, amount, currency, receipt
  file, status `submitted|approved|rejected|paid`, approver, notes),
  `ci_expense_claim_lines`.
- **Flow:** employee submits (with receipt) → approval chain → approved claims
  become a **disbursement batch** (source `expense`) through the exact F2 engine.
- **Surfaces:** employee submit UI + PWA; approver queue; ties into F1 + notifier.
- **Phases:** (1) claim CRUD + receipts, (2) approval, (3) payout via F2.

## F5 · Staff loans & advances — **M** · depends on: F2, payroll calc
- **Data:** `ci_loans` (employee, principal, rate, term, status, disbursed_at),
  `ci_loan_repayments` (schedule: due_date, amount, principal/interest, paid).
  Fold the bare `ci_advance_salary` in as a zero-interest short loan type.
- **Flow:** request → approve → **disburse via F2** → **auto-deduct** repayment
  in payroll each period (writes a payslip deduction line) → close on completion.
- **Surfaces:** request/approve UI, employee statement (PWA), arrears report.
- **Phases:** (1) loan + schedule model, (2) disburse + payroll deduction hook,
  (3) statements + early-settlement.

## F6 · Multi-currency / cross-border pay — **M** · depends on: F2
- **Data:** `ci_currencies`, `ci_fx_rates` (effective-dated, source), currency on
  salary/payslip/disbursement.
- **Logic:** hold salary in a contract currency; convert at a captured rate at
  run time; store both amounts + rate on the disbursement for audit.
- **Surfaces:** rate management (manual + optional feed) under Settings;
  currency-aware payslip + disbursement.
- **Phases:** (1) currency model + rates, (2) payslip/disbursement currency,
  (3) FX gain/loss reporting.

---

# Wave 3 — Employee experience

## F7 · Employee PWA self-service — **L** · depends on: Api/V1 (+ reads F2/F4/F5)
**Goal.** Installable PWA (Next.js or bare) on the JWT API: view/download
payslips, request leave/advances/expenses, see disbursement status, clock in.
- **Backend:** extend `Api/V1` (Payslips, Leave, Expenses, Disbursements, Me);
  JWT already in place (`JwtAuth`).
- **Frontend:** PWA shell, offline cache of last payslip, push-ready.
- **Security:** scope every endpoint to the JWT subject; rate-limit; no PII
  overexposure.
- **Phases:** (1) API endpoints + auth hardening, (2) PWA shell + payslips/leave,
  (3) expenses/loans/disbursement status + installability.

## F8 · WhatsApp + SMS notifications — **S/M** · depends on: notifier
**Goal.** Add a **WhatsApp Business** channel to the existing notifier.
- **Design:** new `whatsapp` tube + `WhatsAppProvider` implementing the same
  driver contract as `SmsProviderInterface` (Meta Cloud API or a BSP); template
  registration; number-verification reuse from F2 where relevant.
- **Surfaces:** channel prefs (extend `ci_user_notification_prefs`), settings for
  credentials (encrypted, same pattern as SMS).
- **Phases:** (1) provider + tube + worker handler, (2) template mapping + prefs,
  (3) delivery receipts.

## F9 · Geofenced / selfie clock-in — **M** · depends on: Api/V1, AttendanceLive
**Goal.** Kill buddy-punching with location + identity proof.
- **Data:** extend attendance with lat/lng, accuracy, geofence_id, selfie file,
  device id; `ci_geofences` (site polygons/radius).
- **Logic:** validate point-in-geofence server-side; optional face match; flag
  anomalies for review.
- **Phases:** (1) geofence model + API clock endpoints, (2) selfie capture +
  storage, (3) anomaly flags + admin review.

---

# Wave 4 — People ops

## F10 · Performance / appraisals / OKRs — **L** · depends on: none (feeds payroll)
- **Data:** `ci_review_cycles`, `ci_goals` (owner, parent, progress, weight),
  `ci_reviews` (subject, reviewer, type self|manager|peer, ratings, comments).
- **Flow:** cycle setup → goal setting → check-ins → 360 collection → calibrated
  rating → optional feed into promotions/increments (payroll).
- **Phases:** (1) cycles + goals, (2) review forms + 360, (3) calibration +
  reporting.

## F11 · Onboarding / offboarding workflows — **M** · depends on: F1 (+ Assets)
- **Data:** `ci_checklists` (template), `ci_checklist_tasks`, `ci_onboarding_runs`
  (employee, type onboard|offboard, status, assignee per task).
- **Flow:** hire triggers an onboarding run (docs, equipment via Assets, access
  grants); resignation triggers offboarding (asset handover, access revocation —
  audited via F1). Notifier drives reminders.
- **Phases:** (1) checklist templates + runs, (2) event triggers (hire/resign),
  (3) asset/access integration + SLA reminders.

## F12 · Shift scheduling & rostering — **M** · depends on: Officeshifts, attendance
- **Data:** `ci_rosters`, `ci_roster_shifts` (employee, date, shift, location),
  `ci_shift_swaps`.
- **Flow:** build/publish roster → notify staff → swap requests/approvals →
  feeds attendance + overtime.
- **Phases:** (1) roster model + builder, (2) publish + notify, (3) swaps +
  coverage gaps.

## F13 · Document / permit expiry alerts — **S** · depends on: notifier, Documents
- **Data:** `ci_tracked_documents` (owner, type contract|id|permit|cert, number,
  issued, expires, file). Reuse `Documents` where possible.
- **Logic:** a scheduled `spark documents:check-expiry` command (cron/queue)
  sends tiered reminders (T-60/30/7/expired) via the notifier.
- **Phases:** (1) tracked-doc model + UI, (2) expiry scanner command, (3) tiered
  reminder cadence + dashboard.

## F14 · Staff ID card generator (front + back, print-ready) — **S/M** · depends on: Employees, Pdf
**Goal.** Branded employee ID cards, **front and back**, as print-ready PDF at
CR80 (85.6 × 54 mm) with photo, QR verification, and batch printing.
- **Data:** `ci_id_card_templates` (company-scoped: layout, brand colours, logo,
  field map, front + back design), `ci_id_cards` (employee, card_no, issued_at,
  expires_at, qr_token, status `active|revoked|reissued`). Reuse the employee
  record + `staff_profile_photo()`.
- **Rendering:** server-side PDF at exact CR80 with bleed/crop marks.
  - *Front:* photo, full name, staff no, department/role, company logo, **QR**,
    validity.
  - *Back:* barcode, emergency contact, "return if found" + terms, signature
    strip.
  Reuse the existing `Erp/Pdf` controller (dompdf/tcpdf).
- **Verification:** QR encodes a **signed token** → public
  `/verify/staff/{token}` endpoint confirms the card is active (ties to F1 +
  employee status), so forged cards fail the scan.
- **Surfaces:** "Generate ID" action in Employees (preview front/back), batch
  export per department, print CSS; reissue/revoke.
- **Phases:** (1) template + single-card front/back PDF, (2) QR + verify
  endpoint, (3) batch print + reissue/revoke lifecycle.

---

## Suggested build order (critical path)

```
F1 audit ─┬─► F2 disbursement ─┬─► F4 expenses
          │                    ├─► F5 loans
          │                    └─► F6 multi-currency
          └─► (unblocks all money features)

F3 statutory  ── parallel with F2 (payroll team)
F8 whatsapp ──► F13 expiry alerts        (notifier track, parallel)
F7 PWA ──────► F9 clock-in               (API track, parallel)
F10 / F11 / F12                          (people-ops track, independent)
```

**Recommended first sprint:** F1 → F2 (through sandbox) → F3, since money movement
is the headline value and F1/F3 de-risk it. The three tracks (money, notifier,
API/people-ops) can then run in parallel.

## Cross-cutting requirements (apply to every feature)

- Multi-tenant scoping (`company_id`) on every new table + query.
- CI4 migrations for all schema (init.sql only runs on fresh DBs).
- Money & identity actions are audited (F1) and rate-limited.
- Secrets/destinations encrypted via the existing `system_setting()` pattern.
- New dispatch points use `service('notifier')->send()`.
- Each feature ships with its own section update to `CONTEXT.md`.
