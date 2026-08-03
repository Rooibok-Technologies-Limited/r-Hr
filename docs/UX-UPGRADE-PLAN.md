<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# Rooibok HR — UI/UX & Feature Upgrade Plan

Status: proposed · Owner: Bodo Desderio · Informed by the 2026-08-03 full-site test.

The app is functionally broad (HRSALE/CI4 base + a modern F2 payments layer) but
the **presentation and delivery layers are dated and inconsistent**: a jQuery
3.5.1 / Bootstrap-4-era admin theme, per-user-type dashboards that drift, and a
deployment that ships broken default assets. This plan sequences fixes from
"make it solid" to "make it delightful", then adds feature depth.

---

## Findings from the live test (baseline)

| Area | Finding | Status |
|------|---------|--------|
| Landing JS | jQuery/waypoints 404 → **all** landing interactivity dead | ✅ fixed |
| Assets | `uploads_data` volume ships empty, shadows repo defaults (logos, avatars) | ⚠ seeded at runtime; needs a first-boot seed |
| Dashboards | one controller serves company & super dashboards; scripts assume super-only widgets | ✅ guarded (leave_status.js); pattern remains |
| Routing | expenses JS called method-name URLs; auto-routing is off | ✅ fixed; **audit every module for the same drift** |
| Consistency | favicon 404 app-wide, decorative theme images missing | ✅ fixed |
| Company views | `erp_company_settings()` null crashed every company page | ✅ fixed |

---

## Phase 0 — Stabilize delivery (1–2 days)
Make what exists reliable before restyling.
- **Seed defaults into the uploads volume on first boot** (entrypoint: if
  `public/uploads` empty, copy repo defaults). Kills the class of "missing logo/
  avatar" bugs permanently.
- **Route/JS contract audit**: grep every `module_scripts/*.js` URL against
  registered routes (auto-routing is off) — expenses was broken; verify the rest.
- **Asset pipeline**: pin front-end libs under version control (or a lockfile),
  add a cache-busting `?v=` on CSS/JS so deploys don't serve stale scripts.
- **CI smoke test**: a headless pass that loads every route and fails on a 500 or
  a console error (this test session, automated).

## Phase 1 — Design-system foundation (3–5 days)
One coherent visual language instead of the inherited theme.
- Adopt **design tokens** (color, spacing, radius, typography, shadow) as CSS
  variables; a single `theme.css` consumed by both landing and admin.
- **Dark mode** + WCAG-AA contrast; respect `prefers-color-scheme`.
- Rebuild shared shells: sidebar, top bar, page header/breadcrumbs, cards,
  tables, modals, empty states, toasts — as documented components.
- Replace ad-hoc DataTables styling with one consistent table component
  (sticky header, responsive, uniform actions column, real empty state).
- Standardize forms (React-Hook-Form-style validation UX on the server-rendered
  side: inline errors, disabled-until-valid, consistent buttons).

## Phase 2 — Dashboards & data-viz (3–4 days)
- Per-role dashboards done right: **company** (headcount, payroll run status,
  wallet balance, pending approvals, attendance today), **super-admin** (MRR,
  tenants, float reconciliation), **staff** (my payslip, leave balance, clock-in).
- Replace the scattered ApexCharts calls with a small chart wrapper that
  no-ops when its container is absent (generalize the leave_status.js guard).
- KPI tiles, sparklines, and a consistent chart palette (see the `dataviz` skill).

## Phase 3 — Core-HR UX depth (1–2 weeks)
- **Attendance**: live board + the kiosk/QR flow polished; geofenced/selfie
  clock-in (roadmap F9); bulk correction UI.
- **Payroll**: run wizard (period → preview → approve → disburse) tying payroll
  to the F2 wallet/disbursement engine in one guided flow.
- **Leave**: calendar view, balance widgets, approval inbox.
- **Employee 360**: profile with payout-methods panel (F2 phase-1 UI still
  pending), documents, contracts, attendance, payslips in one place.
- **Expenses (F4)**: finish the claim→approve→reimburse loop (the view-detail
  endpoint is currently missing) and route it through the disbursement engine.

## Phase 4 — Employee self-service PWA (roadmap F7, 1–2 weeks)
- Installable, offline-first PWA on the existing JWT API: payslips, leave/expense
  requests, disbursement status, clock-in. Push-ready via the notifier.

## Phase 5 — Feature/functionality upgrades
Pulled from `README.md` roadmap, prioritized by leverage:
- **F3 Uganda statutory** (PAYE/NSSF/LST) — correctness + filing exports.
- **F8 WhatsApp channel** on the notifier; **F13** document-expiry alerts.
- **F5 loans/advances** & **F6 multi-currency** on the F2 rails.
- **F14 staff ID cards** (QR-verified) — ties attendance + kiosk.
- **Global command palette** (Ctrl-K), saved filters, CSV/PDF export everywhere,
  audit-log surfacing on every record.

---

## Cross-cutting UX principles
- Every list has: search, filter, empty state, loading state, pagination.
- Every destructive action: confirm + audit (F1) + undo where possible.
- Every money action: show balance impact before confirm; never a raw form.
- Mobile-first: the workforce is on phones — kiosk, self-service, approvals.
- Accessibility: keyboard-navigable, labelled, AA contrast, reduced-motion.

## Suggested order
Phase 0 (stabilize) → Phase 1 (design system) → Phase 2 (dashboards) → then
Phase 3 core-HR and Phase 5 features in parallel tracks, with Phase 4 PWA once
the API surface (F7) is hardened.
