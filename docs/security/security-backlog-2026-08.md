# Rooibok HR — Security Backlog & Audit Recommendations
_Last updated: 2026-08-04 — Bodo Desderio / Rooibok Technologies_

Consolidated output of the pre-production security audit (crash, privesc, tenant
scoping, XSS/Auth, search, notification, email, and modal sweeps). This file tracks
what is **FIXED** vs the prioritized **PENDING** backlog. The fixed items are in git
history; this doc is the forward plan.

## Fixed this cycle (main)
- Privilege escalation: 42 super routes checklogin → superauth; SuperAuth fail-closed.
- Forgeable id tokens → HMAC-authenticated (unforgeable).
- Class A save 500s: array-input crash across 15 controllers.
- **Tenant scoping (WRITE)**: 86 update/delete/read sites + Employees `ownsEmployee`
  guard-then-act (`ce0be9d`, `173cf76`).
- **XSS + Auth**: org_chart JS-string `esc(js)`, `hex_color()` theme guard, Auth
  `is_active` filter repaired (`9653cfb`).
- **Registration/reset**: password-reset anti-enumeration + throttle, register
  rate-limit (`d4ba521`).
- **Read-surface IDOR**: 5 Employees list methods + Timesheet attendance branch +
  **ArchiveExport full-tenant-dump** (`743524e`).
- **Notification/email/search**: notification delete/mark IDOR, global-search
  fail-closed, email-template superauth lockdown, Notifier try/catch (`71f2ea3`).

Search audit conclusion: **0 SQL-injection sinks** anywhere — every DataTables handler
uses bound builder `like()/where()`, hardcoded `orderBy`, no request-driven columns.
Search does **not** need injection hardening.

Mass-assignment (UsersModel::allowedFields): assessed, **not reachable** — every
insert/update uses an explicit field array (no raw `getPost()` spread). Left as-is.

---

## PENDING — prioritized

### P1 [HIGH] Modal dialog stored-XSS + object-level authorization sweep
The `read_*` endpoints render `dialog_*` views that fetch the record **inside the
view** by `udecode(field_id)` with **no `company_id` on the fetch**, and echo columns
into inputs **without `esc()`**. Tokens are HMAC-unforgeable (mitigates active id
tampering), but this is missing defense-in-depth + **stored XSS** (a `"` or
`</textarea>` in a stored field breaks out; rich-text description fields aren't
strip-tagged on write).
- Scope: dozens of `dialog_*` views (announcements, complaints, leave, tickets,
  holidays, events, documents, finance accounts/transactions, employee details, +
  ~20 more — template-cloned).
- Fix: (a) append `->where('company_id', <effective company>)` to every dialog record
  fetch and render an empty state when null; (b) `esc()` every `$result[...]` output
  (`esc($x,'attr')` in attributes); for rich-text fields sanitize with an HTML
  allowlist (HTMLPurifier), not `strip_tags`.
- **Root cause is one cloned scaffold** — fix the template/generator, then sweep.

### P2 [HIGH] Internal mailbox IDOR (and half-built feature)
`Mailbox::reply_mail` (274-302) and `send_mail` (178,191) take `mail_id`/`mail_to`
from client POST tokens and never verify company/participant → cross-tenant reply/
insert. Also several mailbox **display views are missing** → those pages 500.
- Fix: a helper that loads a mail/reply by id and asserts `company_id` + participant
  before any read/update/insert; OR remove the dead routes if the feature is shelved.

### P3 [MED] Email templates are global (no tenant isolation)
`ci_email_template` has no `company_id` — one template set shared platform-wide.
Now locked to super-admin (P-fixed), but true multi-tenant email needs a
`company_id` column + per-tenant scoping of reads/writes.

### P4 [MED] SMTP config + secrets
`app/Config/Smtp.php` carries placeholder creds in tracked source; `Services::smtp()`
is **undefined** (the `smtp` branch fatals); DB SMTP settings are never loaded.
- Fix: define an `smtp` service building `Config\Email` from `.env`/DB at runtime;
  move creds to `.env`; delete hardcoded values. Never commit SMTP secrets.

### P5 [MED] Todo GET-mutation CSRF bypass
`Todo::delete_todo` / `update_item` are `match(['get','post'])` and invoked via
`type:"GET"` in `todo.js` — state changes triggerable by `<img src>`, CSRF-exempt.
- Fix: make them POST-only + send CSRF hash from the JS.

### P6 [MED] Broken modal contracts (404 — modal never populates)
- `expenses.js:107` → `expenses/read_expense` (real route: `expenses/read`).
- `finance_payers.js:60` → `finance/read_payee_payers` (no such method/route).
- `todo.js:60` → `todo/read_todo` (no such method/route).
- Fix: point JS at the real route or add the missing `read_*` action; verify the
  JSON response shape the JS expects.

### P7 [MED] Email template output-encoding
Template `name`/`subject` echoed unescaped in `dialog_email_template.php`; senders
`html_entity_decode()` the body (revives markup). `phpmail` branch concatenates
`$from` into headers with no CRLF strip → header injection if a tenant sets a crafted
company email.
- Fix: `esc()` placeholder values at substitution; drop `html_entity_decode` in
  senders; strip `\r\n` from `$from/$to/$subject` (or drop the raw `mail()` branch).

### P8 [LOW] Notification queue resilience & cost
- No idempotency key on `(event, recipient, entity)` → a double-submit re-enqueues
  duplicate **email + SMS** (Africa's Talking = real money). Add a short-window dedupe
  before paid channels.
- `QueueWorker` `bury()`s failed jobs with no auto-kick/dead-letter/alert; the combined
  broadcast job re-sends email when only SMS failed. Split into per-channel jobs; add
  bounded retry + buried-count alerting.

### P9 [LOW] Notification tenant defense-in-depth
Read/mark/delete filter `user_id` only (now scoped); add `company_id` as a second
scope and remove or tenant-gate the `user_id = 0` global-broadcast semantics.

---

## Cross-cutting recommendation
P1 (dialog scoping+XSS) and the tenant READ surface are the same root cause: a cloned
view scaffold with no object-level authz and no output encoding. The durable fix is a
**single reviewed dialog scaffold** (scoped fetch helper + `esc()` by default) that all
`dialog_*` views are re-derived from — otherwise the pattern regrows per new module.
