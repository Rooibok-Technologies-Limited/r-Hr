<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Reconciliation backstop for disbursements (ROADMAP F2, ADR-001).
 *
 * Provider callbacks are best-effort; this poll closes any line still PENDING by
 * asking the provider for its authoritative status. Idempotent — a line already
 * settled by a callback is a no-op.
 *
 * Usage:   php spark disbursements:reconcile [batch_id]
 * Cron:    every 10 minutes — "cd /var/www/html && php spark disbursements:reconcile"
 */
class DisbursementReconcile extends BaseCommand
{
    protected $group       = 'Disbursements';
    protected $name        = 'disbursements:reconcile';
    protected $description = 'Poll provider status for pending disbursements and settle them';

    public function run(array $params)
    {
        $batchId = isset($params[0]) && ctype_digit((string) $params[0]) ? (int) $params[0] : null;

        CLI::write('Reconciling ' . ($batchId ? "batch #{$batchId}" : 'all pending disbursements') . '…', 'yellow');
        $result = service('disbursementEngine')->reconcile($batchId);
        CLI::write("Checked {$result['checked']}, settled {$result['settled']}.", 'green');

        return 0;
    }
}
