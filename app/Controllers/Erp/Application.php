<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the TimeHRM License
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.timehrm.com/license.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to timehrm.official@gmail.com so we can send you a copy immediately.
 *
 * @author   TimeHRM
 * @author-email  timehrm.official@gmail.com
 * @copyright  Copyright © timehrm.com All Rights Reserved
 */
namespace App\Controllers\Erp;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Files\UploadedFile;
 
use App\Models\SystemModel;
use App\Models\RolesModel;
use App\Models\UsersModel;
use App\Models\DepartmentModel;
use App\Models\DesignationModel;
use App\Models\MainModel;
use App\Models\StaffdetailsModel;

class Application extends BaseController {

	public function erp_calendar()
	{		
		$RolesModel = new RolesModel();
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		//$AssetsModel = new AssetsModel();
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
			if(!in_array('system_calendar',staff_role_resource())) {
				$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
				return redirect()->to(site_url('erp/desk'));
			}
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Dashboard.xin_system_calendar').' | '.$xin_system['application_name'];
		$data['path_url'] = 'employees';
		$data['breadcrumbs'] = lang('Dashboard.xin_system_calendar');

		$data['subview'] = view('erp/erp_calendar/erp_calendar', $data);
		return view('erp/layout/layout_main', $data); //page load
	}
	public function reports()
	{		
		$RolesModel = new RolesModel();
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		//$AssetsModel = new AssetsModel();
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
			if(!in_array('system_reports',staff_role_resource())) {
				$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
				return redirect()->to(site_url('erp/desk'));
			}
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Dashboard.xin_system_reports').' | '.$xin_system['application_name'];
		$data['path_url'] = 'employees';
		$data['breadcrumbs'] = lang('Dashboard.xin_system_reports');

		$data['subview'] = view('erp/reports_imports/reports', $data);
		return view('erp/layout/layout_main', $data); //page load
	}
	public function import()
	{		
		$RolesModel = new RolesModel();
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		//$AssetsModel = new AssetsModel();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if(!$session->has('sup_username')){ 
			return redirect()->to(site_url('erp/login'));
		}
		if($user_info['user_type'] != 'company' && $user_info['user_type']!='staff'){
			return redirect()->to(site_url('erp/desk'));
		}
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Dashboard.dashboard_employees').' | '.$xin_system['application_name'];
		$data['path_url'] = 'employees';
		$data['breadcrumbs'] = lang('Dashboard.dashboard_employees');

		$data['subview'] = view('erp/reports_imports/import_data', $data);
		return view('erp/layout/layout_main', $data); //page load
	}

	/**
	 * POST erp/application/import — process an uploaded CSV. Supported datasets:
	 * departments (department_name) and designations (designation_name, department).
	 * Company-scoped; skip-duplicates optional; returns a flash summary.
	 */
	public function import_process()
	{
		$session  = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (! $session->has('sup_username')) {
			return redirect()->to(site_url('erp/login'));
		}
		$user_info = (new UsersModel())->where('user_id', $usession['sup_user_id'])->first();
		if (empty($user_info) || ($user_info['user_type'] !== 'company' && $user_info['user_type'] !== 'staff')) {
			return redirect()->to(site_url('erp/desk'));
		}
		$companyId = ($user_info['user_type'] === 'staff') ? (int) $user_info['company_id'] : (int) $user_info['user_id'];

		$type    = strip_tags(trim((string) $this->request->getPost('import_type')));
		$skipDup = (int) $this->request->getPost('skip_duplicates') === 1;
		$file    = $this->request->getFile('import_file');

		$supported = ['departments', 'designations'];
		if (! in_array($type, $supported, true)) {
			return redirect()->to(site_url('erp/system-import'))
				->with('import_error', 'Import for that dataset is not available yet. Supported: Departments, Designations.');
		}
		if (! $file || ! $file->isValid() || strtolower((string) $file->getExtension()) !== 'csv') {
			return redirect()->to(site_url('erp/system-import'))->with('import_error', 'Please upload a valid .csv file.');
		}
		if ($file->getSize() > 2 * 1024 * 1024) {
			return redirect()->to(site_url('erp/system-import'))->with('import_error', 'File too large (max 2 MB).');
		}

		// Parse CSV into header-keyed rows.
		$rows = [];
		if (($h = fopen($file->getTempName(), 'r')) !== false) {
			$header = fgetcsv($h);
			$header = array_map(static fn ($c) => strtolower(trim((string) $c)), $header ?: []);
			$cols   = count($header);
			while (($r = fgetcsv($h)) !== false) {
				if ($cols === 0) { break; }
				if (count(array_filter($r, static fn ($v) => trim((string) $v) !== '')) === 0) { continue; }
				$rows[] = array_combine($header, array_pad(array_slice($r, 0, $cols), $cols, ''));
			}
			fclose($h);
		}

		$imported = 0; $skipped = 0; $errors = [];
		$now = date('d-m-Y h:i:s');

		if ($type === 'departments') {
			$DepartmentModel = new DepartmentModel();
			foreach ($rows as $i => $row) {
				$name = strip_tags(trim((string) ($row['department_name'] ?? '')));
				if ($name === '') { $errors[] = 'Row ' . ($i + 2) . ': department_name is required'; continue; }
				$dup = $DepartmentModel->where('company_id', $companyId)->where('department_name', $name)->countAllResults();
				if ($dup > 0) { $skipped++; continue; } // departments are unique by name per company
				$ok = $DepartmentModel->insert([
					'company_id' => $companyId, 'department_name' => $name,
					'department_head' => 0, 'added_by' => (int) $usession['sup_user_id'], 'created_at' => $now,
				]);
				if ($ok) { $imported++; } else { $errors[] = 'Row ' . ($i + 2) . ': could not save "' . $name . '"'; }
			}
		} else { // designations
			$DepartmentModel  = new DepartmentModel();
			$DesignationModel = new DesignationModel();
			foreach ($rows as $i => $row) {
				$name     = strip_tags(trim((string) ($row['designation_name'] ?? '')));
				$deptName = strip_tags(trim((string) ($row['department'] ?? '')));
				if ($name === '') { $errors[] = 'Row ' . ($i + 2) . ': designation_name is required'; continue; }
				// department_id is NOT NULL — the named department must already exist.
				$dept = $deptName !== ''
					? $DepartmentModel->where('company_id', $companyId)->where('department_name', $deptName)->first()
					: null;
				if (! $dept) {
					$errors[] = 'Row ' . ($i + 2) . ': department "' . $deptName . '" not found (create it first)';
					continue;
				}
				$dup = $DesignationModel->where('company_id', $companyId)
					->where('designation_name', $name)->where('department_id', (int) $dept['department_id'])->countAllResults();
				if ($dup > 0) { $skipped++; continue; }
				$ok = $DesignationModel->insert([
					'company_id' => $companyId, 'department_id' => (int) $dept['department_id'],
					'designation_name' => $name, 'description' => '', 'created_at' => $now,
				]);
				if ($ok) { $imported++; } else { $errors[] = 'Row ' . ($i + 2) . ': could not save "' . $name . '"'; }
			}
		}

		try {
			service('audit')->record('data.import', [
				'entity_type' => $type, 'company_id' => $companyId,
				'summary' => "CSV import ($type): $imported imported, $skipped skipped, " . count($errors) . ' errors',
			]);
		} catch (\Throwable $e) {}

		return redirect()->to(site_url('erp/system-import'))
			->with('import_success', "Imported {$imported}, skipped {$skipped}" . (count($errors) ? ', ' . count($errors) . ' error(s)' : '') . '.')
			->with('import_errors', array_slice($errors, 0, 20));
	}
	public function company_settings()
	{		
		$RolesModel = new RolesModel();
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		//$AssetsModel = new AssetsModel();
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
			if(!in_array('company_settings',staff_role_resource())) {
				$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
				return redirect()->to(site_url('erp/desk'));
			}
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Dashboard.dashboard_employees').' | '.$xin_system['application_name'];
		$data['path_url'] = 'employees';
		$data['breadcrumbs'] = lang('Dashboard.dashboard_employees');

		$data['subview'] = view('erp/settings/company_settings', $data);
		return view('erp/layout/layout_main', $data); //page load
	}
	public function company_constants()
	{		
		$RolesModel = new RolesModel();
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		//$AssetsModel = new AssetsModel();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if(!$session->has('sup_username')){ 
			return redirect()->to(site_url('erp/login'));
		}
		if($user_info['user_type'] != 'company' && $user_info['user_type']!='staff'){
			return redirect()->to(site_url('erp/desk'));
		}
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Dashboard.dashboard_employees').' | '.$xin_system['application_name'];
		$data['path_url'] = 'employees';
		$data['breadcrumbs'] = lang('Dashboard.dashboard_employees');

		$data['subview'] = view('erp/settings/company_constants', $data);
		return view('erp/layout/layout_main', $data); //page load
	}
	public function org_chart()
	{		
		$RolesModel = new RolesModel();
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		//$AssetsModel = new AssetsModel();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if(!$session->has('sup_username')){ 
			return redirect()->to(site_url('erp/login'));
		}
		if($user_info['user_type'] != 'company' && $user_info['user_type']!='staff'){
			return redirect()->to(site_url('erp/desk'));
		}
		if($user_info['user_type'] != 'company'){
			if(!in_array('org_chart',staff_role_resource())) {
				$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
				return redirect()->to(site_url('erp/desk'));
			}
		}
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Dashboard.xin_org_chart_title').' | '.$xin_system['application_name'];
		$data['path_url'] = 'chart';
		$data['breadcrumbs'] = lang('Dashboard.xin_org_chart_title');

		$data['subview'] = view('erp/chart/key_chart', $data);
		return view('erp/layout/layout_main', $data); //page load
	}
}
