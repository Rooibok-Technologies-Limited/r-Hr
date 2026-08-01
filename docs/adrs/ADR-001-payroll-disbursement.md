# ADR-001 — Payroll disbursement architecture

- Status: Proposed
- Date: 2026-08-01
- Author: Bodo Desderio
- Context feature: ROADMAP F2 (also underpins F4 expenses, F5 loans, F6 FX)

## Context

`ci_payslips` produces net pay but nothing moves money. Staff are paid primarily
by **mobile money (MTN MoMo, Airtel Money)** in Uganda, secondarily by **bank
transfer**. We must capture and **verify** payout destinations, move money
safely, and reconcile — for a multi-tenant SaaS where a bug pays the wrong person
or pays twice. This is the highest-risk feature in the roadmap.

## Decision

**1. Separate destination from disbursement.**
`ci_employee_payout_methods` (encrypted MSISDN/account) is verified independently
of any payment run. A method is *payable* only when `verified_at` is set. This
keeps verification state durable and reusable across payroll, expenses, and loans.

**2. Verification is mandatory and provider-anchored.**
- Mobile money: provider **account-holder name lookup** + a **micro-payout with
  code confirmation** (proves the number exists *and* is controlled by the payee).
- Bank: penny-drop/name-match where available, else **maker-checker** manual
  verification with evidence.
Rationale: name lookup alone can be spoofed by typos onto a real stranger; the
micro-payout proves control. Cost is one tiny transfer per method, one-time.

**3. Batch + maker-checker + dual control.**
A run is a `ci_disbursement_batches` row built from a payroll period, **prepared
by one user and approved by a different one** before any funds move. No single
actor can move money. Every state transition is written to the audit log (F1).

**4. Idempotency over retries.**
Each `ci_disbursements` row carries a **unique idempotency_key**; the provider
call is keyed on it. Retries and worker restarts can never double-pay. Status is
authoritative from **signed provider callbacks** (`Api/V1/Webhooks`) with a
**poll fallback** for missed callbacks.

**5. Reconcile against a ledger, daily.**
Every disbursement posts to a ledger; a daily job matches provider statements to
`ci_disbursements` and surfaces mismatches. Money state is never inferred from
the app alone.

**6. Sandbox-first, gated go-live.**
Build and demo entirely on MoMo/Airtel **sandbox**. Real credentials + caps
(per-run, per-day float checks) and the **KYC/go-live checklist** (payments
skill) gate the switch to production.

## Consequences

- (+) Wrong-payee and double-pay are structurally prevented (verification +
  idempotency + dual control), not merely tested against.
- (+) The engine is reused verbatim by expenses (F4), loans (F5), and FX (F6).
- (−) More moving parts than a naive "loop and send": verification adds a step
  and a tiny cost; maker-checker adds a role/approval; reconciliation is ongoing.
  Accepted — this is payroll money.
- (−) Requires provider onboarding/KYC before real value moves. Sequenced last.

## Alternatives considered

- **Direct send in the payroll controller** — rejected: no idempotency, no dual
  control, no reconciliation; a retry or crash double-pays.
- **CSV export to a bank/aggregator portal** — viable interim fallback and worth
  keeping as a manual escape hatch, but not the product: no status, no self-
  service, no audit trail.
- **Trust provider name lookup as sole verification** — rejected: doesn't prove
  control of the number; micro-payout confirmation closes that gap.
