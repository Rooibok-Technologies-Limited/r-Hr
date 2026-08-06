<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# Defect Register

Severity: Critical / High / Medium / Low.

---

## Navigation (Phase 2) — all fixed by the single-source IA (commit 05d93c8)
- **D-N01** (High) Company sidebar: 25+ ungrouped top-level items, arbitrary order. Fixed: 7 workflow groups.
- **D-N02** (High) Company sidebar: Inventory nested 4 levels (breaks 3-tier). Fixed: depth==3 enforced + verified.
- **D-N03** (Medium) Org Chart duplicated (`erp/chart` + `erp/org-chart`). Fixed: single canonical `erp/org-chart` under People.
- **D-N04** (Medium) Finance scattered across 10 loose siblings. Fixed: consolidated Finance group (7 items, payroll/wallet clusters).
- **D-N05** (Medium) 3 hand-maintained sidebars drift (Expenses shown twice for staff). Fixed: one config, one renderer.
- **D-N06** (Low) Labels mixed hardcoded literals + `lang()`, inconsistent casing ("Payroll run"). Fixed: `Nav.php` lang file.
- **D-N07** (Medium) No single source; bespoke `select_module_class()` + `sa_active()` active-state. Fixed: NavBuilder computes active trail.
- **D-N08** (Low) Icon reuse (credit-card ×4). Fixed: unique icon per item per portal (verified).
- **D-N09** (Low) Orphaned URL-only routes. Fixed: homed in NAVIGATION.md §orphans or documented deliberately-hidden.

## Authorization (Phase 4)
- **D-AUTHZ-01** (Critical) `Settings::index` (route `erp/system-settings`) edited the GLOBAL
  platform settings row (`setting_id=1`: application_name, currency_converter, timezone) but
  explicitly allowed `user_type == 'company'`, and `header.php` linked every company user to it
  via the top-bar gear. A tenant could read/mutate platform-wide configuration — cross-tenant
  privilege escalation.
  - **Root cause:** `index()` had a laxer guard than its sibling `super_settings()` (which
    correctly rejects non-super); the comment literally read "company admins allowed".
  - **Fix (commit below):** `index()` now `user_type !== 'super_user' → redirect`, mirroring
    `super_settings()`. Header gear routes company → `erp/company-settings` (their own tenant
    page), super → `erp/system-settings`.
  - **Verified by:** role-matrix probe — company/staff/anon now 307 on `erp/system-settings`,
    super 200; company retains 200 on `erp/company-settings` + `erp/my-profile`. 0 authz
    failures across the 11-route × 4-role matrix.

## Static (Phase 3)
- No app-code defects. Debug artifacts (`print_r`/`die`) are confined to CI4's framework error
  view, the vendored Stripe ThirdParty lib, and the super-only `Backup_erp` mysqli tool
  (legacy but functional). TODO/FIXME markers are all in vendored Stripe or false positives
  (phone-mask "XXX"). PHP lint: 0 failures across 1077 app files. Not fixed = not defects.

## Phase C — Zero-hardcoding sweep (2026-08-06)
Confirms the prior audit (88c72ab): the codebase is largely settings-driven. Evidence-based
per-category findings:
- **Currency**: erp_currency()/erp_currency_symbol() (tenant-first) — DONE earlier this session.
- **Timezone**: live app timezone set per-request from the tenant's system_timezone
  (BaseController date_default_timezone_set). One DEAD hardcoded line ($current_time =
  Time::now('Africa/Kampala'), never used) removed (97e987d).
- **Tax**: TaxEngine reads PAYE from ci_paye_bands (rate_percent) and NSSF from
  system_setting('nssf_employee_rate'/'nssf_employer_rate') with fallbacks — dynamic.
- **Phone**: the '256' MSISDN prefix in the MoMo/Airtel disbursement libraries is Uganda's
  real dialing code for those payment rails — legitimate domain infra, not a leak.
- **Brand**: tenant-facing views use system_setting('application_name')/$app_name with a
  'Rooibok HR' safety-net fallback (fires only when unset); platform frontend (landing/
  cookie/footer/api-docs) correctly shows the platform brand per the white-label model.
- **Employee-ID prefix / leave caps**: per-tenant configurable (this session).
- Minor/noted (not fixed): `frontend/contact.php` hardcodes `info@rooibok.co.ug` (placeholder
  domain) on the platform contact page — low-risk, could move to a platform setting (Phase C tail).
- Dead vendor template `meetings_calendar_kendo.php` points at demos.telerik.com — unused
  (included nowhere); leave or delete in a cleanup pass.

## Phase D — non-nav security audit (2026-08-06)
### D-AUTHZ-02 (Critical, FIXED e830e67)
A bare STAFF could POST erp/settings/update_currency and edit the GLOBAL setting_id=1 row
("Updated System Currency"), plus 15 sibling global-config endpoints (add_logo/favicon,
update_payment_gateway, company/currency types, religions, notification config,
super_settings, system_info) — all on the 'checklogin' filter. Switched 16 mutation routes
to 'superauth'; reads (_list/_info dropdown data) kept on checklogin. Verified: staff 303,
super 200, staff reads 200.

### D-HOLIDAY-01 (Medium, FIXED 18d7f19)
Holiday add/update/delete had no method-level authz. Added canManageHolidays(resource)
(company or staff with holiday2/3/4). Verified: bare staff -> Unauthorized, 0 rows created.

### D-IDOR-01 (High, PARTIALLY FIXED — dedicated pass needed)
**Systematic cross-tenant IDOR on write endpoints**: 31 delete/update statements run
`->where('<pk>', $id)->delete($id)` (or update) with NO company_id scope — tenant A can
mutate tenant B's row by id (ids are uencode-obfuscated but reversible). Awards::delete_award
FIXED as the pattern (e830e67 adds ->where('company_id', effective_company_id())).
REMAINING (verify each table's ownership column first — some lack company_id and scope via a
parent/user):
- HAS company_id (scope with effective_company_id()): Trainers:351, Visitors:478, Department:372,
  Timesheet:1355(attendance)+1293(overtime via parent), Payroll:1617/1638(advance_salary),
  Clients:1495(user)/1516(lead)/1537(followup).
- NO company_id (needs per-table ownership analysis): Suppliers:375, Warehouse:353,
  Employees:2848-2920(contract options), Estimates:641/Invoices:1067/Orders:753/
  Orderquotes:605/Purchases:590 (item-level — scope via parent document ownership).
- SUPER-only (superauth route filter — by design, NOT a defect): Companies:1000, Membership:563,
  Users:717/738, Languages:355, Settings:1323/1343/1363/1483.
Method: for each tenant-facing statement, add the actor's company scope (or verify parent
ownership for item rows), then live-probe cross-tenant delete no-ops.

### Other Phase D (remaining)
- D4 layout forensics across top routes × viewports (nav + company dashboard done; rest pending).
- D5 console-error sweep across every route × role (dashboards + nav done clean; rest pending).
