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
use App\Models\ConstantsModel;
use App\Models\LeaveModel;

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

		$supported = ['departments', 'designations', 'employees', 'attendance', 'leaves'];
		if (! in_array($type, $supported, true)) {
			return redirect()->to(site_url('erp/system-import'))
				->with('import_error', 'Import for that dataset is not available yet.');
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
		} elseif ($type === 'designations') {
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
		} elseif ($type === 'employees') {
			$DepartmentModel   = new DepartmentModel();
			$DesignationModel  = new DesignationModel();
			$UsersModel        = new UsersModel();
			$StaffdetailsModel = new StaffdetailsModel();
			$db      = \Config\Database::connect();
			$shift   = $db->table('ci_office_shifts')->where('company_id', $companyId)
				->orderBy('office_shift_id', 'ASC')->get()->getRowArray();
			$defShift = $shift ? (int) $shift['office_shift_id'] : 0;
			$company     = $UsersModel->where('user_id', $companyId)->first();
			$companyName = $company['company_name'] ?? '';
			$seat = company_employee_limit($companyId);
			$remaining = $seat['limit'] > 0 ? max(0, $seat['limit'] - $seat['current']) : PHP_INT_MAX;
			foreach ($rows as $i => $row) {
				$rn    = $i + 2;
				if ($imported >= $remaining) { $errors[] = "Row $rn: plan employee limit (" . $seat['limit'] . ") reached — upgrade to add more"; continue; }
				$first = strip_tags(trim((string) ($row['first_name'] ?? '')));
				$last  = strip_tags(trim((string) ($row['last_name'] ?? '')));
				$email = strip_tags(trim((string) ($row['email'] ?? '')));
				if ($first === '' || $last === '' || $email === '') { $errors[] = "Row $rn: first_name, last_name and email are required"; continue; }
				if (! filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Row $rn: invalid email"; continue; }
				if ($UsersModel->where('email', $email)->countAllResults() > 0) { $skipped++; continue; }
				$deptName  = strip_tags(trim((string) ($row['department'] ?? '')));
				$desigName = strip_tags(trim((string) ($row['designation'] ?? '')));
				$dept = $deptName !== '' ? $DepartmentModel->where('company_id', $companyId)->where('department_name', $deptName)->first() : null;
				if (! $dept) { $errors[] = "Row $rn: department '$deptName' not found"; continue; }
				$desig = $desigName !== '' ? $DesignationModel->where('company_id', $companyId)->where('designation_name', $desigName)->first() : null;
				if (! $desig) { $errors[] = "Row $rn: designation '$desigName' not found"; continue; }
				$base = preg_replace('/[^a-z0-9._]/', '', strtolower(explode('@', $email)[0])) ?: 'staff';
				$username = $base; $k = 1;
				while ($UsersModel->where('username', $username)->countAllResults() > 0) { $username = $base . $k; $k++; }
				$password = bin2hex(random_bytes(9)); // random — employee sets theirs via password reset
				$uid = $UsersModel->insert([
					'first_name' => $first, 'last_name' => $last, 'email' => $email, 'user_type' => 'staff',
					'username' => $username, 'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
					'contact_number' => strip_tags(trim((string) ($row['contact_number'] ?? ($row['phone'] ?? '')))), 'country' => 0, 'user_role_id' => 0, 'address_1' => '', 'address_2' => '', 'city' => '',
					'profile_photo' => 'default.png', 'state' => '', 'zipcode' => '', 'gender' => 1,
					'company_name' => $companyName, 'trading_name' => '', 'registration_no' => '', 'government_tax' => '',
					'company_type_id' => 0, 'last_login_date' => '0', 'last_logout_date' => '0', 'last_login_ip' => '0',
					'is_logged_in' => '0', 'is_active' => 1, 'company_id' => $companyId, 'created_at' => date('d-m-Y h:i:s'),
				]);
				if (! $uid) { $errors[] = "Row $rn: could not create user"; continue; }
				$join = strip_tags(trim((string) ($row['joining_date'] ?? ''))) ?: date('d-m-Y');
				$StaffdetailsModel->insert([
					'user_id' => (int) $uid, 'employee_id' => (new \App\Libraries\IdCardService())->generateNumber($companyId),
					'department_id' => (int) $dept['department_id'], 'designation_id' => (int) $desig['designation_id'],
					'office_shift_id' => $defShift, 'date_of_joining' => $join, 'date_of_leaving' => '', 'date_of_birth' => '',
					'marital_status' => 0, 'religion_id' => 0, 'blood_group' => '', 'citizenship_id' => 0,
					'basic_salary' => 0, 'hourly_rate' => 0, 'salay_type' => 1, 'leave_categories' => 0,
					'role_description' => '', 'bio' => '', 'experience' => 0, 'fb_profile' => '', 'twitter_profile' => '',
					'gplus_profile' => '', 'linkedin_profile' => '', 'account_title' => '', 'account_number' => '',
					'bank_name' => '', 'iban' => '', 'swift_code' => '', 'bank_branch' => '', 'contact_full_name' => '',
					'contact_phone_no' => '', 'contact_email' => '', 'contact_address' => '', 'created_at' => date('d-m-Y h:i:s'),
				]);
				$imported++;
			}
		} elseif ($type === 'attendance') {
			$UsersModel = new UsersModel();
			$db = \Config\Database::connect();
			foreach ($rows as $i => $row) {
				$rn    = $i + 2;
				$email = strip_tags(trim((string) ($row['employee_email'] ?? '')));
				$date  = strip_tags(trim((string) ($row['date'] ?? '')));
				$in    = strip_tags(trim((string) ($row['clock_in'] ?? '')));
				$out   = strip_tags(trim((string) ($row['clock_out'] ?? '')));
				if ($email === '' || $date === '') { $errors[] = "Row $rn: employee_email and date are required"; continue; }
				$emp = $UsersModel->where('company_id', $companyId)->where('email', $email)->where('user_type', 'staff')->first();
				if (! $emp) { $errors[] = "Row $rn: employee '$email' not found in your company"; continue; }
				$dup = $db->table('ci_timesheet')->where('company_id', $companyId)
					->where('employee_id', (int) $emp['user_id'])->where('attendance_date', $date)->countAllResults();
				if ($dup > 0) { $skipped++; continue; }
				$db->table('ci_timesheet')->insert([
					'company_id' => $companyId, 'employee_id' => (int) $emp['user_id'], 'attendance_date' => $date,
					'clock_in' => $in !== '' ? $in : '00:00:00', 'clock_in_ip_address' => '0',
					'clock_out' => $out, 'clock_out_ip_address' => '0', 'clock_in_out' => ($out !== '' ? '1' : '0'),
					'clock_in_latitude' => '0', 'clock_in_longitude' => '0', 'clock_out_latitude' => '0', 'clock_out_longitude' => '0',
					'time_late' => '0', 'early_leaving' => '0', 'overtime' => '0', 'total_work' => '0', 'total_rest' => '0',
					'attendance_status' => '1',
				]);
				$imported++;
			}
		} else { // leaves
			$UsersModel     = new UsersModel();
			$ConstantsModel = new ConstantsModel();
			$LeaveModel     = new LeaveModel();
			foreach ($rows as $i => $row) {
				$rn    = $i + 2;
				$email = strip_tags(trim((string) ($row['employee_email'] ?? '')));
				$ltype = strip_tags(trim((string) ($row['leave_type'] ?? '')));
				$from  = strip_tags(trim((string) ($row['start_date'] ?? '')));
				$to    = strip_tags(trim((string) ($row['end_date'] ?? '')));
				$reason = strip_tags(trim((string) ($row['reason'] ?? '')));
				if ($email === '' || $ltype === '' || $from === '' || $to === '') {
					$errors[] = "Row $rn: employee_email, leave_type, start_date and end_date are required"; continue;
				}
				$emp = $UsersModel->where('company_id', $companyId)->where('email', $email)->where('user_type', 'staff')->first();
				if (! $emp) { $errors[] = "Row $rn: employee '$email' not found in your company"; continue; }
				$lt = $ConstantsModel->where('company_id', $companyId)->where('type', 'leave_type')
					->where('category_name', $ltype)->first();
				if (! $lt) { $errors[] = "Row $rn: leave type '$ltype' not found (create it first)"; continue; }
				$ok = $LeaveModel->insert([
					'company_id' => $companyId, 'employee_id' => (int) $emp['user_id'],
					'leave_type_id' => (int) $lt['constants_id'], 'from_date' => $from, 'to_date' => $to,
					'reason' => $reason !== '' ? $reason : '-', 'remarks' => '', 'status' => 1, 'is_half_day' => 0,
					'leave_attachment' => '', 'created_at' => date('d-m-Y h:i:s'),
				]);
				if ($ok) { $imported++; } else { $errors[] = "Row $rn: could not save leave"; }
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

	/**
	 * GET erp/import-template/{type} — download a ready-to-fill CSV template for a
	 * dataset. Header row matches exactly what import_process() reads, plus one
	 * example row users can overwrite. Columns follow the common HRIS import layout.
	 */
	public function import_template($type = '')
	{
		$session = \Config\Services::session();
		if (! $session->has('sup_username')) { return redirect()->to(site_url('erp/login')); }
		$type = preg_replace('/[^a-z_]/', '', strtolower((string) $type));

		$templates = [
			'employees' => [
				['first_name', 'last_name', 'email', 'contact_number', 'department', 'designation', 'joining_date'],
				['Jane', 'Doe', 'jane.doe@example.com', '256700000000', 'Human Resources', 'HR Officer', '01-01-2026'],
				['John', 'Okello', 'john.okello@example.com', '256770000000', 'Finance', 'Accountant', '15-01-2026'],
			],
			'departments' => [
				['department_name'],
				['Human Resources'],
				['Finance'],
			],
			'designations' => [
				['designation_name', 'department'],
				['HR Officer', 'Human Resources'],
				['Accountant', 'Finance'],
			],
			'attendance' => [
				['employee_email', 'date', 'clock_in', 'clock_out'],
				['jane.doe@example.com', '2026-01-15', '08:00:00', '17:00:00'],
			],
			'leaves' => [
				['employee_email', 'leave_type', 'start_date', 'end_date', 'reason'],
				['jane.doe@example.com', 'Annual Leave', '2026-02-01', '2026-02-05', 'Family holiday'],
			],
			'contacts' => [
				['first_name', 'last_name', 'email', 'contact_number'],
				['Jane', 'Doe', 'jane.doe@example.com', '256700000000'],
				['John', 'Okello', 'john.okello@example.com', '256770000000'],
			],
		];
		if (! isset($templates[$type])) {
			return redirect()->to(site_url('erp/system-import'))->with('import_error', 'Unknown template.');
		}

		$fh = fopen('php://temp', 'r+');
		foreach ($templates[$type] as $line) { fputcsv($fh, $line); }
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);

		return $this->response
			->setHeader('Content-Type', 'text/csv; charset=UTF-8')
			->setHeader('Content-Disposition', 'attachment; filename="' . $type . '_import_template.csv"')
			->setBody($csv);
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
