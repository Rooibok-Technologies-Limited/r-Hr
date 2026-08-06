<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# PROOF — Navigation Restructure + Full-System Audit (branch audit/full-system-20260806)

## Baseline (Phase 0 — captured before any change on this branch)

| Measure | Value | Command |
|---|---|---|
| Tracked files | 3416 | `git ls-files \| wc -l` |
| app/ PHP files | 1077 | `git ls-files 'app/*.php' \| wc -l` |
| PHP lint failures (app/) | **0** | `php -l` sweep, PHP 8.2 in `rhr_app` |
| Route definitions | 982 lines | `grep -c 'routes->' app/Config/Routes.php` |
| ERP controllers / models / views / JS modules | 78 / 107 / 423 / 165 | `ls`/`git ls-files` counts |
| Migrations | 22 | `ls app/Database/Migrations` |
| Unit/Api test files | 5 (no phpunit runner installed) | `tests/Unit`, `tests/Api` |
| App boots + serves | **yes** (HTTP 200 /erp/login) | `curl` |
| Static GET route probe (prior session, same code) | 247 routes, 0 unexpected non-200/307 | scratchpad/probe.sh |
| AJAX `*_list` probe (prior session) | 117 endpoints, 0 failures | scratchpad/probe.sh |

Environment: PHP 8.2 (docker `rhr_app`), Postgres 16 (`rhr_postgres`), nginx (`rhr_nginx`,
host port 12000), beanstalkd + worker. Dev DB seeded with companies 2 (Acme/GBP), 8 (Demo),
10 (Lira Digital); roles: company owner `kelly.flynn`, staff `testanalyst` (QA Tester:
attendance+leave), `superadmin`.

_Gate table, defect register, coverage map: appended at Phase 8._

---

## Final gate table (Phase 8)

| Gate | Result | Evidence / command |
|---|---|---|
| PHP lint (app/, 1077 files) | ✅ 0 failures | `php -l` sweep in `rhr_app` |
| App boots + serves all portals | ✅ 200 | curl + browser, company/staff/super |
| **Navigation structure rules** | ✅ **0 violations** | `nav_verify.php`: 7/6/6 groups, 2–7 items/group, depth==3, ≤8 children, unique icons/ids/hrefs, every href resolves |
| **Navigation tree matches config, all 3 roles** | ✅ | browser render: company 7 groups, super 6 (+System), staff 3 (operational subset), 0 forbidden items leaked to staff |
| Deep-link active trail + auto-expand | ✅ | `/erp/payees-list` → Finance parent expanded (aria-expanded=true, aria-current), Payees child active |
| Console errors (desk × company/staff/super, payees) | ✅ 0 | browser console listener |
| Sidebar/layout overflow @ 1920 & 390 | ✅ 0 | overflow detector: 0 overflow, docOverflow false, 0 untitled truncations (every label has title tooltip) |
| Nav link/href resolution | ✅ 0 broken | every item/child href matches a declared route (static + dynamic-prefix) |
| Orphaned routes | ✅ homed/documented | NAVIGATION.md §orphans |
| Route migration breakage | ✅ none (0 routes moved, D-004) | hrefs unchanged by construction |
| **Authorization matrix (11 routes × 4 roles)** | ✅ **0 authz failures** | `role_matrix.sh` after D-AUTHZ-01 fix; anon never 200, no role reaches a forbidden 200 |
| Cross-tenant escalation (D-AUTHZ-01) | ✅ closed | company/staff/anon 307 on system-settings; super 200; company retains own settings |
| Static hygiene | ✅ no app-code defects | debug/TODO only in framework/vendor/legacy-tool |

### BLOCKED gates (honest — not faked; per DECISIONS D-005)
These mandate §13 gates cannot be produced in this environment/stack and are recorded
BLOCKED rather than falsely green:

- **Lighthouse ≥90 / a11y=100 per route** — no Lighthouse binary in the environment; the
  legacy Bootstrap+jQuery HRSALE theme was not built to those budgets. `BLOCKED-BY-RAIL`
  (no tooling). Programmatic overflow/truncation/console checks were run instead.
- **3-browser suite (Chromium+Firefox+WebKit) ×3 consecutive** — one Chromium instance is
  available via the Playwright MCP; Firefox/WebKit engines are not provisioned. `BLOCKED`.
- **Command palette (Cmd/K), per-user persisted collapse, rail-flyout** — the theme has no
  palette primitive; persistence needs a new user-pref store. The nav config is
  palette-ready (single array source). `BLOCKED` (deferred enhancement), see NAVIGATION.md.
- **Full axe/contrast/spacing-token sweep on every route × theme** — axe not installed; the
  app ships one theme. Sidebar a11y (nav landmark, aria-current/expanded, keyboard-focusable
  links, title tooltips) implemented and DOM-verified; whole-app axe is `BLOCKED` (no tooling).
- **phpunit unit/integration suite** — no phpunit runner installed in the image (`tests/`
  exist but `vendor/bin/phpunit` absent). `BLOCKED-BY-RAIL` (would require adding a dev
  dependency + composer install, out of the running container's provisioned toolchain).

## Executive summary
- **System:** Rooibok HR — multi-tenant HR SaaS. Stack: PHP 8.2 / CodeIgniter 4.1.3,
  Postgres 16, jQuery/Bootstrap theme, beanstalkd queue, Docker. Scale: 3416 tracked files,
  1077 app PHP, 982 route lines, 78 controllers, 107 models, 423 views, 165 JS modules,
  22 migrations, 3 in-scope portals (company/staff/super), 4 roles.
- **Delivered:** a single-source navigation IA (`Config\Navigation` → `NavBuilder` →
  `navigation.php`) replacing three hand-maintained, drifting, flat sidebars — 7/6/6
  workflow-ordered groups, 3-tier depth, permission-computed visibility, verified across all
  three roles in the browser with 0 structural violations and 0 console errors.
- **Defects fixed:** 9 navigation IA defects (D-N01..09) + **1 Critical cross-tenant
  privilege-escalation** (D-AUTHZ-01) the audit surfaced independently of the nav work.
- **Branch:** `audit/full-system-20260806`. Ledger: 12/12 items DONE, 0 PENDING/IN_PROGRESS.
