<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Refresh cached FX rates from the trusted source. Run daily from cron:
 *   php spark fx:refresh
 */
class FxRefresh extends BaseCommand
{
    protected $group       = 'Rooibok';
    protected $name        = 'fx:refresh';
    protected $description = 'Fetch and cache the latest foreign-exchange rates (trusted daily source).';

    public function run(array $params)
    {
        helper('main');
        $fx = new \App\Libraries\FxRates();
        $ok = $fx->refresh();
        if ($ok) {
            CLI::write('FX rates refreshed (age now ' . round((float) $fx->ratesAgeHours(), 2) . 'h).', 'green');
        } else {
            CLI::error('FX refresh failed — the last cached rates remain in use.');
        }
    }
}
