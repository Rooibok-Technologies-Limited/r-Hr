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
