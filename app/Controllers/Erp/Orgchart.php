<?php
namespace App\Controllers\Erp;
use App\Controllers\BaseController;

use App\Models\SystemModel;
use App\Models\RolesModel;
use App\Models\UsersModel;
use App\Models\DepartmentModel;
use App\Models\DesignationModel;
use App\Models\StaffdetailsModel;

class Orgchart extends BaseController {

	public function index()
	{
		$RolesModel = new RolesModel();
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if(!$session->has('sup_username')){
			$session->setFlashdata('err_not_logged_in',lang('Dashboard.err_not_logged_in'));
			return redirect()->to(site_url('erp/login'));
		}
		if($user_info['user_type'] != 'company' && $user_info['user_type']!='staff'){
			$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}
		if($user_info['user_type'] != 'company'){
			if(!in_array('org_chart1',staff_role_resource())) {
				$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
				return redirect()->to(site_url('erp/desk'));
			}
		}
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Dashboard.xin_org_chart_title').' | '.$xin_system['application_name'];
		$data['path_url'] = 'org_chart';
		$data['breadcrumbs'] = lang('Dashboard.xin_org_chart_title');

		$data['subview'] = view('erp/chart/org_chart', $data);
		return view('erp/layout/layout_main', $data);
	}

	/**
	 * AJAX endpoint - returns JSON tree structure for org chart
	 * Root = Company name
	 * Level 1 = Departments
	 * Level 2 = Employees grouped by designation
	 */
	public function get_tree_data()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			return $this->response->setJSON(['error' => 'Unauthorized']);
		}

		$UsersModel = new UsersModel();
		$DepartmentModel = new DepartmentModel();
		$DesignationModel = new DesignationModel();
		$StaffdetailsModel = new StaffdetailsModel();

		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if($user_info['user_type'] == 'staff'){
			$company_id = $user_info['company_id'];
		} else {
			$company_id = $usession['sup_user_id'];
		}

		$company_info = $UsersModel->where('user_id', $company_id)->first();
		$company_name = $company_info['company_name'] ?: ($company_info['first_name'].' '.$company_info['last_name']);

		// Build tree: Root -> Departments -> Employees (grouped by designation)
		$departments = $DepartmentModel->where('company_id', $company_id)->findAll();
		$dept_children = [];

		foreach($departments as $dept) {
			$designations = $DesignationModel->where('department_id', $dept['department_id'])->findAll();
			$emp_nodes = [];

			foreach($designations as $desig) {
				$staff_details = $StaffdetailsModel->where('designation_id', $desig['designation_id'])->findAll();

				foreach($staff_details as $sd) {
					$emp = $UsersModel->where('user_id', $sd['user_id'])->where('is_active', 1)->first();
					if(!$emp) continue;

					$photo_url = '';
					if(!empty($emp['profile_photo']) && $emp['profile_photo'] != 'default.png') {
						$photo_url = base_url('uploads/profile/'.$emp['profile_photo']);
					} else {
						$photo_url = base_url('assets/images/avatar.png');
					}

					$emp_nodes[] = [
						'name' => $emp['first_name'].' '.$emp['last_name'],
						'designation' => $desig['designation_name'],
						'department' => $dept['department_name'],
						'photo_url' => $photo_url,
						'user_id' => $emp['user_id'],
						'children' => [],
					];
				}
			}

			if(!empty($emp_nodes) || !empty($designations)) {
				// Department head info
				$head_name = '';
				if(!empty($dept['department_head']) && $dept['department_head'] != 0) {
					$head = $UsersModel->where('user_id', $dept['department_head'])->first();
					if($head) {
						$head_name = $head['first_name'].' '.$head['last_name'];
					}
				}

				$dept_children[] = [
					'name' => $dept['department_name'],
					'designation' => $head_name ? 'Head: '.$head_name : '',
					'department' => '',
					'photo_url' => '',
					'user_id' => 0,
					'children' => $emp_nodes,
				];
			}
		}

		$tree = [
			'name' => $company_name,
			'designation' => 'Organization',
			'department' => '',
			'photo_url' => '',
			'user_id' => 0,
			'children' => $dept_children,
		];

		return $this->response->setJSON($tree);
	}
}
