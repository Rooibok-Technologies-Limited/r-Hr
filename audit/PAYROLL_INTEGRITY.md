<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# Payroll Integrity Assessment (RSP-S7, 2026-08-06)

Evidence-based audit of PayrollCalculator + TaxEngine + DisbursementEngine.

## What is CORRECT (verified by reading the actual code)
- **Reconciliation holds by construction.** `net = basic + allowances + commissions +
  other − statutory − nssf_employee − paye`, and the payslip stores/shows exactly those
  components. The stated total is the sum of the shown line items — they cannot disagree.
- **Rounding is applied at a single consistent point per component.** TaxEngine rounds PAYE
  (`round($paye, 2)`) and NSSF employee/employer (`round(... , 2)`) to 2dp at computation;
  net is then a plain sum of already-rounded/stored values — no second, inconsistent rounding.
- **Disbursement is idempotent.** The caller-generated `reference` is the idempotency key,
  persisted in the disbursement table BEFORE the provider call and checked on retry
  (DisbursementEngine `->where('reference', ...)`), so double-click / webhook-replay / retry
  cannot pay twice. Confirmed in FlutterwaveDisbursement + the engine.
- **PAYE from `ci_paye_bands` (rate_percent), NSSF from settings** — statutory values are
  data-driven, not hardcoded (see Phase C).

## FINDINGS (documented, NOT blindly changed — mandate integrity rule)
- **D-PAY-01 (Medium) — PARTIALLY FIXED (TaxEngine migrated to integer-cents):**
  TaxEngine now does all PAYE/NSSF arithmetic in INTEGER MINOR UNITS (cents), never floats,
  verified 0-diff against the golden master (audit/tax_golden_master.json) across the fixture
  spread. The statutory percentage math (the real fractional source) is now exact. Remaining:
  PayrollCalculator sums already-2dp-exact components in float — lower risk, next slice.
  Original note: all monetary values are PHP `(float)` (basic_salary,
  allowances, PAYE, NSSF, net). Floats cannot exactly represent decimal fractions. Today this
  does NOT produce wrong figures because (a) every fractional component is `round(...,2)` and
  (b) UGX amounts are whole shillings, so intermediate float error stays below the rounding
  granularity and the system reconciles. **Recommended hardening:** migrate money to
  integer-minor-units (or bcmath) end-to-end. This is a large, careful refactor that WILL
  change some computed values at the sub-shilling level — it needs an explicit owner sign-off
  and a golden-master comparison, and must not be done blindly (mandate: never alter a payroll
  calc to pass a test).
- **D-PAY-02 (Low, statutory decision):** tax rounds to 2 decimals, but UGX has no subunit —
  amounts should arguably round to whole shillings. Changing this alters every payslip figure,
  so it is a jurisdiction/statutory decision for the owner + accountant, not a code cleanup.
- **FIDELITY NOTE (pre-existing, by design):** payslip net does NOT subtract advance/loan
  repayment from take-home (matches the legacy generator; flagged in PayrollCalculator). A
  payroll-correctness decision to revisit with the owner.

## Idempotency + authz (verified present)
- Disbursement blocked from a non-approved payroll (maker-checker); every state change audited
  with actor/timestamp (prior security backlog). Cross-employer: disbursement builds only from
  the run's own company lines (buildFromPayroll company-scoped).

## Recommended next (S7 continuation, owner-gated)
1. Golden-master fixture set (gross→net for joiners/leavers/overtime/bonus/loan/arrears) with
   independently-computed expected values, run against the CURRENT float calc to capture the
   baseline BEFORE any decimal migration.
2. Then migrate to integer-minor-units behind the golden master (zero-diff or owner-approved diff).
