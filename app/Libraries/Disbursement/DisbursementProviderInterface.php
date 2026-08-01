<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Disbursement;

/**
 * Contract for an outbound money-movement provider (MoMo, Airtel, bank).
 *
 * Mirrors the SMS provider pattern: one driver per gateway, selected at
 * runtime, degrading to a no-op/sandbox driver when unconfigured. Every method
 * returns a plain array so callers never branch on driver type.
 */
interface DisbursementProviderInterface
{
    /** Machine name, e.g. 'mtn_momo', 'airtel', 'null'. */
    public function name(): string;

    /** True when live credentials are present; false ⇒ sandbox/no-op behaviour. */
    public function isConfigured(): bool;

    /**
     * Confirm the destination exists and return the registered account holder.
     *
     * @return array{ok: bool, name: ?string, reason: ?string}
     */
    public function validateAccountHolder(string $account): array;

    /**
     * Move money out. The caller MUST have generated and persisted $reference
     * (a UUID) BEFORE calling this, and MUST have an approval record — never
     * call transfer() straight from a webhook.
     *
     * @param array{reference:string, account:string, amount:int|float, currency:string, note?:string} $params
     * @return array{ok: bool, status: string, provider_txn_id: ?string, raw: mixed, reason: ?string}
     *         status ∈ {pending, successful, failed}
     */
    public function transfer(array $params): array;

    /**
     * Poll the authoritative status of a prior transfer (reconciliation).
     *
     * @return array{status: string, raw: mixed}  status ∈ {pending, successful, failed, unknown}
     */
    public function status(string $reference): array;
}
