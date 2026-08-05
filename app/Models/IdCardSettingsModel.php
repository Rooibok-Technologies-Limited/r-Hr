<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Models;

use CodeIgniter\Model;

class IdCardSettingsModel extends Model
{
    protected $table      = 'ci_id_card_settings';
    protected $primaryKey = 'setting_id';

    protected $allowedFields = [
        'company_id', 'template', 'show_logo', 'enable_qr', 'fields',
        'default_orientation', 'allow_orientation_choice',
        'id_prefix', 'id_pattern', 'seq_length', 'validity_years', 'terms',
        'color_primary', 'color_secondary', 'color_accent', 'color_dark',
        'color_light', 'color_bg', 'color_text', 'color_muted',
        'created_at', 'updated_at',
    ];

    protected $useTimestamps = false;

    /** Settings row for a tenant, or null when never configured. */
    public function forCompany(int $companyId): ?array
    {
        return $this->where('company_id', $companyId)->first();
    }
}
