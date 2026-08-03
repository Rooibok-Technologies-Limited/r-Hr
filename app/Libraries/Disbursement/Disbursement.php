<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Disbursement;

/**
 * Disbursement manager — resolves the right provider for a payout method type
 * and degrades to NullDisbursement when the chosen gateway is not configured,
 * so callers never move money by accident in an unconfigured environment.
 *
 *   service('disbursement')->for('momo')->transfer([...]);
 */
class Disbursement
{
    /** @var array<string, DisbursementProviderInterface> */
    private array $cache = [];

    /**
     * Provider for a payout method type: momo → MTN, airtel → Airtel.
     * A configured driver is returned as-is; an unconfigured one degrades to
     * NullDisbursement. Unknown types also fall back to Null.
     */
    public function for(string $type): DisbursementProviderInterface
    {
        $type = strtolower($type);
        if (isset($this->cache[$type])) {
            return $this->cache[$type];
        }

        // Pooled-aggregator mode (ADR-002): when Flutterwave is selected and
        // configured, EVERY payout type flows through it (it fans out to the
        // right rail). Falls through to per-type direct drivers otherwise.
        helper('main');
        if ((system_setting('disbursement_aggregator') ?: '') === 'flutterwave') {
            $agg = new FlutterwaveDisbursement();
            if ($agg->isConfigured()) {
                return $this->cache[$type] = $agg;
            }
        }

        $driver = null;
        switch ($type) {
            case 'momo':
            case 'mtn':
                $driver = new MtnMomoDisbursement();
                break;
            case 'airtel':
                $driver = new AirtelMoneyDisbursement();
                break;
        }

        if ($driver === null || ! $driver->isConfigured()) {
            $driver = new NullDisbursement();
        }

        return $this->cache[$type] = $driver;
    }
}
