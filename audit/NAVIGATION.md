<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# Navigation Information Architecture — Before / After

Single source of truth: **`app/Config/Navigation.php`** → filtered by
**`app/Libraries/NavBuilder.php`** → rendered by **`app/Views/default/navigation.php`**.
The three portal menu views (`company_left_menu.php`, `staff_left_menu.php`,
`super_users_left_menu.php`) are now thin shims that build context and delegate.

## Before (defects — see INVENTORY.md D-N01..N09)
- **Company:** 25+ flat top-level items, no groups, arbitrary order, Inventory nested
  4 levels, Org Chart duplicated (`erp/chart` + `erp/org-chart`), Finance concept
  scattered across 10 loose siblings, mixed hardcoded/`lang()` labels.
- **Staff:** 352 lines, per-item `staff_role_resource()` gates hand-wired, Expenses
  shown twice, same items as company but divergently structured.
- **Super:** 7 flat caption groups, own `sa_active()` active-state, no sub-items.
- **No** breadcrumbs, command palette, or shared active-state rule. Three files drift.

## After — the tree (GROUP → ITEM → child, exactly 3 tiers)

### Tenant portal (company + staff, role/plan/module/resource filtered)
```
Overview        Dashboard · Live Attendance(admin) · ID Cards[All, Settings(admin)]
People          Employees · Recruitment · Clients · Leads · Org Chart ·
                Core HR[Departments, Designations, Policies, Announcements]
Time & Leave    Attendance[Log, Manual(admin), Monthly(admin), Overtime] · Leave ·
                Requests(staff)[Expense Claim, Loan, Travel, Advance Salary]
Talent          Performance[Indicators, Appraisals, Competencies, Goals, Goal Types,
                Calendar] · Training · Disciplinary
Finance         Finance[Accounts, Payees(admin), Payers(admin)] ·
                Payroll[List(admin), Payslips, Run, Payout Methods] ·
                Disbursements(admin)[Wallet, Disbursements] · Expenses · Invoices ·
                Estimates · Subscription(admin)
Workspace       Projects · Tasks · Inventory[Products, Out of Stock, Suppliers,
                Purchases, Sales Orders, Warehouses] · Support Tickets · Broadcasts(admin)
Configuration   Company Settings · My Profile          (admin only — group hidden for staff)
```

### Super-admin portal
```
Overview        Dashboard · Audit Log
People          Companies · Staff Users[All, User Roles]
Billing         Subscription Plans · All Invoices · Payment History · Company Wallets · Disbursements
Content         Landing Page CMS · Broadcasts[All, Templates]
Configuration   General Settings[General, Constants, Theme] · Templates[Email, SMS] ·
                Payment Gateways · Tax (PAYE/NSSF)
System          Database Backup · Archive Portal · API Documentation
```

## Rationale (affinity — grouped by the user's job to be done)
- **Finance** consolidates the 10 scattered money surfaces into 7 items; the payroll
  cluster (list/payslips/run/payout) lives under one navigable parent, wallet+
  disbursements under another — "everything I touch when I pay people" in one place.
- **Time & Leave** unifies attendance + leave + staff self-service requests (previously
  a top-level "Requests" submenu unrelated to attendance).
- **Org Chart** de-duplicated to the single canonical `erp/org-chart`, filed under People.
- **Talent** groups performance + training + disciplinary (career/development).
- Super-admin **System** group (super-only) isolates platform danger-zone (backup,
  archive, API) from tenant-facing config.

## Structural compliance (verified, 0 violations — PROOF.md nav-structure)
Groups/portal: company 7, staff 6, super 6 (≤7). Items/group 2–7. Children ≤8. Depth
exactly 3 (harness rejects any child with children). No duplicate item id, no duplicate
top-level href within a portal, no repeated icon within a portal. Every href resolves to
a declared route in `Routes.php` (static + dynamic-prefix match).

## Role visibility (rendered, browser-verified)
| Portal | Groups rendered | Notes |
|---|---|---|
| company (Acme, Pro plan) | Overview, People, Time & Leave, Talent, Finance, Workspace, Configuration | plan/module-gated items (recruitment/performance/projects/inventory) correctly absent when the tenant's plan/setup_modules don't grant them |
| super_user | Overview, People, Billing, Content, Configuration, System | System group present ONLY here |
| staff (QA Tester: attendance+leave) | Overview, Time & Leave, Finance | operational subset only; 0 admin-only items leaked; empty groups (People/Talent/Workspace/Config) hidden entirely |

Visibility is computed from permissions (`plan_allows`, `setup_modules`,
`staff_role_resource`), never a hardcoded role string — a custom staff role composes.
The sidebar is presentation only; server-side authorisation is independent (Phase 4).

## Route migration
**None (D-004).** The IA is implemented in the navigation layer; every href keeps its
existing live path. Migration map is empty → zero broken links, zero redirect hops,
by construction. ~750 route references in JS/emails/tests are therefore untouched.

## Previously orphaned routes — now homed or documented hidden
- `erp/manual-attendance`, `erp/monthly-attendance` → Attendance children (were reachable
  by URL / partially wired).
- `erp/payees-list`, `erp/payers-list` → Finance ▸ Finance children (shipped in this
  session's finance feature, never in a sidebar).
- `erp/business-travel`, `erp/advance-salary`, `erp/loan-request` → Time & Leave ▸ Requests.
- `erp/system-constants`, `erp/theme-settings`, `erp/system-tax-settings`,
  `erp/system-payment-settings` → super Configuration children.
- **Deliberately hidden** (reachable by direct link, intentionally not in nav): report
  pages (`erp/*-report`), `erp/feature-locked/*` (interstitial), `erp/renew` /
  `erp/subscription-locked` (billing walls), detail/edit routes (`*-details`,
  `create-*`, `read_*`) reached from their list pages, `verify/staff/*` (public).

## Behaviour delivered
Active-state: leaf exact-match, parent+group inherit; parent auto-expands on deep-link
(verified `/erp/payees-list` → Finance expanded, aria-expanded=true, aria-current). One
`<nav aria-label>` landmark, `aria-current="page"` on active, `aria-expanded` on
collapsible triggers, `title` tooltip on every label (long-label safety). Sidebar label
filter box at top. Collapse/expand + rail mode inherit the theme's existing sidebar JS
(unchanged class contract `.pc-navbar/.pc-item/.pc-submenu/.pc-hasmenu`).

## Deferred vs mandate §7.6 (honest scope — DECISIONS D-005)
Command palette (`Cmd/K`), per-user persisted collapse state, and rail-flyout are not
built: the legacy Bootstrap/jQuery theme has no palette primitive and persistence would
need a new user-pref store. The declarative config is palette-ready (one array to source
from). Logged BLOCKED in PROOF.md, not faked.
