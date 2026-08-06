<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# System Inventory

## Scale
- Tracked files: **3416** (`git ls-files | wc -l`). Documented exclusions from the
  code-surface enumeration: `system/` (CI4 framework vendor, ~2400 files, not authored),
  `public/assets/` (vendored CSS/JS/fonts), `writable/` (runtime). Authored app surface =
  `app/` (1077 PHP) + `public/module_scripts/` (165 JS) + `docker/`, `docs/`, `tests/`.
- ERP controllers: 78 · Models: 107 · Views (app): 423 · JS modules: 165 · Migrations: 22.
- Route definitions: 982 lines in `app/Config/Routes.php`.

## Portals (sidebars) — the Phase-2 target
| Portal | user_type | Menu view | Lines | State |
|---|---|---|---|---|
| Team member | `staff` | `app/Views/default/staff_left_menu.php` | 352 | flat + role-gated, 4-level inventory nesting |
| Admin | `company` | `app/Views/default/company_left_menu.php` | 254 | **flat, 25+ top-level items, arbitrary order** |
| Super admin | `super_user` | `app/Views/default/super_users_left_menu.php` | 118 | grouped (7 captions) but flat items, own `sa_active()` |
| Client (out of mandate — D-003) | `customer` | `client_left_menu.php` | — | left as-is |

## Navigation defects found in the existing sidebars (→ DEFECTS.md)
- **D-N01** Company sidebar: 25+ ungrouped top-level items, no section headers, order arbitrary.
- **D-N02** Company sidebar: Inventory nests 4 levels (`Inventory → Products → product-list`),
  breaks the 3-tier rule.
- **D-N03** Org Chart duplicated: under Core HR (`erp/chart`) AND top-level (`erp/org-chart`)
  — two different routes, same concept.
- **D-N04** Finance concept scattered: Finance, Payroll, Payroll run, Wallet, Disbursements,
  Payout methods, Expenses, Invoices, Estimates, Subscription Invoices all as loose top-level
  siblings interleaved with unrelated items.
- **D-N05** Three sidebars hand-maintained separately → drift (staff shows Expenses twice:
  in Requests submenu as "Expense Claim" and again top-level as "Expenses"; company shows
  Finance twice-adjacent to Accounts).
- **D-N06** Labels inconsistent: hardcoded English literals ("Staff ID Cards", "Payroll run",
  "Wallet", "Disbursements", "Expenses", "Broadcasts", "Org Chart", "Live Attendance",
  "Subscription Invoices") mixed with `lang()` keys — un-localisable, inconsistent casing
  ("Payroll run" vs Title Case).
- **D-N07** No single source of truth: sidebar, (no) breadcrumbs, (no) command palette all
  absent or divergent; active-state via a bespoke `select_module_class()` map for company/staff
  and a separate `sa_active()` for super.
- **D-N08** Icon reuse: `credit-card` used for ID Cards, Finance, Wallet, Payment History
  (≥4 items share it) — violates one-icon-per-item.
- **D-N09** Orphaned/hidden routes reachable only by URL (not in any sidebar) — enumerated in
  NAVIGATION.md §orphans (e.g. `erp/competencies` present for company but gated oddly;
  `erp/payroll-run/list`, `erp/attendance-report`, report pages, `erp/feature-locked/*`).

## Authorisation surface (roles)
- `super_user` — platform owner; SuperAuth filter; sees System/company-management portal.
- `company` — tenant owner (company_id == own user_id); full tenant admin.
- `staff` — tenant employee; gated per-item by `staff_role_resource()` (resource keys like
  `attendance`, `leave1`, `expense1`, `product1`, …) stored on the staff role.
- `customer` — invoice client portal (out of mandate).
- Cross-cutting gates layered on top of role: `plan_allows($feature)` (plan tier:
  payroll/projects/recruitment/performance/training/inventory) and `setup_modules[$key]`
  (per-tenant module toggle). Both must pass where applied.

Full route/controller/model/flow enumeration is large; the navigation-relevant surface is
mapped in NAVIGATION.md. Non-nav code surface is audited by the Phase-3 static sweep and the
Phase-4 route×role probe (whole-population commands, see PROOF.md), not one ledger row per file.
