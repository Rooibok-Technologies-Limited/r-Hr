<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Staff ID Card domain service. Owns: per-tenant card settings (with the
 * "Abstract Organic" defaults), staff-ID number generation, secure verification
 * tokens, card issue / regenerate / revoke lifecycle, and assembly of the data
 * bundle the SVG template renders. Everything is tenant-scoped; the public
 * verification path consumes only a whitelisted subset (see publicVerifyData()).
 */
namespace App\Libraries;

use App\Models\IdCardModel;
use App\Models\IdCardSettingsModel;

class IdCardService
{
    /** Abstract Organic template defaults (sampled from the reference art). */
    public const DEFAULTS = [
        'template'        => 'abstract_organic',
        'default_orientation'      => 'portrait',
        'allow_orientation_choice' => 1,
        'show_logo'       => 1,
        'enable_qr'       => 1,
        'id_prefix'       => 'RT',
        'id_pattern'      => '{PREFIX}-{YEAR}-{SEQUENCE}',
        'seq_length'      => 4,
        'validity_years'  => 2,
        'terms'           => 'This identification card remains the property of the issuing company. If found, please return it to the company. Unauthorized use is prohibited.',
        'color_primary'   => '#E07B54', // coral ribbon
        'color_secondary' => '#A7C49A', // sage green
        'color_accent'    => '#E8A07E', // light coral dot
        'color_dark'      => '#3B5A45', // forest green
        'color_light'     => '#D8D2C7', // warm grey blob
        'color_bg'        => '#ECE8E1', // cream base
        'color_text'      => '#23201C',
        'color_muted'     => '#6E6A63',
    ];

    /** Default field visibility toggles. */
    public const DEFAULT_FIELDS = [
        'photo'         => true,
        'name'          => true,
        'position'      => true,
        'staff_id'      => true,
        'join_date'     => true,
        'expiry_date'   => true,
        'date_of_birth' => false,
        'department'    => false,
        'phone'         => false,
        'blood_group'   => false,
    ];

    protected IdCardModel $cards;
    protected IdCardSettingsModel $settingsModel;

    public function __construct()
    {
        $this->cards         = new IdCardModel();
        $this->settingsModel = new IdCardSettingsModel();
    }

    // ---------------------------------------------------------------- settings

    /** Resolve a tenant's card settings, merged over the Abstract Organic defaults. */
    public function settings(int $companyId): array
    {
        $row = $this->settingsModel->forCompany($companyId) ?: [];
        $out = self::DEFAULTS;
        // The tenant's own system theme is the DEFAULT card palette (brand-first).
        // Explicit per-card colours below still override these.
        $theme = $this->tenantThemeColors($companyId);
        $out['color_primary']   = $theme['primary'];
        $out['color_secondary'] = $theme['secondary'];
        $out['color_accent']    = $theme['accent'];
        $out['color_dark']      = $theme['dark'];
        foreach (self::DEFAULTS as $k => $v) {
            if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
                $out[$k] = $row[$k];
            }
        }
        $out['show_logo'] = isset($row['show_logo']) ? (int) $row['show_logo'] : 1;
        $out['enable_qr'] = isset($row['enable_qr']) ? (int) $row['enable_qr'] : 1;
        $out['allow_orientation_choice'] = isset($row['allow_orientation_choice']) ? (int) $row['allow_orientation_choice'] : 1;
        $out['default_orientation'] = $this->normalizeOrientation($row['default_orientation'] ?? $out['default_orientation']);
        $out['seq_length']     = (int) $out['seq_length'];
        $out['validity_years'] = (int) $out['validity_years'];

        $fields = self::DEFAULT_FIELDS;
        if (! empty($row['fields'])) {
            $decoded = json_decode($row['fields'], true);
            if (is_array($decoded)) {
                foreach (self::DEFAULT_FIELDS as $k => $v) {
                    if (array_key_exists($k, $decoded)) {
                        $fields[$k] = (bool) $decoded[$k];
                    }
                }
            }
        }
        $out['fields'] = $fields;
        return $out;
    }

    /** Persist (upsert) a tenant's settings from a sanitised assoc array. */
    public function saveSettings(int $companyId, array $data): void
    {
        $data['company_id'] = $companyId;
        $data['updated_at'] = date('Y-m-d H:i:s');
        $existing = $this->settingsModel->forCompany($companyId);
        if ($existing) {
            $this->settingsModel->where('company_id', $companyId)->set($data)->update();
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->settingsModel->insert($data);
        }
    }

    // --------------------------------------------------------------- staff IDs

    /** Existing employee_id, or generate + persist one per the tenant's pattern. */
    public function getOrCreateStaffId(int $companyId, int $userId): string
    {
        $db      = \Config\Database::connect();
        $details = $db->table('ci_erp_users_details')->where('user_id', $userId)->get()->getRowArray();
        $existing = trim((string) ($details['employee_id'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }
        $number = $this->generateNumber($companyId);
        if ($details) {
            $db->table('ci_erp_users_details')->where('user_id', $userId)->update(['employee_id' => $number]);
        }
        return $number;
    }

    /** Build the next unique card/staff number for a tenant. */
    public function generateNumber(int $companyId): string
    {
        $s       = $this->settings($companyId);
        $prefix  = strtoupper($s['id_prefix']);
        $year    = date('Y');
        $len     = max(1, (int) $s['seq_length']);
        $pattern = $s['id_pattern'] ?: '{PREFIX}-{YEAR}-{SEQUENCE}';

        $seq = $this->nextSequence($companyId, $prefix, $year, $len, $pattern);
        // Guard against collisions across both the details and cards tables.
        for ($i = 0; $i < 1000; $i++) {
            $candidate = $this->formatNumber($pattern, $prefix, $year, $seq + $i, $len);
            if (! $this->numberTaken($companyId, $candidate)) {
                return $candidate;
            }
        }
        // Extremely unlikely fallback: suffix with a short random tail.
        return $this->formatNumber($pattern, $prefix, $year, $seq, $len) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 3));
    }

    protected function formatNumber(string $pattern, string $prefix, string $year, int $seq, int $len): string
    {
        $seqStr = str_pad((string) $seq, $len, '0', STR_PAD_LEFT);
        return strtr($pattern, [
            '{PREFIX}'   => $prefix,
            '{YEAR}'     => $year,
            '{SEQUENCE}' => $seqStr,
            '{SEQ}'      => $seqStr,
        ]);
    }

    /** Highest existing sequence for this tenant/prefix/year + 1. */
    protected function nextSequence(int $companyId, string $prefix, string $year, int $len, string $pattern): int
    {
        $db  = \Config\Database::connect();
        $max = 0;
        // Turn the pattern into a capture regex for the numeric sequence.
        $regexBody = preg_quote($pattern, '/');
        $regexBody = strtr($regexBody, [
            preg_quote('{PREFIX}', '/')   => preg_quote($prefix, '/'),
            preg_quote('{YEAR}', '/')     => preg_quote($year, '/'),
            preg_quote('{SEQUENCE}', '/') => '(\d+)',
            preg_quote('{SEQ}', '/')      => '(\d+)',
        ]);
        $regex = '/^' . $regexBody . '$/';

        $scan = function (array $values) use ($regex, &$max) {
            foreach ($values as $v) {
                if (preg_match($regex, (string) $v, $m) && isset($m[1])) {
                    $max = max($max, (int) $m[1]);
                }
            }
        };

        $ids = $db->table('ci_erp_users_details d')
            ->select('d.employee_id')
            ->join('ci_erp_users u', 'u.user_id = d.user_id')
            ->where('u.company_id', $companyId)
            ->where('d.employee_id IS NOT NULL')
            ->get()->getResultArray();
        $scan(array_column($ids, 'employee_id'));

        $nums = $db->table('ci_employee_id_cards')
            ->select('card_number')->where('company_id', $companyId)
            ->get()->getResultArray();
        $scan(array_column($nums, 'card_number'));

        return $max + 1;
    }

    protected function numberTaken(int $companyId, string $number): bool
    {
        $db = \Config\Database::connect();
        $inCards = $db->table('ci_employee_id_cards')->where('company_id', $companyId)->where('card_number', $number)->countAllResults();
        if ($inCards > 0) {
            return true;
        }
        $inDetails = $db->table('ci_erp_users_details d')
            ->join('ci_erp_users u', 'u.user_id = d.user_id')
            ->where('u.company_id', $companyId)->where('d.employee_id', $number)
            ->countAllResults();
        return $inDetails > 0;
    }

    // -------------------------------------------------------------- lifecycle

    private function newToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16)); // 32 hex chars, non-guessable
        } while ($this->cards->byToken($token) !== null);
        return $token;
    }

    /** Normalise an orientation string to 'portrait' | 'landscape'. */
    public function normalizeOrientation(?string $o): string
    {
        return strtolower(trim((string) $o)) === 'landscape' ? 'landscape' : 'portrait';
    }

    /**
     * Resolve the orientation to use given a (possibly null) request, honouring
     * the tenant's allow_orientation_choice flag and default.
     */
    public function resolveOrientation(int $companyId, ?string $requested, ?array $card = null): string
    {
        $s = $this->settings($companyId);
        if ($requested !== null && (int) $s['allow_orientation_choice'] === 1) {
            return $this->normalizeOrientation($requested);
        }
        if ($card && ! empty($card['orientation'])) {
            return $this->normalizeOrientation($card['orientation']);
        }
        return $this->normalizeOrientation($s['default_orientation']);
    }

    /** Issue (or regenerate) a card for an employee. Returns the card row. */
    public function issue(int $companyId, int $userId, int $actorId, bool $regenerate = false, ?string $orientation = null): array
    {
        $s          = $this->settings($companyId);
        $number     = $this->getOrCreateStaffId($companyId, $userId);
        $now        = date('Y-m-d H:i:s');
        $expiryDate = date('Y-m-d', strtotime('+' . max(1, (int) $s['validity_years']) . ' years'));
        $orient     = $this->resolveOrientation($companyId, $orientation, $this->cards->forEmployee($companyId, $userId));

        $existing = $this->cards->forEmployee($companyId, $userId);
        if ($existing && ! $regenerate) {
            return $existing;
        }

        if ($existing) {
            $update = [
                'card_number'       => $number,
                'status'            => 'active',
                'orientation'       => $orient,
                'issued_at'         => $now,
                'expiry_date'       => $expiryDate,
                'revoked_at'        => null,
                'revoked_by'        => null,
                'last_generated_at' => $now,
                'updated_at'        => $now,
            ];
            // Keep the same verify token across regenerations so already-printed
            // cards keep verifying, unless it was revoked (then rotate for safety).
            if (($existing['status'] ?? '') === 'revoked') {
                $update['verify_token'] = $this->newToken();
            }
            $this->cards->where('card_id', $existing['card_id'])->set($update)->update();
            $card = $this->cards->find($existing['card_id']);
        } else {
            $id = $this->cards->insert([
                'company_id'        => $companyId,
                'user_id'           => $userId,
                'card_number'       => $number,
                'verify_token'      => $this->newToken(),
                'status'            => 'active',
                'orientation'       => $orient,
                'issued_at'         => $now,
                'expiry_date'       => $expiryDate,
                'last_generated_at' => $now,
                'created_at'        => $now,
                'updated_at'        => $now,
            ], true);
            $card = $this->cards->find($id);
        }

        service('audit')->record($regenerate ? 'id_card.regenerated' : 'id_card.generated', [
            'entity_type' => 'employee',
            'entity_id'   => $userId,
            'summary'     => 'ID card ' . ($regenerate ? 'regenerated' : 'generated') . ' (' . $number . ')',
        ]);
        return $card;
    }

    /** Revoke a card — the QR immediately shows CARD REVOKED. */
    public function revoke(int $companyId, int $userId, int $actorId): bool
    {
        $card = $this->cards->forEmployee($companyId, $userId);
        if (! $card) {
            return false;
        }
        $this->cards->where('card_id', $card['card_id'])->set([
            'status'     => 'revoked',
            'revoked_at' => date('Y-m-d H:i:s'),
            'revoked_by' => $actorId,
            'updated_at' => date('Y-m-d H:i:s'),
        ])->update();
        service('audit')->record('id_card.revoked', [
            'entity_type' => 'employee', 'entity_id' => $userId,
            'summary'     => 'ID card revoked (' . $card['card_number'] . ')',
        ]);
        return true;
    }

    /** Effective status: explicit revoke > expiry > inactive employee > active. */
    public function effectiveStatus(?array $card, int $employeeActive): string
    {
        if (! $card) {
            return 'not_issued';
        }
        if (($card['status'] ?? '') === 'revoked') {
            return 'revoked';
        }
        if (! empty($card['expiry_date']) && strtotime($card['expiry_date']) < strtotime(date('Y-m-d'))) {
            return 'expired';
        }
        if ($employeeActive !== 1) {
            return 'inactive';
        }
        return 'active';
    }

    // ------------------------------------------------------------- card data

    /**
     * Assemble the full card render bundle (authenticated preview / print).
     * Returns null when the employee is not visible to $companyId.
     */
    public function buildCardData(int $companyId, int $userId, bool $autoIssue = true, ?string $orientation = null): ?array
    {
        helper(['main', 'timehr']);
        $db = \Config\Database::connect();

        $emp = $db->table('ci_erp_users')->where('user_id', $userId)->get()->getRowArray();
        if (! $emp) {
            return null;
        }
        // Tenant scope: the employee's company_id must match the effective tenant.
        // (Owners carry company_id=0 on their own row; a self/own-company card is
        // still resolved through the acting company id.)
        if ((int) $emp['company_id'] !== $companyId && (int) $emp['user_id'] !== $companyId) {
            return null;
        }

        $det  = $db->table('ci_erp_users_details')->where('user_id', $userId)->get()->getRowArray() ?: [];
        $desig = ! empty($det['designation_id'])
            ? $db->table('ci_designations')->where('designation_id', $det['designation_id'])->get()->getRowArray() : null;
        $dept  = ! empty($det['department_id'])
            ? $db->table('ci_departments')->where('department_id', $det['department_id'])->get()->getRowArray() : null;

        $s    = $this->settings($companyId);
        $card = $this->cards->forEmployee($companyId, $userId);
        if (! $card && $autoIssue) {
            $card = $this->issue($companyId, $userId, (int) (session('sup_username')['sup_user_id'] ?? 0));
        }

        $orient     = $this->resolveOrientation($companyId, $orientation, $card);
        $staffId    = $card['card_number'] ?? $this->getOrCreateStaffId($companyId, $userId);
        $joinRaw    = $det['date_of_joining'] ?? '';
        $expiryRaw  = $card['expiry_date'] ?? ($joinRaw ? date('Y-m-d', strtotime('+' . $s['validity_years'] . ' years', strtotime($joinRaw))) : '');
        $status     = $this->effectiveStatus($card, (int) ($emp['is_active'] ?? 1));

        $cs = function_exists('erp_company_settings') ? (erp_company_settings() ?: []) : [];
        // Tenant identity lives on the OWNER row (user_id == companyId), not on a
        // staff row. Resolve the company name/contact from there.
        $owner   = $db->table('ci_erp_users')->where('user_id', $companyId)->get()->getRowArray() ?: [];
        $coName  = trim((string) ($cs['company_name'] ?? $owner['company_name'] ?? $owner['first_name'] ?? ''));

        return [
            'user_id'       => (int) $userId,
            'first_name'    => $emp['first_name'] ?? '',
            'last_name'     => $emp['last_name'] ?? '',
            'full_name'     => trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')),
            'position'      => $desig['designation_name'] ?? '',
            'department'    => $dept['department_name'] ?? '',
            'photo_url'     => function_exists('staff_profile_photo') ? staff_profile_photo($userId) : '',
            'staff_id'      => $staffId,
            'join_date'     => $this->fmtDate($joinRaw),
            'expiry_date'   => $this->fmtDate($expiryRaw),
            'dob'           => $this->fmtDate($det['date_of_birth'] ?? ''),
            'phone'         => $emp['contact_number'] ?? '',
            'blood_group'   => $det['blood_group'] ?? '',
            'status'        => $status,
            'orientation'   => $orient,
            'verify_token'  => $card['verify_token'] ?? '',
            'verify_url'    => ! empty($card['verify_token']) ? site_url('verify/staff/' . $card['verify_token']) : '',
            'company'       => [
                // TENANT identity only — never the platform/system name.
                'name'    => $coName,
                'logo'    => $this->companyLogoUrl($cs),
                'website' => $cs['website'] ?? '',
                'phone'   => $cs['contact_number'] ?? ($owner['contact_number'] ?? ''),
            ],
            'settings'      => $s,
            'card'          => $card,
        ];
    }

    /** Whitelisted subset for the PUBLIC verification page — never leak private HR data. */
    public function publicVerifyData(string $token): ?array
    {
        $card = $this->cards->byToken($token);
        if (! $card) {
            return null;
        }
        $data = $this->buildCardData((int) $card['company_id'], (int) $card['user_id'], false);
        if (! $data) {
            return null;
        }
        return [
            'company_name' => $data['company']['name'],
            'company_logo' => $data['company']['logo'],
            'photo_url'    => $data['photo_url'],
            'full_name'    => $data['full_name'],
            'staff_id'     => $data['staff_id'],
            'position'     => $data['position'],
            'department'   => $data['department'],
            'join_date'    => $data['join_date'],
            'expiry_date'  => $data['expiry_date'],
            'status'       => $data['status'],
            'orientation'  => $data['orientation'],
        ];
    }

    /** TENANT logo only — the platform/system brand must never appear on a card. */
    private function companyLogoUrl(array $cs): string
    {
        if (! empty($cs['company_logo'])) {
            return base_url('public/uploads/logo/company/' . $cs['company_logo']);
        }
        return '';
    }

    /**
     * The tenant's system theme colours, used as the card's default palette so a
     * card is on-brand out of the box. Mirrors htmlheader.php's defaults; reads
     * per-tenant theme_* if those columns exist (forward-compatible), else the
     * app defaults. Never throws on a missing column.
     */
    private function tenantThemeColors(int $companyId): array
    {
        $primary = '#7267EF'; $success = '#17C666';
        try {
            $row = \Config\Database::connect()->table('ci_erp_company_settings')
                ->where('company_id', $companyId)->get()->getRowArray() ?: [];
            $primary = hex_color($row['theme_primary'] ?? null, $primary);
            $success = hex_color($row['theme_success'] ?? null, $success);
        } catch (\Throwable $e) { /* columns absent — keep app defaults */ }
        return [
            'primary'   => $primary,
            'secondary' => $success,
            'accent'    => $this->shade($primary, 0.30),   // lighter primary
            'dark'      => $this->shade($primary, -0.45),  // darker primary
        ];
    }

    /** Lighten (amount>0) or darken (amount<0) a #hex colour. */
    private function shade(string $hex, float $amount): string
    {
        $hex = ltrim(hex_color($hex, '#7267EF'), '#');
        if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
        $adj = static function ($c) use ($amount) {
            $c = $amount >= 0 ? $c + (255 - $c) * $amount : $c * (1 + $amount);
            return max(0, min(255, (int) round($c)));
        };
        return sprintf('#%02X%02X%02X', $adj($r), $adj($g), $adj($b));
    }

    private function fmtDate(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '' || $raw === '0000-00-00') {
            return '';
        }
        $ts = strtotime($raw);
        return $ts ? date('d/m/Y', $ts) : $raw;
    }
}
