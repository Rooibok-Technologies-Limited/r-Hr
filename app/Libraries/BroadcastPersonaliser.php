<?php

namespace App\Libraries;

class BroadcastPersonaliser
{
    /**
     * Replace personalisation tokens in a template string.
     *
     * @param string $template  The template text containing {{tokens}}.
     * @param array  $recipient Recipient user data (merged with staff details).
     * @param array  $sender    Sender user data.
     *
     * @return string The personalised text.
     */
    public function personalise(string $template, array $recipient, array $sender): string
    {
        $tokens = [
            '{{first_name}}'    => $recipient['first_name'] ?? '',
            '{{last_name}}'     => $recipient['last_name']  ?? '',
            '{{full_name}}'     => trim(($recipient['first_name'] ?? '') . ' ' . ($recipient['last_name'] ?? '')),
            '{{company_name}}'  => $recipient['company_name'] ?? '',
            '{{department}}'    => $recipient['department_name'] ?? '',
            '{{designation}}'   => $recipient['designation_name'] ?? '',
            '{{plan_name}}'     => $recipient['plan_name'] ?? '',
            '{{expiry_date}}'   => isset($recipient['expiry_date']) ? date('d M Y', strtotime($recipient['expiry_date'])) : '',
            '{{date}}'          => date('d M Y'),
            '{{month}}'         => date('F Y'),
            '{{sender_name}}'   => trim(($sender['first_name'] ?? '') . ' ' . ($sender['last_name'] ?? '')),
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $template);
    }

    /**
     * Build the list of recipients based on audience type.
     *
     * @param array $broadcast  The broadcast record (must contain audience_type, audience_ids).
     * @param int   $companyId  The company to scope queries to.
     *
     * @return array Array of user records.
     */
    public function buildRecipientList(array $broadcast, int $companyId): array
    {
        $UsersModel = new \App\Models\UsersModel();

        $audienceIds = $broadcast['audience_ids'] ?? [];
        if (is_string($audienceIds)) {
            $audienceIds = json_decode($audienceIds, true) ?: [];
        }

        return match ($broadcast['audience_type']) {
            'all_employees'     => $UsersModel->where('company_id', $companyId)
                                              ->where('user_type', 'staff')
                                              ->where('is_active', 1)
                                              ->findAll(),
            'department'        => $this->getByDepartments($audienceIds, $companyId),
            'individual'        => $this->getByIds($audienceIds),
            'all_company_admins' => $UsersModel->where('user_type', 'company')
                                               ->where('is_active', 1)
                                               ->findAll(),
            default             => [],
        };
    }

    /**
     * Get staff users belonging to specific departments within a company.
     *
     * @param array $departmentIds Array of department IDs.
     * @param int   $companyId     Company ID.
     *
     * @return array
     */
    private function getByDepartments(array $departmentIds, int $companyId): array
    {
        if (empty($departmentIds)) {
            return [];
        }

        $db = \Config\Database::connect();

        $result = $db->table('ci_erp_users u')
            ->select('u.*')
            ->join('ci_erp_users_details d', 'd.user_id = u.user_id')
            ->where('u.company_id', $companyId)
            ->where('u.user_type', 'staff')
            ->where('u.is_active', 1)
            ->whereIn('d.department_id', $departmentIds)
            ->get()
            ->getResultArray();

        return $result;
    }

    /**
     * Get users by their IDs.
     *
     * @param array $userIds Array of user IDs.
     *
     * @return array
     */
    private function getByIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $UsersModel = new \App\Models\UsersModel();

        return $UsersModel->whereIn('user_id', $userIds)
                          ->where('is_active', 1)
                          ->findAll();
    }

    /**
     * Enrich a recipient record with department/designation names for token replacement.
     *
     * @param array $recipient Raw user record.
     *
     * @return array Enriched user record.
     */
    public function enrichRecipient(array $recipient): array
    {
        $StaffdetailsModel  = new \App\Models\StaffdetailsModel();
        $DepartmentModel    = new \App\Models\DepartmentModel();
        $DesignationModel   = new \App\Models\DesignationModel();

        $details = $StaffdetailsModel->where('user_id', $recipient['user_id'])->first();

        if ($details) {
            if (! empty($details['department_id'])) {
                $dept = $DepartmentModel->find($details['department_id']);
                $recipient['department_name'] = $dept['department_name'] ?? '';
            }
            if (! empty($details['designation_id'])) {
                $desig = $DesignationModel->find($details['designation_id']);
                $recipient['designation_name'] = $desig['designation_name'] ?? '';
            }
        }

        return $recipient;
    }
}
