<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Register the PesaPal IPN URL and persist the returned ipn_id (ROADMAP F2).
 *
 * SubmitOrderRequest requires a registered IPN id, so this must run once (per
 * environment) after the consumer key/secret are configured in Super Settings →
 * PesaPal. Re-running simply registers again and overwrites the stored id.
 *
 * Usage:   php spark pesapal:setup [ipn_url]
 *          (ipn_url defaults to <site>/api/v1/webhooks/pesapal)
 */
class PesapalSetup extends BaseCommand
{
    protected $group       = 'Rooibok';
    protected $name        = 'pesapal:setup';
    protected $description = 'Register the PesaPal IPN URL and store the returned ipn_id in settings';

    public function run(array $params)
    {
        helper('main');

        $pesapal = new \App\Libraries\Collections\PesapalCollections();
        if (! $pesapal->isConfigured()) {
            CLI::error('PesaPal consumer key/secret not configured. Set them in Super Settings → PesaPal first.');
            return 1;
        }

        $url = $params[0] ?? rtrim(site_url('api/v1/webhooks/pesapal'), '/');
        CLI::write('Registering IPN URL: ' . $url, 'yellow');

        $res = $pesapal->registerIpn($url, 'POST');
        if (! ($res['ok'] ?? false)) {
            CLI::error('RegisterIPN failed: ' . ($res['reason'] ?? 'unknown'));
            return 1;
        }

        $ipnId = (string) $res['ipn_id'];
        \Config\Database::connect()->table('ci_erp_settings')
            ->where('setting_id', 1)
            ->update(['pesapal_ipn_id' => $ipnId]);
        clear_system_cache();

        CLI::write('IPN registered. ipn_id = ' . $ipnId, 'green');
        CLI::write('Saved to settings — wallet top-ups will now use this notification id.');
        return 0;
    }
}
