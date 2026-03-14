<?php

namespace App\Controllers\Api\V1;

use App\Models\UsersModel;
use App\Models\StaffdetailsModel;
use App\Models\DepartmentModel;
use App\Models\DesignationModel;

class Employees extends ApiBaseController
{
    /**
     * GET /api/v1/employee/{id}
     *
     * Returns employee details. Only accessible for employees within the same company.
     */
    public function show(int $id)
    {
        $companyId = $this->jwtCompanyId();

        $usersModel = new UsersModel();
        $user = $usersModel->where('user_id', $id)
                           ->where('company_id', $companyId)
                           ->first();

        if (!$user) {
            return $this->errorResponse('Employee not found', 404);
        }

        // Get staff details
        $staffModel = new StaffdetailsModel();
        $details = $staffModel->where('user_id', $id)->first();

        // Get department and designation names
        $departmentName  = null;
        $designationName = null;

        if ($details) {
            if (!empty($details['department_id'])) {
                $deptModel  = new DepartmentModel();
                $department = $deptModel->find($details['department_id']);
                $departmentName = $department['department_name'] ?? null;
            }
            if (!empty($details['designation_id'])) {
                $desigModel  = new DesignationModel();
                $designation = $desigModel->find($details['designation_id']);
                $designationName = $designation['designation_name'] ?? null;
            }
        }

        return $this->jsonResponse([
            'id'              => (int) $user['user_id'],
            'company_id'      => (int) $user['company_id'],
            'first_name'      => $user['first_name'],
            'last_name'       => $user['last_name'],
            'email'           => $user['email'],
            'contact_number'  => $user['contact_number'],
            'gender'          => $user['gender'],
            'user_type'       => $user['user_type'],
            'is_active'       => (int) $user['is_active'],
            'department'      => $departmentName,
            'designation'     => $designationName,
            'date_of_joining' => $details['date_of_joining'] ?? null,
            'employee_id'     => $details['employee_id']     ?? null,
        ]);
    }
}
