<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# Remaining Work — Phased Plan (compiled 2026-08-06, branch audit/full-system-20260806)

Everything below is scoped for future sessions. Ordered by dependency + value.

## Test users seeded (Acme, company_id=2) — for viewing each role's dashboard/sidebar
All password `Staff1234!`. Created this session; each shows a distinct dynamic sidebar
(verified via NavBuilder):

| Username | Dept | Role | Sidebar groups seen |
|---|---|---|---|
| `hr.officer` | Human Resources | HR Officer Role | Overview, People (employees/recruitment/org-chart/core-hr), Time & Leave, Talent (performance), Finance (payroll), Account |
| `fin.accountant` | Finance | Accountant Role | Overview, Time & Leave (+requests), Finance (finance/payroll/expenses/invoices/estimates), Account |
| `ops.lead` | Operations | Operations Lead Role | Overview, People (clients/leads), Time & Leave, Talent (training), Workspace (tickets), Account |
| `testanalyst` | (existing) | QA Tester | Overview, Time & Leave, Finance, Account |
| `kelly.flynn` | — | company owner | all 7 tenant groups |
| `superadmin` | — | super_user | 6 platform groups incl. System |

(To reseed: the seeding logic created ci_staff_roles + ci_erp_users/details with proper
password_hash and the tenant ID-card number generator. Roles: HR Officer=id2, Accountant=id3,
Operations Lead=id4.)

---

## PHASE A — Dashboard redesign (neat / sleek / smart, per role)
**Why:** dashboards are dense and inconsistent across roles (user feedback). 4 dashboard
views: `company_dashboard.php`, `staff_dashboard.php`, `super_admin_dashboard.php`,
`clients_dashboard.php`.
**Scope:**
- A1. Company: tighten the KPI row (Employees/Wallet/Present/Pending) into one consistent
  card system; group the money panels (Deposit/Invoices/Payroll/Expenses) under a "Finance
  at a glance" band; make Projects/Tasks status cards conditional on plan (don't render empty
  boxes when the module is locked — they currently show "0" boxes even when Projects is locked).
- A2. Staff: the ESS dashboard is good but long — collapse into: top action row (Clock in,
  Apply Leave, My Profile), a 2-col band (Attendance summary | Leave balance), then a
  collapsible "More" (Holidays, Training, Goals, Assets, Awards, Documents). Hide tiles whose
  target the staff can't open (see Phase B/authz — several tiles 307 today: Assets, Awards,
  Documents, Training, Announcements for a bare role).
- A3. Consistent design tokens: one card radius/shadow/spacing scale; verify no off-scale
  values; both light/dark.
- A4. Verify each of the 4 dashboards at 1280/768/390 for overflow + a neat grid.
**Dependency:** none. Can start immediately.

## PHASE B — Leave engine: rules + tenant config (partially exists)
**Current state (verified):** leave application ALREADY deducts dynamically — `Leave.php`
computes `rem_leave = days_per_year(field_one) - taken` and rejects requests exceeding the
remaining annual quota, and blocks when quota is 0. Leave types live in `ci_erp_constants`
(type=leave_type, `field_one`=days/year). Staff dashboard shows per-type assigned/remaining.
**Gaps to build:**
- B1. **Max-days-per-single-application** rule ("only 10 at a time / 21"). Add a per-leave-type
  field (`field_two` is free — repurpose as `max_per_request`, or add a column). Enforce in
  `Leave.php` application validation: `if ($no_of_days > $max_per_request) reject`.
- B2. **Tenant-admin leave config UI**: a page for the company admin to set, per leave type,
  the annual assigned days AND the max-per-request cap (and half-day allowed?). The leave-type
  CRUD exists (Types controller / erp/leave-type) — extend its form + save with the new field.
  Ensure NO hardcoded 10/21 anywhere — all from config.
- B3. **Accrual option** (optional/stretch): monthly accrual vs annual grant, per type.
- B4. Verify end-to-end as a seeded staff: apply within cap (ok), exceed cap (rejected with the
  configured number), exceed remaining (rejected), balance updates on approval.
**Dependency:** none functionally; pairs well with Phase A staff dashboard.

## PHASE C — Zero-hardcoding audit (system-wide)
**Done already:** currency (erp_currency/erp_currency_symbol, tenant-first), employee-ID
prefix (per-tenant), currency symbols, nav labels (lang file). 
**Still to sweep — find & replace hardcoded values with settings/config/lang:**
- C1. Numeric limits: any literal leave/attendance/payroll thresholds, page sizes, seat caps
  not read from settings (grep for magic numbers in controllers).
- C2. Dates/timezones: any hardcoded year (`2026`, `2021`), `Africa/Kampala` literal (should be
  `system_timezone`), date formats not using `date_format_xi`.
- C3. Brand/company literals shown to tenants (should be `system_setting('application_name')`
  or tenant name) — re-run the earlier zero-hardcoding grep since new code landed.
- C4. Country/phone/tax literals (256 dialing code, PAYE/NSSF bands — confirm all from
  `ci_paye_bands` + settings, none inline).
- C5. Status strings / enums hardcoded in views vs a shared constant/lang.
- C6. URLs/hosts (localhost, VPS IP) outside config/env.
**Method:** grep sweeps per category → per-hit decision (config vs lang vs leave) → fix →
re-verify. This is the biggest sweep; run as its own session with a checklist.

## PHASE D — Full non-nav system audit (extend P3–P8)
The mandate audit this session focused on navigation + role authz. Extend to the rest:
- D1. Per-controller authz matrix for ALL ~78 controllers × 4 roles (this session probed 11
  representative routes; D-AUTHZ-01 found + fixed). Systematically probe every protected route.
- D2. The `add_holiday`/create endpoints authz: `Holidays::add_holiday` had no explicit
  resource gate at the method head (view hides the button, but the POST endpoint should also
  server-gate on holiday2/company). Sweep all `add_*/update_*/delete_*` for method-level gates.
- D3. IDOR sweep per resource (change an id, another tenant's object) — the earlier security
  backlog covered reads; re-verify writes after this session's changes.
- D4. Layout forensics (overflow/overlap/contrast) across the top ~20 routes × 3 viewports.
- D5. Console-error sweep across every route × role.

## PHASE E — Ship
- E1. Merge `audit/full-system-20260806` → main (owner decision; standing pref is work-on-main).
- E2. Delete remaining old `hr_*` docker volumes (owner-run; hr_redis_data already gone).
- E3. Phase 3 Traefik deploy (blocked on domain purchase — see hr-deploy-readiness).

---

## Open defects/notes carried forward
- **D-HOLIDAY-01** (Low, noted not fixed): `Holidays::add_holiday` POST lacks a method-level
  resource gate (view hides the control; endpoint relies on that). Fix in Phase D2.
- Staff self-service pages over-gated on ADMIN role-resources (payslips, expenses, documents,
  assets, awards, training, announcements all 307 for a bare role) — the dashboard advertises
  them as tiles → dead clicks. Decide per page: personal ESS should be self-accessible
  (ungate view like Holidays was) vs genuinely admin-only. Phase A2 + B.
