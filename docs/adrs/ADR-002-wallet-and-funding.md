# ADR-002 — Disbursement float: wallet & funding model

- Status: Accepted
- Date: 2026-08-01
- Author: Bodo Desderio
- Builds on: ADR-001 (disbursement engine), ROADMAP F2

## Context

The disbursement engine (ADR-001) records *instructions and outcomes* but has no
concept of the **source of funds**. Mobile-money payouts debit a prefunded
provider float; the app never physically holds cash. We must decide whose float
funds payouts and how companies fund it — a decision with **regulatory** weight
in Uganda (National Payment Systems Act 2020: pooling and transmitting customer
funds requires a Bank of Uganda PSP/e-money licence).

## Decision

**Aggregator-backed pooled wallet with an internal per-company ledger (Model C).**

- The actual float sits in a **licensed aggregator** (recommended: Flutterwave;
  Pesapal/Beyonic as fallbacks) under **one master Rooibok merchant account**.
  The aggregator holds the licence and the cash — Rooibok does not run a bank
  account of pooled customer funds.
- Each company has a **virtual wallet** (a ledger balance) in this app.
  Companies **top themselves up** via the aggregator's collection API
  (MoMo/card/bank) with no Rooibok involvement; the top-up credits their virtual
  balance.
- **Payouts** debit the company's virtual balance plus a **transaction fee**; the
  aggregator moves the real money from the master float to the employee.
- **Rooibok super admin** has **read-only** oversight of every company's balance,
  transactions, and the master-float reconciliation.
- Rejected: per-company BYO gateway credentials (Model A) — too much onboarding
  friction. Rejected: Rooibok running its own pooled bank account (Model B) —
  requires a BoU PSP/e-money licence.

### Wallet & ledger

- `ci_company_wallets` — one per company: `balance` (available) + `reserved`
  (held for in-flight payouts) + currency + status.
- `ci_wallet_transactions` — append-only, signed-amount ledger with
  `balance_after`; types: `topup, payout, fee, reserve, release, reversal,
  adjustment`. Every entry links to its disbursement/top-up reference.
- Balance is authoritative from the ledger; reconciled against the aggregator's
  reported master balance.

### Money-movement discipline

- `process()` computes `amount + fee` per line, **reserves** it (available →
  reserved) and refuses lines with insufficient funds — no overspend, no
  double-spend across concurrent batches.
- `applyTerminal(successful)` **settles** the reserve (funds gone) and posts the
  `payout` + `fee` ledger entries.
- `applyTerminal(failed)` **releases** the reserve back to available.
- Wallet mutations are advisory-locked per wallet, atomic, and audited (F1).

### Fees

- Per-payout fee = flat + percentage (system settings), recorded as its own
  `fee` ledger entry. Covers the aggregator cost and an optional Rooibok margin —
  the product's money-movement revenue line.

## Consequences

- (+) Delivers the desired UX (central, self-serve top-up, pooled, per-company
  visibility, Rooibok read-only) without Rooibok pursuing its own PSP licence.
- (+) The ledger/fee/reserve layer is **aggregator-agnostic** — Flutterwave is
  one driver behind the existing `DisbursementProviderInterface`; swapping
  aggregators does not touch the wallet.
- (−) Dependency on the aggregator (fees, uptime, ToS). Verify with the
  aggregator/counsel that an internal ledger over one master account is within
  their terms (their sub-account/virtual-account products are designed for it).
- (−) Reconciliation between the internal ledger and the aggregator's balance is
  an ongoing operational task.

## Build order (keyless parts first)

1. Wallet + ledger tables, `WalletService` (credit/debit/reserve/release/settle).
2. Fee model + engine integration (balance-checked reserve → settle/release).
3. Flutterwave driver: collections (top-up) + payouts + balance.
4. Top-up flow + webhooks; wallet UI (company self-service + Rooibok read-only).
