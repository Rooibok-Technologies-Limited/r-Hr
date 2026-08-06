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
