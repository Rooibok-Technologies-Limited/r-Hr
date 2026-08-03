<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Collections;

/**
 * Contract for an inbound money-movement (collections) provider — the company
 * wallet-funding side of ADR-002.
 *
 * A company funds its virtual wallet by paying the aggregator through a hosted
 * checkout (card / mobile money). initiate() creates the hosted payment and
 * returns a redirect link; verify() re-checks a transaction server-side (never
 * trust a webhook/IPN body for the amount or outcome) before the wallet is
 * credited. Drivers degrade to isConfigured() === false with no network calls
 * when unconfigured, so nothing is credited by accident.
 *
 * Every method returns a plain array so callers never branch on driver type.
 */
interface CollectionsProviderInterface
{
    /** Machine name, e.g. 'flutterwave', 'pesapal'. */
    public function name(): string;

    /** True when live credentials are present; false ⇒ no-op / unconfigured. */
    public function isConfigured(): bool;

    /**
     * Create a hosted top-up payment for a company. The returned `tx_ref` is our
     * idempotency key and MUST carry the company mapping so verify() can resolve
     * which wallet to credit.
     *
     * @return array{ok:bool, link?:string, tx_ref?:string, reason?:string}
     */
    public function initiate(int $companyId, float $amount, array $opts = []): array;

    /**
     * Authoritative server-side verification of a charge. Callers MUST use the
     * returned amount/currency/company_id — NEVER a webhook/IPN body's — to
     * decide what to credit. [SECURITY]
     *
     * @return array{ok:bool, status?:string, amount?:float, currency?:string, tx_ref?:string, company_id?:int, reason?:string}
     *         status ∈ {successful, failed, pending}
     */
    public function verify($transactionId): array;
}
