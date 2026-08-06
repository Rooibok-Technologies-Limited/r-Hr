<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# Currency Conversion — auto-updated trusted FX rates (2026-08-06)

## Built
- **`App\Libraries\FxRates`** — fetches daily rates from `open.er-api.com` (exchangerate-api's
  free, no-key endpoint; central-bank + commercial sourced), caches them in **ci_fx_rates**
  (migration ...000004) with a fetch timestamp. NEVER manually-entered rates.
- **`fx_convert($amount,$from,$to)`** + **`fx_rate($from,$to)`** helpers. Cross-rates via a USD base.
- **`php spark fx:refresh`** — the daily refresh command (add to cron). The service also
  lazily fetches if the cache is empty, and falls back to the last good cache if the source
  is unreachable (ratesAgeHours() exposes staleness). Never blocks a web request.
- Verified live: 1M UGX → 269.94 USD → 233.68 EUR; GBP→EUR→GBP round-trips to 500.00 exactly.

## Deploy: schedule the daily refresh
Add to the host/container cron (rates change daily):
```
0 6 * * *  docker exec rhr_app php /var/www/html/spark fx:refresh
```

## IMPORTANT design decision (payroll correctness) — needs owner intent
The rates + convert helper are ready. WHERE conversion is applied is a real decision:

1. **Display/reporting conversion (safe, recommended default):** amounts are STORED in the
   tenant's operating currency; when someone views them in another currency, convert for
   display only. The stored contractual figures never change.
2. **Operating-currency switch = one-time restatement (deliberate, confirmed):** if a tenant
   changes their operating currency, convert their stored money columns ONCE at the current
   rate, record the rate + timestamp, behind a typed confirmation + audit entry.

**Do NOT auto-convert stored salaries on every page load.** A salary is a contractual amount
in a currency (e.g. 2,000,000 UGX). Re-converting it daily by FX would make an employee's pay
fluctuate — wrong for payroll. That is why switching currency today only relabels the display
symbol. The safe path is (1) for viewing and (2) as an explicit, audited migration.

## Wiring status
- Foundation (rates + helper): DONE.
- Applying conversion to a currency-switch restatement across money columns (salaries,
  allowances, expenses, invoices, wallet, disbursements): NOT wired — it is a data migration
  that needs the owner's intent (display-only vs restate) + a confirmation/audit flow. Ready
  to build once that decision is made.

## IMPLEMENTED — display-only model (owner chose this 2026-08-06)
- **base_currency** per tenant (migration ...000005; seeded = current default_currency so no
  data is re-interpreted). Money is STORED in base_currency (fixed, contractual).
- **default_currency** is now the DISPLAY preference (changeable in my-profile).
- Helpers: `erp_base_currency()`, `money_convert_display($amount)`, `money_fmt($amount)` —
  convert base→display via trusted FX, format with the display symbol + its decimals. No-op
  when base == display (the default).
- **Wired**: company dashboard money figures (9) now use money_fmt → real conversion.
  Verified: Acme (base GBP) with display EUR shows £1,000 stored as **€1,165.83** (live rate),
  stored value unchanged.
- **Remaining (mechanical follow-up)**: migrate the other ~350 `number_to_currency($x,
  erp_currency())` sites to `money_fmt($x)` so the whole app converts for display (not just the
  dashboard). Same one-line swap; can be swept per-controller/view.
