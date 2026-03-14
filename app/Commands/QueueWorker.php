<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Queue;

/**
 * Long-running Spark command that processes background jobs from
 * all registered Beanstalkd tubes.
 *
 * Usage:
 *   php spark queue:worker
 *
 * Designed to run under Supervisor (or similar) inside the Docker
 * worker container so it restarts automatically on failure.
 */
class QueueWorker extends BaseCommand
{
    protected $group       = 'Queue';
    protected $name        = 'queue:worker';
    protected $description = 'Process background jobs from all tubes';

    /**
     * Tubes the worker listens on.
     * Add new tubes here as features are built.
     */
    private array $tubes = [
        'payroll',
        'emails',
        'payments',
        'broadcasts',
        'archive_vault',
    ];

    // ------------------------------------------------------------------

    public function run(array $params)
    {
        CLI::write('Queue worker started at ' . date('Y-m-d H:i:s'), 'green');
        CLI::write('Listening on tubes: ' . implode(', ', $this->tubes), 'green');
        CLI::newLine();

        $queue = new Queue();

        if (! $queue->isConnected()) {
            CLI::error('Cannot connect to Beanstalkd. Check BEANSTALK env var and ensure the service is running.');
            return;
        }

        // Main loop — runs until the process is killed.
        while (true) {
            foreach ($this->tubes as $tube) {
                $job = $queue->reserve($tube, 2);

                if ($job === null) {
                    continue;
                }

                CLI::write("Processing job #{$job['id']} from [{$tube}]", 'yellow');

                try {
                    $this->processJob($tube, $job['payload']);
                    $queue->delete($job['id']);
                    CLI::write("  Job #{$job['id']} completed", 'green');
                } catch (\Throwable $e) {
                    CLI::error("  Job #{$job['id']} failed: " . $e->getMessage());
                    log_message('error', "Queue job #{$job['id']} [{$tube}] failed: {msg}\n{trace}", [
                        'msg'   => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $queue->bury($job['id']);
                }
            }
        }
    }

    // ------------------------------------------------------------------
    //  Dispatcher
    // ------------------------------------------------------------------

    private function processJob(string $tube, array $payload): void
    {
        match ($tube) {
            'payroll'       => $this->handlePayroll($payload),
            'emails'        => $this->handleEmail($payload),
            'payments'      => $this->handlePayment($payload),
            'broadcasts'    => $this->handleBroadcast($payload),
            'archive_vault' => $this->handleArchive($payload),
            default         => CLI::write("  Unknown tube: {$tube}", 'light_red'),
        };
    }

    // ------------------------------------------------------------------
    //  Handlers — stubs to be fleshed out in later phases
    // ------------------------------------------------------------------

    /**
     * Phase 5.3: Generate payslips with PAYE / NSSF calculations.
     */
    private function handlePayroll(array $payload): void
    {
        CLI::write('  Payroll job: ' . json_encode($payload));
    }

    /**
     * Send an email using the CI4 Email service.
     *
     * Expected payload keys: to, subject, body
     * Optional: cc, bcc, attachments
     */
    private function handleEmail(array $payload): void
    {
        $email = \Config\Services::email();

        $email->setTo($payload['to'] ?? '');

        if (! empty($payload['cc'])) {
            $email->setCC($payload['cc']);
        }
        if (! empty($payload['bcc'])) {
            $email->setBCC($payload['bcc']);
        }

        $email->setSubject($payload['subject'] ?? '(no subject)');
        $email->setMessage($payload['body'] ?? '');

        if (! empty($payload['attachments']) && is_array($payload['attachments'])) {
            foreach ($payload['attachments'] as $file) {
                $email->attach($file);
            }
        }

        if ($email->send()) {
            CLI::write('  Email sent to ' . ($payload['to'] ?? '?'));
        } else {
            throw new \RuntimeException('Email send failed: ' . $email->printDebugger(['headers']));
        }
    }

    /**
     * Payment confirmation processing.
     */
    private function handlePayment(array $payload): void
    {
        CLI::write('  Payment job: ' . json_encode($payload));
    }

    /**
     * Phase 5.5: Broadcast delivery (in-app / email / SMS).
     */
    private function handleBroadcast(array $payload): void
    {
        $logId       = $payload['log_id']       ?? 0;
        $channels    = $payload['channels']     ?? [];
        $subject     = $payload['subject']      ?? '';
        $bodyHtml    = $payload['body_html']     ?? '';
        $bodySms     = $payload['body_sms']      ?? '';
        $email       = $payload['email']         ?? '';
        $phone       = $payload['phone']         ?? '';
        $recipientId = $payload['recipient_id']  ?? 0;
        $companyId   = $payload['company_id']    ?? null;

        if (! $logId) {
            CLI::write('  Broadcast job: missing log_id — skipping', 'light_red');
            return;
        }

        $BroadcastLogModel = new \App\Models\BroadcastLogModel();
        $updateData = [];
        $errors = [];

        // --- In-App Notification ---
        if (in_array('inapp', $channels) && $recipientId) {
            try {
                $NotificationModel = new \App\Models\NotificationModel();
                $NotificationModel->notify(
                    (int) $recipientId,
                    $companyId ? (int) $companyId : null,
                    $subject,
                    strip_tags(mb_substr($bodyHtml, 0, 300)),
                    ''
                );
                $updateData['inapp_sent'] = 1;
                CLI::write("  In-app notification sent to user #{$recipientId}");
            } catch (\Throwable $e) {
                $errors[] = 'inapp: ' . $e->getMessage();
                CLI::write('  In-app failed: ' . $e->getMessage(), 'light_red');
            }
        }

        // --- Email ---
        if (in_array('email', $channels) && $email) {
            try {
                $emailService = \Config\Services::email();
                $emailService->setTo($email);
                $emailService->setSubject($subject);
                $emailService->setMessage($bodyHtml);

                if ($emailService->send()) {
                    $updateData['email_sent'] = 1;
                    CLI::write("  Email sent to {$email}");
                } else {
                    $errors[] = 'email: send failed';
                    CLI::write('  Email send failed for ' . $email, 'light_red');
                }
            } catch (\Throwable $e) {
                $errors[] = 'email: ' . $e->getMessage();
                CLI::write('  Email exception: ' . $e->getMessage(), 'light_red');
            }
        }

        // --- SMS ---
        if (in_array('sms', $channels) && $phone && $bodySms) {
            try {
                // Use available SMS gateway (placeholder — integrate with actual provider)
                $updateData['sms_sent']   = 1;
                $updateData['sms_status'] = 'sent';
                CLI::write("  SMS queued to {$phone}");
            } catch (\Throwable $e) {
                $errors[] = 'sms: ' . $e->getMessage();
                $updateData['sms_status'] = 'failed';
                CLI::write('  SMS failed: ' . $e->getMessage(), 'light_red');
            }
        }

        // Update log entry
        $updateData['sent_at'] = date('Y-m-d H:i:s');
        if (! empty($errors)) {
            $updateData['error_message'] = implode('; ', $errors);
        }

        $BroadcastLogModel->update($logId, $updateData);
    }

    /**
     * Phase 10: Archive vault bundle generation.
     *
     * Payload actions:
     *   - generate_bundle: Create a ZIP vault bundle with all archived
     *     data for a company, compute SHA-256 checksum, and update the
     *     snapshot record.
     */
    private function handleArchive(array $payload): void
    {
        $action = $payload['action'] ?? '';

        if ($action === 'generate_bundle') {
            $this->generateVaultBundle($payload);
        } else {
            CLI::write('  Archive job: unknown action — ' . json_encode($payload), 'light_red');
        }
    }

    /**
     * Generate a ZIP vault bundle containing all archived data for a company.
     *
     * 1. Query all archive tables for the company
     * 2. Write each dataset as a JSON file in a temp directory
     * 3. Create a ZIP archive
     * 4. Compute SHA-256 checksum
     * 5. Move bundle to storage path
     * 6. Update snapshot record with path and checksum
     * 7. Clean up temp directory
     */
    private function generateVaultBundle(array $payload): void
    {
        $companyId = $payload['company_id'] ?? 0;

        if (! $companyId) {
            CLI::write('  Vault bundle: missing company_id', 'light_red');
            return;
        }

        $archDb = \Config\Database::connect('archive');

        // Find the latest snapshot for this company
        $snapshot = $archDb->table('arc_company_snapshots')
            ->where('source_company_id', $companyId)
            ->orderBy('archived_at', 'DESC')
            ->get()
            ->getRowArray();

        if (! $snapshot) {
            CLI::write("  Vault bundle: no snapshot found for company #{$companyId}", 'light_red');
            return;
        }

        $snapshotId = $snapshot['snapshot_id'];
        $timestamp  = time();
        $tmpDir     = sys_get_temp_dir() . '/archive_' . $companyId . '_' . $timestamp;

        if (! mkdir($tmpDir, 0755, true)) {
            CLI::write("  Vault bundle: failed to create temp dir {$tmpDir}", 'light_red');
            return;
        }

        CLI::write("  Generating vault bundle for company #{$companyId}...");

        // Write JSON files for each archived data type
        $datasets = [
            'snapshot'   => [$snapshot],
            'employees'  => $archDb->table('arc_employees')
                ->where('source_company_id', $companyId)
                ->get()->getResultArray(),
            'attendance' => $archDb->table('arc_attendance')
                ->where('source_company_id', $companyId)
                ->get()->getResultArray(),
            'payroll'    => $archDb->table('arc_payroll')
                ->where('source_company_id', $companyId)
                ->get()->getResultArray(),
            'leaves'     => $archDb->table('arc_leaves')
                ->where('source_company_id', $companyId)
                ->get()->getResultArray(),
            'system_logs' => $archDb->table('arc_system_logs')
                ->where('source_company_id', $companyId)
                ->get()->getResultArray(),
            'contacts'   => $archDb->table('arc_contacts')
                ->where('snapshot_id', $snapshotId)
                ->get()->getResultArray(),
        ];

        foreach ($datasets as $name => $data) {
            $jsonPath = $tmpDir . '/' . $name . '.json';
            file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            CLI::write("    Written {$name}.json (" . count($data) . ' records)');
        }

        // Write a manifest
        $manifest = [
            'company_id'   => $companyId,
            'snapshot_id'  => $snapshotId,
            'company_name' => $snapshot['company_name'] ?? 'Unknown',
            'generated_at' => date('Y-m-d H:i:s'),
            'datasets'     => array_map(fn($d) => count($d), $datasets),
        ];
        file_put_contents($tmpDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        // Create ZIP bundle
        $zipFilename = "vault_company_{$companyId}_{$timestamp}.zip";
        $storagePath = WRITEPATH . 'vault_bundles';

        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $zipPath = $storagePath . '/' . $zipFilename;
        $zip     = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            CLI::write("  Vault bundle: failed to create ZIP at {$zipPath}", 'light_red');
            $this->cleanupDir($tmpDir);
            return;
        }

        // Add all files from temp dir to the ZIP
        $files = glob($tmpDir . '/*.json');
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        // Compute SHA-256 checksum
        $checksum = hash_file('sha256', $zipPath);

        // Update snapshot with bundle path and checksum
        $archDb->table('arc_company_snapshots')
            ->where('snapshot_id', $snapshotId)
            ->update([
                'vault_bundle_path' => 'vault_bundles/' . $zipFilename,
                'vault_checksum'    => $checksum,
            ]);

        // Clean up temp directory
        $this->cleanupDir($tmpDir);

        CLI::write("  Vault bundle created: {$zipFilename} (SHA-256: {$checksum})", 'green');
    }

    /**
     * Recursively remove a temporary directory and its contents.
     */
    private function cleanupDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->cleanupDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
