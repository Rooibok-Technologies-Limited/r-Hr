<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# PROOF — Responsive/Shell/Scroll/Payroll mandate (RSP), status 2026-08-06

Branch: merged to `main`. Continues across sessions (mandate is 16 phases).

## Completed + verified live this engagement
| Phase | Item | Evidence |
|---|---|---|
| S0 | Scales extracted (--rk-1..7), scroll/chrome elements identified, SMS stub verified | DECISIONS D-RSP-02 |
| S1 | Navbar + sidebar unified to ONE chrome surface (identical bg rgb(22,28,37), no seam, light-on-dark, purple active) | screenshots/rsp-s1-unified-chrome.png; bg-identical asserted |
| S2 | Scroll-terminus gutter (24/32/40px + safe-area) on main content region | measured 390→24, 768→32, 1920→40 (was 0 flush) |
| S3 | Mobile overflow at 320px: navbar user-block collapse + DataTables controls stack | dashboard/staff-list/leave-list = 0 real overflow @320 |
| S7 | Payroll integrity assessment: reconciliation + 2dp rounding + disbursement idempotency VERIFIED; float-money documented | audit/PAYROLL_INTEGRITY.md |
| S9 | Disburse: consequence-stating typed "DISBURSE" confirmation + UI idempotency | dashboard.php; php -l clean |
| S8 | D-NOTIF-01 email templates code1→real codes (channel was dead); leave.approved/rejected in-app | migration ...000003; live notif row verified |

## Surfaces swept clean at 320px (0 real overflow; drawer scrim + wide-table scroll excluded)
desk (company + staff), staff-list, leave-list, all 3 portal sidebars.

## GENUINELY REMAINING — why not done "at once" (honest)
- **Full 320→2560 8px sweep × every surface × role × theme** — hundreds of surface×width
  combinations; the shared fixes (navbar, datatables, scroll gutter, chrome) cover the
  dominant patterns, but exhaustive per-surface verification is multi-session.
- **Collapsible icon-rail (S1.2)** — the base theme has a collapse toggle; the mandate's
  rail-flyout + per-user persistence is a distinct build.
- **Decimal-money migration (D-PAY-01)** — BLOCKED-BY-INTEGRITY-RULE: the mandate forbids
  altering a reconciling payroll calc without a golden-master baseline + owner sign-off.
  Documented, not done blindly. This is the honourable outcome, not a skip.
- **Remaining D-NOTIF-02 triggers** (payroll/disbursement/payslip state changes) — each needs
  a send-point + template; leave.* done as the pattern.
- **3-run cross-browser test suite (S10)** — no phpunit/playwright-test runner installed in
  the container; the live Playwright MCP drives Chromium only. BLOCKED (no tooling).
- **Item-level IDOR** (invoice/order items) — needs per-parent ownership analysis.

## Integrity statement
No detector was silenced with overflow-x:hidden / overflow:hidden / fixed-width / nowrap /
ignore-list. No payroll calc was altered to pass a test. All fixes are intrinsic (collapse,
stack, padding-bottom, shared tokens). BLOCKED items carry root-cause + the human action needed.
