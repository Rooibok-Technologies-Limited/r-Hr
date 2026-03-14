<?php

namespace App\Models;

use CodeIgniter\Model;

class BroadcastTemplateModel extends Model
{
    protected $table      = 'ci_broadcast_templates';
    protected $primaryKey = 'template_id';

    protected $allowedFields = [
        'template_id',
        'company_id',
        'template_name',
        'subject',
        'body_html',
        'body_sms',
        'category',
        'created_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /**
     * Get templates for a company (includes global templates where company_id IS NULL).
     */
    public function getForCompany(?int $companyId): array
    {
        return $this->groupStart()
                    ->where('company_id', $companyId)
                    ->orWhere('company_id IS NULL')
                    ->groupEnd()
                    ->orderBy('template_name', 'ASC')
                    ->findAll();
    }

    /**
     * Get all templates (super admin).
     */
    public function getAll(): array
    {
        return $this->orderBy('template_name', 'ASC')
                    ->findAll();
    }
}
