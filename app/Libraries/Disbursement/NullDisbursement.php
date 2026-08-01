<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Disbursement;

/**
 * Safe sandbox / no-op disbursement driver.
 *
 * Used whenever a real gateway is unconfigured, so the payout-method and
 * verification flows are fully exercisable without moving money or holding
 * credentials. It never contacts a network and never reports a successful
 * transfer — transfers stay 'pending' so nothing is ever falsely marked paid.
 * Account-holder lookup returns ok with a null name (unverifiable offline), so
 * verification falls back to the one-time-code control check.
 */
class NullDisbursement implements DisbursementProviderInterface
{
    public function name(): string
    {
        return 'null';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function validateAccountHolder(string $account): array
    {
        return ['ok' => true, 'name' => null, 'reason' => 'sandbox: name lookup unavailable'];
    }

    public function transfer(array $params): array
    {
        return [
            'ok'              => false,
            'status'          => 'pending',
            'provider_txn_id' => null,
            'raw'             => null,
            'reason'          => 'sandbox: no gateway configured',
        ];
    }

    public function status(string $reference): array
    {
        return ['status' => 'unknown', 'raw' => null];
    }
}
