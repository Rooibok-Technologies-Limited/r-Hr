<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Collections;

/**
 * Collections resolver — picks the configured wallet-funding gateway (ADR-002).
 *
 * `collections_provider` chooses explicitly ('flutterwave' | 'pesapal'); absent
 * or unconfigured falls back to whichever gateway has live credentials
 * (Flutterwave preferred for back-compat). An unconfigured provider is still
 * returned so callers degrade via isConfigured() === false rather than erroring.
 *
 *   service('collections')->initiate($companyId, $amount, [...]);
 */
class Collections
{
    public function provider(): CollectionsProviderInterface
    {
        helper('main');
        $choice = strtolower((string) (system_setting('collections_provider') ?: ''));

        $flutterwave = new \App\Libraries\FlutterwaveCollections();
        $pesapal     = new PesapalCollections();

        if ($choice === 'pesapal' && $pesapal->isConfigured()) {
            return $pesapal;
        }
        if ($choice === 'flutterwave' && $flutterwave->isConfigured()) {
            return $flutterwave;
        }

        // Auto: first configured wins.
        if ($flutterwave->isConfigured()) {
            return $flutterwave;
        }
        if ($pesapal->isConfigured()) {
            return $pesapal;
        }

        // Nothing configured — return the explicitly-chosen driver (or Flutterwave)
        // so isConfigured() === false and the caller shows "not configured".
        return $choice === 'pesapal' ? $pesapal : $flutterwave;
    }
}
