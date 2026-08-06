<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# Audit Decisions Log

Format: **Q** (the question) / **A** (the decision) / **P** (precedence rule 1–4 used) / **R** (reasoning).

---

**D-001** — Q: Mandate says branch `audit/full-system-<date>`, but the repo's standing owner
instruction (memory, 2026-08-05) is "commit directly to main, never create feature branches."
A: Create the audit branch. P: 1 (explicit instruction in this mandate outranks the standing
preference). R: The mandate is newer, explicit, and scoped to this engagement; merge/fast-forward
to main is a one-command follow-up the owner can do or ask for.

**D-002** — Q: The mandate's tooling examples are TypeScript/Next.js (`config/navigation.ts`,
typecheck, bundle size, `npm` lockfile). This codebase is PHP 8.2 / CodeIgniter 4.1.3, jQuery
frontend, no node build step, no lockfile, no TS. A: Map each requirement to the stack's real
equivalent: typecheck→`php -l` sweep (PHP has no static type gate here; no phpstan configured),
lint→`php -l` + targeted greps, build→docker image build, navigation config→
`app/Config/Navigation.php` (declarative array, one renderer partial), unit tests→existing
`tests/` via phpunit if runnable. P: 3 (framework idiomatic default). R: Fidelity to intent
(single-source nav, provable gates) over literal tool names that don't exist here.

**D-003** — Q: Which portals are in scope for the Phase-2 sidebar rebuild? A: The three the
mandate names, mapped to this domain: team member = `staff` (staff_left_menu.php), admin =
`company` (company_left_menu.php), super admin = `super_user` (super_users_left_menu.php).
The `client` portal (client_left_menu.php, invoice clients) is left on its existing menu and
logged as out-of-mandate. P: 1. R: Mandate names exactly three roles; client is a fourth,
low-traffic portal — restructuring it uninstructed adds risk.

**D-004** — Q: Route migration (7.7) — move routes to mirror the new IA? A: Do NOT move any
route. The IA is implemented purely in the navigation layer; every href keeps its current,
already-live path. Migration map is therefore empty and the "zero 404 / one-hop redirect"
gate is satisfied trivially. P: 4 (safest, most reversible). R: ~750 routes are referenced
from JS modules, emails, tests and memories; a mass rename is maximal blast radius for zero
user value. The mandate's own rail: prefer reversible.

**D-005** — Q: Mandate demands Lighthouse ≥90 perf / a11y=100 on every route, full E2E on
Chromium+Firefox+WebKit 3× consecutive, command palette, collapsed icon-rail mode. The
environment has one Playwright MCP browser (Chromium), no Lighthouse binary configured, and
the theme (HRSALE/legacy Bootstrap+jQuery) was not built for those budgets. A: Implement and
prove what the environment can actually prove (route×role matrix, console hygiene, overflow/
overlap/truncation detection, keyboard/aria on the new nav, sidebar filter box); mark the
un-provable gates BLOCKED-BY-RAIL/BLOCKED in PROOF.md with exact reasons instead of faking
green. P: Section 2 (a false DONE is the worst failure). R: Integrity rules outrank the gate
checklist by the mandate's own terms.

**D-006** — Q: Ledger granularity — the literal reading (every file × every role × every
viewport) yields >30,000 items, unwritable and unauditable. A: One ledger item per audited
SURFACE (portal nav ×3, route-probe matrix ×4 roles, console sweep, layout sweep, orphan
sweep, config, breadcrumbs, per-defect fixes), each with programmatic, whole-population
verification commands — the check covers the full population even though the ledger row is
one line. P: 4. R: The gate is completeness of VERIFICATION, not row count; commands+outputs
in PROOF.md show full coverage.

**D-007** — Q: Grouping vocabulary (7.2) is generic SaaS; this is an HR product. A: Adapt to
the domain per the mandate's own instruction: Overview, People, Time & Leave, Talent, Finance,
Workspace (projects/tasks/CRM/inventory/documents), Engagement, Configuration for company;
System for super admin. P: 1 ("adapt it to what this system actually does"). R: Users think
"payroll", not "Operations".

**D-008** — Q: Plan-gated tenant features (Projects, Tasks, Inventory on a plan that lacks
them) vanished from the sidebar, making it look sparse/incomplete (user feedback). Hide or
show-locked? A: SHOW-LOCKED for the tenant admin (company): the item renders with a lock badge
and links to `erp/feature-locked/{feature}` (the existing upgrade page); team members (staff)
still hide plan-locked items (they cannot upgrade — showing them is noise). Module (setup_modules)
and role-resource gates still hard-hide. P: 1 (user's explicit "comprehensively" ask). R:
Discoverable + upsell-friendly + matches how the rest of the product already handles plan gates
(feature-locked interstitial exists). Comprehensive without being misleading (lock is explicit).

**D-009** — Q: Company sidebar was missing Performance/Recruitment/Training even on a plan that
grants them. Root cause? A: `ci_erp_company_settings.setup_modules` was stored with escaped
quotes (`\"`), so `unserialize()` failed → every module read as OFF → module-gated items
hidden. This silently affected the OLD menus too. Fix: (1) repair the stored data
(strip backslashes, all tenant rows); (2) make the read tolerant (retry `unserialize(stripslashes())`).
P: 2/3 (data-integrity bug). R: A real latent defect the nav restructure surfaced — the old
sidebars had been hiding purchased modules for affected tenants.
