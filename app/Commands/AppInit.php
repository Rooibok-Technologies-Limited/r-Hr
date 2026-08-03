<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * First-boot / deploy initializer — closes the two deployment gaps found in the
 * 2026-08-03 site test:
 *   1. The `uploads_data` volume starts empty and shadows the repo's default
 *      assets (logos, favicon, default avatar) → seed them from docker/seed/uploads.
 *   2. The archive DB has no arc_* tables until docker/postgres/archive_schema.sql
 *      is loaded → create them if missing.
 *
 * Idempotent: existing files/tables are left untouched. Run as root so it can
 * write the (root-owned) uploads volume:
 *
 *   docker exec -u 0 rooibok_app php spark app:init
 *
 * (Run `php spark migrate` separately for schema migrations.)
 */
class AppInit extends BaseCommand
{
    protected $group       = 'Rooibok';
    protected $name        = 'app:init';
    protected $description  = 'Seed default upload assets + archive schema (idempotent).';

    public function run(array $params)
    {
        $this->seedUploads();
        $this->seedArchiveSchema();
        CLI::write('app:init complete.', 'green');
        return 0;
    }

    /** Copy any MISSING default assets from docker/seed/uploads into public/uploads. */
    private function seedUploads(): void
    {
        $src = ROOTPATH . 'docker/seed/uploads';
        $dst = FCPATH . 'uploads';
        if (! is_dir($src)) {
            CLI::write('uploads: no seed dir (' . $src . ') — skipped', 'yellow');
            return;
        }
        $copied = 0;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            $rel    = substr($item->getPathname(), strlen($src) + 1);
            $target = $dst . '/' . $rel;
            if ($item->isDir()) {
                if (! is_dir($target)) {
                    @mkdir($target, 0775, true);
                }
            } elseif (! file_exists($target)) {
                @mkdir(dirname($target), 0775, true);
                if (@copy($item->getPathname(), $target)) {
                    @chown($target, 1000);
                    @chgrp($target, 1000);
                    $copied++;
                }
            }
        }
        CLI::write("uploads: seeded {$copied} missing default asset(s).", 'green');
    }

    /** Create the arc_* tables from the schema file if they don't exist yet. */
    private function seedArchiveSchema(): void
    {
        try {
            $db = \Config\Database::connect('archive');
            if ($db->tableExists('arc_company_snapshots')) {
                CLI::write('archive: arc_* tables already present — skipped.', 'green');
                return;
            }
            $file = ROOTPATH . 'docker/postgres/archive_schema.sql';
            if (! is_file($file)) {
                CLI::write('archive: schema file missing (' . $file . ') — skipped', 'yellow');
                return;
            }
            $sql = (string) file_get_contents($file);
            // Execute statement-by-statement (strip line comments first).
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            $count = 0;
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                $db->query($stmt);
                $count++;
            }
            CLI::write("archive: created arc_* schema ({$count} statements).", 'green');
        } catch (\Throwable $e) {
            CLI::error('archive: ' . $e->getMessage());
        }
    }
}
