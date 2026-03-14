<?php

namespace App\Libraries;

/**
 * Marketing intelligence extraction for the archive subsystem.
 *
 * Pulls company admin users and CRM-style client contacts into
 * arc_contacts in the archive database. Also generates tags for
 * segmentation (city, industry, plan tier, churn status, etc.).
 *
 * Called during:
 *   - Full company archiving (ArchiveCompany command)
 *   - Monthly extraction on active companies (via cron)
 */
class ContactExtractor
{
    /**
     * Extract contacts for a company into arc_contacts.
     *
     * Pulls the company admin plus any employee contacts that have
     * marketing consent, and inserts them into the archive database.
     *
     * @param int      $companyId  The live company ID.
     * @param int|null $snapshotId Optional snapshot ID to link contacts to.
     *
     * @return int Number of contacts extracted.
     */
    public function extractContacts(int $companyId, ?int $snapshotId = null): int
    {
        $liveDb = \Config\Database::connect('default');
        $archDb = \Config\Database::connect('archive');

        $count = 0;

        // Load snapshot for tag generation (use provided or latest)
        $snapshot = null;
        if ($snapshotId) {
            $snapshot = $archDb->table('arc_company_snapshots')
                ->where('snapshot_id', $snapshotId)
                ->get()
                ->getRowArray();
        } else {
            $snapshot = $archDb->table('arc_company_snapshots')
                ->where('source_company_id', $companyId)
                ->orderBy('archived_at', 'DESC')
                ->get()
                ->getRowArray();
        }

        // Build snapshot from live data if no archive snapshot exists
        if (! $snapshot) {
            $snapshot = $this->buildLiveSnapshot($liveDb, $companyId);
        }

        $tags = $this->generateTags($snapshot);

        // 1. Extract company admin (user_type = 'company')
        $admin = $liveDb->table('ci_erp_users')
            ->where('company_id', $companyId)
            ->where('user_type', 'company')
            ->get()
            ->getRowArray();

        if (! $admin) {
            // Try by user_id = companyId
            $admin = $liveDb->table('ci_erp_users')
                ->where('user_id', $companyId)
                ->where('user_type', 'company')
                ->get()
                ->getRowArray();
        }

        if ($admin) {
            // Check if contact already exists (avoid duplicates)
            $existing = $archDb->table('arc_contacts')
                ->where('email', $admin['email'])
                ->where('contact_type', 'admin')
                ->countAllResults();

            if ($existing === 0) {
                $archDb->table('arc_contacts')->insert([
                    'snapshot_id'         => $snapshotId,
                    'contact_type'        => 'admin',
                    'first_name'          => $admin['first_name'],
                    'last_name'           => $admin['last_name'],
                    'full_name'           => $admin['first_name'] . ' ' . $admin['last_name'],
                    'email'               => $admin['email'],
                    'phone'               => $admin['contact_number'] ?? null,
                    'company_name'        => $admin['company_name'] ?? ($snapshot['company_name'] ?? null),
                    'country'             => $snapshot['country'] ?? null,
                    'city'                => $snapshot['city'] ?? null,
                    'region'              => $snapshot['region'] ?? null,
                    'industry'            => $snapshot['company_type'] ?? null,
                    'employee_count'      => $snapshot['employee_count'] ?? null,
                    'plan_tier'           => $snapshot['plan_tier'] ?? null,
                    'subscription_months' => $snapshot['total_months_paid'] ?? null,
                    'status'              => $snapshot['archive_reason'] ?? 'active',
                    'last_seen'           => date('Y-m-d'),
                    'consent_given'       => $admin['marketing_consent'] ?? 0,
                    'tags'                => $this->formatTagsForPostgres($tags),
                ]);
                $count++;
            }
        }

        // 2. Extract employees with marketing consent
        $employees = $liveDb->table('ci_erp_users')
            ->where('company_id', $companyId)
            ->where('user_type', 'employee')
            ->where('marketing_consent', 1)
            ->get()
            ->getResultArray();

        foreach ($employees as $emp) {
            $existing = $archDb->table('arc_contacts')
                ->where('email', $emp['email'])
                ->where('contact_type', 'employee')
                ->countAllResults();

            if ($existing > 0) {
                continue;
            }

            $archDb->table('arc_contacts')->insert([
                'snapshot_id'         => $snapshotId,
                'contact_type'        => 'employee',
                'first_name'          => $emp['first_name'],
                'last_name'           => $emp['last_name'],
                'full_name'           => $emp['first_name'] . ' ' . $emp['last_name'],
                'email'               => $emp['email'],
                'phone'               => $emp['contact_number'] ?? null,
                'company_name'        => $snapshot['company_name'] ?? null,
                'country'             => $snapshot['country'] ?? null,
                'city'                => $snapshot['city'] ?? null,
                'region'              => $snapshot['region'] ?? null,
                'industry'            => $snapshot['company_type'] ?? null,
                'employee_count'      => $snapshot['employee_count'] ?? null,
                'plan_tier'           => $snapshot['plan_tier'] ?? null,
                'subscription_months' => $snapshot['total_months_paid'] ?? null,
                'status'              => 'active',
                'last_seen'           => date('Y-m-d'),
                'consent_given'       => 1,
                'tags'                => $this->formatTagsForPostgres($tags),
            ]);
            $count++;
        }

        // 3. Extract CRM-style client contacts (invoices table has client_id)
        $clients = $liveDb->table('ci_invoices')
            ->select('ci_invoices.client_id, ci_erp_users.first_name, ci_erp_users.last_name, ci_erp_users.email, ci_erp_users.contact_number, ci_erp_users.company_name')
            ->join('ci_erp_users', 'ci_erp_users.user_id = ci_invoices.client_id', 'left')
            ->where('ci_invoices.company_id', $companyId)
            ->groupBy('ci_invoices.client_id, ci_erp_users.first_name, ci_erp_users.last_name, ci_erp_users.email, ci_erp_users.contact_number, ci_erp_users.company_name')
            ->get()
            ->getResultArray();

        foreach ($clients as $client) {
            if (empty($client['email'])) {
                continue;
            }

            $existing = $archDb->table('arc_contacts')
                ->where('email', $client['email'])
                ->where('contact_type', 'client')
                ->countAllResults();

            if ($existing > 0) {
                continue;
            }

            $archDb->table('arc_contacts')->insert([
                'snapshot_id'         => $snapshotId,
                'contact_type'        => 'client',
                'first_name'          => $client['first_name'] ?? null,
                'last_name'           => $client['last_name'] ?? null,
                'full_name'           => trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '')) ?: null,
                'email'               => $client['email'],
                'phone'               => $client['contact_number'] ?? null,
                'company_name'        => $client['company_name'] ?? null,
                'country'             => $snapshot['country'] ?? null,
                'city'                => $snapshot['city'] ?? null,
                'region'              => $snapshot['region'] ?? null,
                'industry'            => null,
                'employee_count'      => null,
                'plan_tier'           => null,
                'subscription_months' => null,
                'status'              => 'client',
                'last_seen'           => date('Y-m-d'),
                'consent_given'       => 0,
                'tags'                => $this->formatTagsForPostgres(['client', $snapshot['city'] ?? null]),
            ]);
            $count++;
        }

        return $count;
    }

    // ------------------------------------------------------------------
    //  Tag Generation
    // ------------------------------------------------------------------

    /**
     * Generate segmentation tags from a company snapshot.
     *
     * Possible tags:
     *   - City name (e.g. "kampala", "nairobi")
     *   - Industry / company type
     *   - Plan tier (e.g. "basic", "premium")
     *   - "churned" — if archive_reason is expired/cancelled
     *   - "long_term" — if subscription lasted > 12 months
     *   - "large_company" — if employee_count >= 50
     *   - "small_company" — if employee_count < 10
     *
     * @param array $snapshot Arc_company_snapshots row or equivalent.
     *
     * @return array List of tag strings.
     */
    public function generateTags(array $snapshot): array
    {
        $tags = [];

        // City
        $city = $snapshot['city'] ?? null;
        if ($city && $city !== '0') {
            $tags[] = strtolower(trim($city));
        }

        // Region
        $region = $snapshot['region'] ?? null;
        if ($region && $region !== '0') {
            $tags[] = strtolower(trim($region));
        }

        // Industry / company type
        $industry = $snapshot['company_type'] ?? null;
        if ($industry) {
            $tags[] = strtolower(trim($industry));
        }

        // Plan tier
        $planTier = $snapshot['plan_tier'] ?? ($snapshot['plan_name'] ?? null);
        if ($planTier) {
            $tags[] = 'plan:' . strtolower(trim($planTier));
        }

        // Churn status
        $archiveReason = $snapshot['archive_reason'] ?? null;
        if (in_array($archiveReason, ['expired', 'cancelled', 'churned'], true)) {
            $tags[] = 'churned';
        }

        // Subscription duration
        $totalMonths = $snapshot['total_months_paid'] ?? null;
        if ($totalMonths === null && isset($snapshot['subscription_start'], $snapshot['subscription_end'])) {
            $start = strtotime($snapshot['subscription_start']);
            $end   = strtotime($snapshot['subscription_end']);
            if ($start && $end) {
                $totalMonths = max(1, (int) round(($end - $start) / (30 * 86400)));
            }
        }

        if ($totalMonths && $totalMonths > 12) {
            $tags[] = 'long_term';
        }

        // Company size
        $empCount = $snapshot['employee_count'] ?? 0;
        if ($empCount >= 50) {
            $tags[] = 'large_company';
        } elseif ($empCount < 10) {
            $tags[] = 'small_company';
        }

        // Filter out nulls and empty strings
        return array_values(array_filter($tags, fn($t) => $t !== null && $t !== ''));
    }

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /**
     * Build a snapshot-like array from live database data when no
     * archive snapshot exists (used for monthly extraction on active companies).
     */
    private function buildLiveSnapshot($liveDb, int $companyId): array
    {
        $company = $liveDb->table('ci_erp_users')
            ->where('company_id', $companyId)
            ->where('user_type', 'company')
            ->get()
            ->getRowArray();

        if (! $company) {
            $company = $liveDb->table('ci_erp_users')
                ->where('user_id', $companyId)
                ->where('user_type', 'company')
                ->get()
                ->getRowArray();
        }

        $settings = $liveDb->table('ci_erp_settings')
            ->where('setting_id', $companyId)
            ->get()
            ->getRowArray();

        $membership = $liveDb->table('ci_company_membership')
            ->where('company_id', $companyId)
            ->get()
            ->getRowArray();

        $planName = null;
        $planTier = null;
        if ($membership) {
            $plan = $liveDb->table('ci_membership')
                ->where('membership_id', $membership['membership_id'] ?? 0)
                ->get()
                ->getRowArray();
            $planName = $plan['membership_type'] ?? null;
            $planTier = $membership['subscription_type'] ?? null;
        }

        $employeeCount = $liveDb->table('ci_erp_users')
            ->where('company_id', $companyId)
            ->where('user_type', 'employee')
            ->countAllResults();

        // Resolve company type
        $companyType = null;
        $typeId = $company['company_type_id'] ?? ($settings['company_type_id'] ?? null);
        if ($typeId) {
            $typeRow = $liveDb->table('ci_general_options')
                ->select('option_value')
                ->where('option_id', $typeId)
                ->get()
                ->getRowArray();
            $companyType = $typeRow['option_value'] ?? null;
        }

        return [
            'source_company_id' => $companyId,
            'company_name'      => $company['company_name'] ?? ($settings['company_name'] ?? null),
            'trading_name'      => $company['trading_name'] ?? ($settings['trading_name'] ?? null),
            'country'           => $settings['country'] ?? ($company['country'] ?? null),
            'city'              => $settings['city'] ?? ($company['city'] ?? null),
            'region'            => $settings['state'] ?? ($company['state'] ?? null),
            'company_type'      => $companyType,
            'employee_count'    => $employeeCount,
            'plan_name'         => $planName,
            'plan_tier'         => $planTier,
            'subscription_start' => $membership['created_at'] ?? null,
            'subscription_end'   => $membership['expiry_date'] ?? null,
            'total_months_paid'  => null,
            'archive_reason'     => 'active',
        ];
    }

    /**
     * Format a PHP array of tags into PostgreSQL TEXT[] literal.
     *
     * @param array $tags
     *
     * @return string PostgreSQL array literal, e.g. {kampala,churned,large_company}
     */
    private function formatTagsForPostgres(array $tags): string
    {
        $tags = array_values(array_filter($tags, fn($t) => $t !== null && $t !== ''));

        if (empty($tags)) {
            return '{}';
        }

        $escaped = array_map(function ($tag) {
            // Escape double quotes and backslashes for PostgreSQL array literals
            $tag = str_replace('\\', '\\\\', $tag);
            $tag = str_replace('"', '\\"', $tag);
            // Wrap in double quotes if it contains special chars
            if (preg_match('/[,{}"\\s]/', $tag)) {
                return '"' . $tag . '"';
            }
            return $tag;
        }, $tags);

        return '{' . implode(',', $escaped) . '}';
    }
}
