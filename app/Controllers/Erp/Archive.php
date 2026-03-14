<?php
/**
 * Phase 10.7–10.8: Super Admin Archive Portal
 *
 * Provides dashboard, search, contacts, vault and restore-to-live
 * functionality for the Tier 2 archive database.
 */
namespace App\Controllers\Erp;
use App\Controllers\BaseController;

use App\Models\UsersModel;
use App\Models\SystemModel;

class Archive extends BaseController {

	/**
	 * Shared guard — every public method must call this first.
	 * Returns user_info array on success or redirects on failure.
	 */
	private function requireSuperUser()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (!$session->has('sup_username')) {
			$session->setFlashdata('err_not_logged_in', lang('Dashboard.err_not_logged_in'));
			return redirect()->to(site_url('erp/login'));
		}
		$UsersModel = new UsersModel();
		$user_info  = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if (!$user_info || $user_info['user_type'] !== 'super_user') {
			$session->setFlashdata('unauthorized_module', lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}
		return $user_info;
	}

	/**
	 * Return a query builder on the archive DB connection.
	 */
	private function archiveDb()
	{
		return \Config\Database::connect('archive');
	}

	// ----------------------------------------------------------------
	//  10.7 — Dashboard
	// ----------------------------------------------------------------
	public function index()
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$SystemModel = new SystemModel();
		$UsersModel  = new UsersModel();
		$xin_system  = $SystemModel->where('setting_id', 1)->first();
		$arc         = $this->archiveDb();

		// Tier 1 — live DB stats
		$live_companies = $UsersModel->where('user_type', 'company')->countAllResults();
		$live_active    = $UsersModel->where('user_type', 'company')->where('is_active', 1)->countAllResults();

		// Tier 2 — archive record counts
		$arc_snapshots  = $arc->table('arc_company_snapshots')->countAllResults();
		$arc_employees  = $arc->table('arc_employees')->countAllResults();
		$arc_attendance = $arc->table('arc_attendance')->countAllResults();
		$arc_payroll    = $arc->table('arc_payroll')->countAllResults();
		$arc_leaves     = $arc->table('arc_leaves')->countAllResults();
		$arc_contacts   = $arc->table('arc_contacts')->countAllResults();

		// Tier 3 — vault file counts
		$arc_vault = $arc->table('arc_company_snapshots')
						 ->where('vault_bundle_path IS NOT NULL')
						 ->countAllResults();

		// Last rotation date
		$last_rotation = $arc->table('arc_company_snapshots')
							 ->orderBy('archived_at', 'DESC')
							 ->limit(1)
							 ->get()->getRowArray();
		$last_rotation_date = $last_rotation ? $last_rotation['archived_at'] : 'Never';

		$data = [
			'title'              => 'Archive Portal | ' . $xin_system['application_name'],
			'path_url'           => 'archive_dashboard',
			'breadcrumbs'        => 'Archive Portal',
			'live_companies'     => $live_companies,
			'live_active'        => $live_active,
			'arc_snapshots'      => $arc_snapshots,
			'arc_employees'      => $arc_employees,
			'arc_attendance'     => $arc_attendance,
			'arc_payroll'        => $arc_payroll,
			'arc_leaves'         => $arc_leaves,
			'arc_contacts'       => $arc_contacts,
			'arc_vault'          => $arc_vault,
			'last_rotation_date' => $last_rotation_date,
		];
		$data['subview'] = view('erp/archive/dashboard', $data);
		return view('erp/layout/layout_main', $data);
	}

	// ----------------------------------------------------------------
	//  Archived companies list
	// ----------------------------------------------------------------
	public function companies()
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$SystemModel = new SystemModel();
		$xin_system  = $SystemModel->where('setting_id', 1)->first();
		$arc         = $this->archiveDb();

		$snapshots = $arc->table('arc_company_snapshots')
						 ->orderBy('archived_at', 'DESC')
						 ->get()->getResultArray();

		$data = [
			'title'       => 'Archived Companies | ' . $xin_system['application_name'],
			'path_url'    => 'archive_companies',
			'breadcrumbs' => 'Archived Companies',
			'snapshots'   => $snapshots,
		];
		$data['subview'] = view('erp/archive/companies', $data);
		return view('erp/layout/layout_main', $data);
	}

	// ----------------------------------------------------------------
	//  Single company snapshot detail
	// ----------------------------------------------------------------
	public function company_detail($snapshotId)
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$SystemModel = new SystemModel();
		$xin_system  = $SystemModel->where('setting_id', 1)->first();
		$arc         = $this->archiveDb();

		$snapshot = $arc->table('arc_company_snapshots')
						->where('snapshot_id', $snapshotId)
						->get()->getRowArray();
		if (!$snapshot) {
			return redirect()->to(site_url('erp/archive/companies'));
		}

		$employees  = $arc->table('arc_employees')->where('snapshot_id', $snapshotId)->orderBy('last_name', 'ASC')->get()->getResultArray();
		$attendance = $arc->table('arc_attendance')->where('source_company_id', $snapshot['source_company_id'])->orderBy('attendance_date', 'DESC')->limit(500)->get()->getResultArray();
		$payroll    = $arc->table('arc_payroll')->where('source_company_id', $snapshot['source_company_id'])->orderBy('payroll_month', 'DESC')->limit(500)->get()->getResultArray();
		$leaves     = $arc->table('arc_leaves')->where('source_company_id', $snapshot['source_company_id'])->orderBy('start_date', 'DESC')->limit(500)->get()->getResultArray();

		$data = [
			'title'       => 'Company Archive: ' . $snapshot['company_name'] . ' | ' . $xin_system['application_name'],
			'path_url'    => 'archive_company_detail',
			'breadcrumbs' => 'Company Archive Detail',
			'snapshot'    => $snapshot,
			'employees'   => $employees,
			'attendance'  => $attendance,
			'payroll'     => $payroll,
			'leaves'      => $leaves,
		];
		$data['subview'] = view('erp/archive/company_detail', $data);
		return view('erp/layout/layout_main', $data);
	}

	// ----------------------------------------------------------------
	//  Cross-table search
	// ----------------------------------------------------------------
	public function search()
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$SystemModel = new SystemModel();
		$xin_system  = $SystemModel->where('setting_id', 1)->first();
		$arc         = $this->archiveDb();
		$request     = \Config\Services::request();

		$company_id   = $request->getGet('company_id');
		$employee_name = $request->getGet('employee_name');
		$date_from    = $request->getGet('date_from');
		$date_to      = $request->getGet('date_to');
		$record_type  = $request->getGet('record_type') ?: 'all';

		$results = [];
		$has_search = ($company_id || $employee_name || $date_from || $date_to);

		if ($has_search) {
			// Search attendance
			if ($record_type === 'all' || $record_type === 'attendance') {
				$qb = $arc->table('arc_attendance');
				if ($company_id)    $qb->where('source_company_id', $company_id);
				if ($employee_name) $qb->like('employee_name', $employee_name);
				if ($date_from)     $qb->where('attendance_date >=', $date_from);
				if ($date_to)       $qb->where('attendance_date <=', $date_to);
				$rows = $qb->limit(200)->get()->getResultArray();
				foreach ($rows as &$r) $r['_type'] = 'Attendance';
				$results = array_merge($results, $rows);
			}
			// Search payroll
			if ($record_type === 'all' || $record_type === 'payroll') {
				$qb = $arc->table('arc_payroll');
				if ($company_id)    $qb->where('source_company_id', $company_id);
				if ($employee_name) $qb->like('employee_name', $employee_name);
				if ($date_from)     $qb->where('payroll_month >=', substr($date_from, 0, 7));
				if ($date_to)       $qb->where('payroll_month <=', substr($date_to, 0, 7));
				$rows = $qb->limit(200)->get()->getResultArray();
				foreach ($rows as &$r) $r['_type'] = 'Payroll';
				$results = array_merge($results, $rows);
			}
			// Search leaves
			if ($record_type === 'all' || $record_type === 'leaves') {
				$qb = $arc->table('arc_leaves');
				if ($company_id)    $qb->where('source_company_id', $company_id);
				if ($employee_name) $qb->like('employee_name', $employee_name);
				if ($date_from)     $qb->where('start_date >=', $date_from);
				if ($date_to)       $qb->where('end_date <=', $date_to);
				$rows = $qb->limit(200)->get()->getResultArray();
				foreach ($rows as &$r) $r['_type'] = 'Leave';
				$results = array_merge($results, $rows);
			}
			// Search employees
			if ($record_type === 'all' || $record_type === 'employees') {
				$qb = $arc->table('arc_employees');
				if ($company_id) $qb->where('source_company_id', $company_id);
				if ($employee_name) {
					$qb->groupStart()
						->like('first_name', $employee_name)
						->orLike('last_name', $employee_name)
					->groupEnd();
				}
				$rows = $qb->limit(200)->get()->getResultArray();
				foreach ($rows as &$r) $r['_type'] = 'Employee';
				$results = array_merge($results, $rows);
			}
		}

		// Get list of archived companies for filter dropdown
		$companies = $arc->table('arc_company_snapshots')
						 ->select('source_company_id, company_name')
						 ->groupBy('source_company_id, company_name')
						 ->get()->getResultArray();

		$data = [
			'title'         => 'Archive Search | ' . $xin_system['application_name'],
			'path_url'      => 'archive_search',
			'breadcrumbs'   => 'Archive Search',
			'results'       => $results,
			'has_search'    => $has_search,
			'companies'     => $companies,
			'f_company_id'  => $company_id,
			'f_employee'    => $employee_name,
			'f_date_from'   => $date_from,
			'f_date_to'     => $date_to,
			'f_record_type' => $record_type,
		];
		$data['subview'] = view('erp/archive/search', $data);
		return view('erp/layout/layout_main', $data);
	}

	// ----------------------------------------------------------------
	//  Contacts — Marketing intelligence
	// ----------------------------------------------------------------
	public function contacts()
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$SystemModel = new SystemModel();
		$xin_system  = $SystemModel->where('setting_id', 1)->first();

		$data = [
			'title'       => 'Archive Contacts | ' . $xin_system['application_name'],
			'path_url'    => 'archive_contacts',
			'breadcrumbs' => 'Marketing Contacts',
		];
		$data['subview'] = view('erp/archive/contacts', $data);
		return view('erp/layout/layout_main', $data);
	}

	/**
	 * AJAX DataTable JSON for contacts
	 */
	public function contacts_list()
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$arc     = $this->archiveDb();
		$request = \Config\Services::request();

		$qb = $arc->table('arc_contacts');

		// filters
		$status   = $request->getGet('status');
		$region   = $request->getGet('region');
		$city     = $request->getGet('city');
		$plan     = $request->getGet('plan_tier');
		$industry = $request->getGet('industry');
		$emp_min  = $request->getGet('emp_min');
		$emp_max  = $request->getGet('emp_max');
		$consent  = $request->getGet('consent');

		if ($status)   $qb->where('status', $status);
		if ($region)   $qb->like('region', $region);
		if ($city)     $qb->like('city', $city);
		if ($plan)     $qb->where('plan_tier', $plan);
		if ($industry) $qb->like('industry', $industry);
		if ($emp_min)  $qb->where('employee_count >=', (int)$emp_min);
		if ($emp_max)  $qb->where('employee_count <=', (int)$emp_max);
		if ($consent !== null && $consent !== '') $qb->where('consent_given', (int)$consent);

		$rows = $qb->orderBy('created_at', 'DESC')->limit(1000)->get()->getResultArray();

		$data = [];
		foreach ($rows as $r) {
			$consent_badge = $r['consent_given']
				? '<span class="badge badge-success">Yes</span>'
				: '<span class="badge badge-secondary">No</span>';
			$unsub_badge = $r['unsubscribed']
				? '<span class="badge badge-danger">Unsubscribed</span>'
				: '<span class="badge badge-light">Active</span>';
			$data[] = [
				$r['full_name'] ?: ($r['first_name'] . ' ' . $r['last_name']),
				$r['email'],
				$r['phone'] ?: '--',
				$r['company_name'] ?: '--',
				$r['country'] . ($r['city'] ? ', ' . $r['city'] : ''),
				$r['industry'] ?: '--',
				$r['plan_tier'] ?: '--',
				$r['employee_count'] ?: '--',
				$r['status'] ?: '--',
				$consent_badge,
				$unsub_badge,
			];
		}
		echo json_encode(['data' => $data]);
		exit();
	}

	// ----------------------------------------------------------------
	//  Vault — bundle file listing
	// ----------------------------------------------------------------
	public function vault()
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$SystemModel = new SystemModel();
		$xin_system  = $SystemModel->where('setting_id', 1)->first();
		$arc         = $this->archiveDb();

		$bundles = $arc->table('arc_company_snapshots')
					   ->where('vault_bundle_path IS NOT NULL')
					   ->orderBy('archived_at', 'DESC')
					   ->get()->getResultArray();

		// Add file size if available on disk
		foreach ($bundles as &$b) {
			$b['file_size'] = '--';
			if (!empty($b['vault_bundle_path']) && file_exists($b['vault_bundle_path'])) {
				$b['file_size'] = number_to_size(filesize($b['vault_bundle_path']), 2);
			}
		}

		$data = [
			'title'       => 'Archive Vault | ' . $xin_system['application_name'],
			'path_url'    => 'archive_vault',
			'breadcrumbs' => 'Vault Bundles',
			'bundles'     => $bundles,
		];
		$data['subview'] = view('erp/archive/vault', $data);
		return view('erp/layout/layout_main', $data);
	}

	// ----------------------------------------------------------------
	//  Download vault bundle ZIP
	// ----------------------------------------------------------------
	public function download_bundle($snapshotId)
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$arc      = $this->archiveDb();
		$snapshot = $arc->table('arc_company_snapshots')
						->where('snapshot_id', $snapshotId)
						->get()->getRowArray();

		if (!$snapshot || empty($snapshot['vault_bundle_path'])) {
			return redirect()->to(site_url('erp/archive/vault'));
		}

		$filepath = $snapshot['vault_bundle_path'];
		if (!file_exists($filepath)) {
			$session = \Config\Services::session();
			$session->setFlashdata('error_msg', 'Vault bundle file not found on disk.');
			return redirect()->to(site_url('erp/archive/vault'));
		}

		return $this->response->download($filepath, null)->setFileName(
			'vault_' . $snapshot['source_company_id'] . '_' . date('Ymd', strtotime($snapshot['archived_at'])) . '.zip'
		);
	}

	// ----------------------------------------------------------------
	//  Settings page (placeholder — retention, B2, toggle)
	// ----------------------------------------------------------------
	public function settings()
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$SystemModel = new SystemModel();
		$xin_system  = $SystemModel->where('setting_id', 1)->first();

		$data = [
			'title'       => 'Archive Settings | ' . $xin_system['application_name'],
			'path_url'    => 'archive_settings',
			'breadcrumbs' => 'Archive Settings',
			'xin_system'  => $xin_system,
		];
		$data['subview'] = view('erp/archive/settings', $data);
		return view('erp/layout/layout_main', $data);
	}

	// ----------------------------------------------------------------
	//  Trigger archive manually (POST)
	// ----------------------------------------------------------------
	public function trigger_archive()
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$Return = ['result' => '', 'error' => '', 'csrf_hash' => csrf_hash()];

		try {
			// Call the archive:company CLI command
			$output = [];
			$code   = 0;
			exec('php ' . FCPATH . '../spark archive:company 2>&1', $output, $code);

			if ($code === 0) {
				$Return['result'] = 'Archive process completed successfully. ' . implode(' ', $output);
			} else {
				$Return['result'] = 'Archive process finished with warnings. ' . implode(' ', $output);
			}
		} catch (\Exception $e) {
			$Return['error'] = 'Archive process error: ' . $e->getMessage();
		}

		$this->output($Return);
	}

	// ----------------------------------------------------------------
	//  10.8 — Restore company to live
	// ----------------------------------------------------------------
	public function restore_company($snapshotId)
	{
		$guard = $this->requireSuperUser();
		if ($guard instanceof \CodeIgniter\HTTP\RedirectResponse) return $guard;

		$Return = ['result' => '', 'error' => '', 'csrf_hash' => csrf_hash()];

		$arc      = $this->archiveDb();
		$liveDb   = \Config\Database::connect('default');
		$request  = \Config\Services::request();

		// 1. Read snapshot
		$snapshot = $arc->table('arc_company_snapshots')
						->where('snapshot_id', $snapshotId)
						->get()->getRowArray();

		if (!$snapshot) {
			$Return['error'] = 'Snapshot not found.';
			$this->output($Return);
			return;
		}

		// Check if already restored
		if (!empty($snapshot['restored_at'])) {
			$Return['error'] = 'This company snapshot has already been restored.';
			$this->output($Return);
			return;
		}

		// Get new expiry from form
		$new_expiry = $request->getPost('new_expiry');
		if (empty($new_expiry)) {
			$new_expiry = date('Y-m-d', strtotime('+1 year'));
		}

		$liveDb->transStart();

		try {
			// 2. Re-create user record in ci_erp_users
			$user_data = [
				'first_name'  => $snapshot['admin_first_name'],
				'last_name'   => $snapshot['admin_last_name'],
				'email'       => $snapshot['admin_email'],
				'contact_no'  => $snapshot['admin_phone'],
				'user_type'   => 'company',
				'is_active'   => 1,
				'created_at'  => date('Y-m-d H:i:s'),
			];
			$liveDb->table('ci_erp_users')->insert($user_data);
			$new_company_id = $liveDb->insertID();

			// 3. Re-create company settings
			$company_settings = [
				'company_id'      => $new_company_id,
				'company_name'    => $snapshot['company_name'],
				'trading_name'    => $snapshot['trading_name'] ?? '',
				'company_type'    => $snapshot['company_type'] ?? '',
				'registration_no' => $snapshot['registration_no'] ?? '',
				'country'         => $snapshot['country'] ?? '',
				'city'            => $snapshot['city'] ?? '',
			];
			$liveDb->table('ci_company_info')->insert($company_settings);

			// Re-create subscription
			$sub_data = [
				'company_id'       => $new_company_id,
				'plan_name'        => $snapshot['plan_name'] ?? 'Basic',
				'plan_tier'        => $snapshot['plan_tier'] ?? 'free',
				'subscription_end' => $new_expiry,
				'is_active'        => 1,
				'created_at'       => date('Y-m-d H:i:s'),
			];
			$liveDb->table('ci_membership_invoices')->insert($sub_data);

			// 4. Restore employees from arc_employees
			$employees = $arc->table('arc_employees')
							 ->where('snapshot_id', $snapshotId)
							 ->get()->getResultArray();

			$employee_id_map = []; // old_id => new_id
			foreach ($employees as $emp) {
				$emp_data = [
					'first_name'      => $emp['first_name'],
					'last_name'       => $emp['last_name'],
					'email'           => $emp['email'],
					'contact_no'      => $emp['phone'] ?? '',
					'department_id'   => 0,
					'designation_id'  => 0,
					'employment_type' => $emp['employment_type'] ?? '',
					'date_of_joining' => $emp['date_joined'],
					'user_type'       => 'staff',
					'company_id'      => $new_company_id,
					'is_active'       => 1,
					'created_at'      => date('Y-m-d H:i:s'),
				];
				$liveDb->table('ci_erp_users')->insert($emp_data);
				$new_emp_id = $liveDb->insertID();
				$employee_id_map[$emp['source_record_id']] = $new_emp_id;
			}

			// 5. Restore attendance records
			$att_records = $arc->table('arc_attendance')
							   ->where('source_company_id', $snapshot['source_company_id'])
							   ->get()->getResultArray();
			foreach ($att_records as $att) {
				$mapped_emp_id = $employee_id_map[$att['employee_id']] ?? $att['employee_id'];
				$att_data = [
					'employee_id'       => $mapped_emp_id,
					'company_id'        => $new_company_id,
					'attendance_date'   => $att['attendance_date'],
					'clock_in'          => $att['clock_in'],
					'clock_out'         => $att['clock_out'],
					'total_work'        => $att['total_work'] ?? '',
					'attendance_status' => $att['attendance_status'] ?? 'present',
				];
				$liveDb->table('ci_attendance')->insert($att_data);
			}

			// Restore payroll records
			$pay_records = $arc->table('arc_payroll')
							   ->where('source_company_id', $snapshot['source_company_id'])
							   ->get()->getResultArray();
			foreach ($pay_records as $pay) {
				$mapped_emp_id = $employee_id_map[$pay['employee_id']] ?? $pay['employee_id'];
				$pay_data = [
					'employee_id'   => $mapped_emp_id,
					'company_id'    => $new_company_id,
					'salary_month'  => $pay['payroll_month'],
					'basic_salary'  => $pay['gross_salary'] ?? 0,
					'paye'          => $pay['paye_deduction'] ?? 0,
					'nssf_employee' => $pay['nssf_employee'] ?? 0,
					'nssf_employer' => $pay['nssf_employer'] ?? 0,
					'net_salary'    => $pay['net_pay'] ?? 0,
				];
				$liveDb->table('ci_payroll')->insert($pay_data);
			}

			// Restore leave records
			$leave_records = $arc->table('arc_leaves')
								 ->where('source_company_id', $snapshot['source_company_id'])
								 ->get()->getResultArray();
			foreach ($leave_records as $lv) {
				$lv_data = [
					'company_id'   => $new_company_id,
					'leave_type'   => $lv['leave_type'] ?? '',
					'date_from'    => $lv['start_date'],
					'date_to'      => $lv['end_date'],
					'days'         => $lv['days_taken'] ?? 0,
					'leave_status' => $lv['status'] ?? 'approved',
				];
				$liveDb->table('ci_leave')->insert($lv_data);
			}

			// 6. Set new expiry — already done above in subscription insert

			// 7. Mark snapshot as restored
			$arc->table('arc_company_snapshots')
				->where('snapshot_id', $snapshotId)
				->update([
					'restored_at'      => date('Y-m-d H:i:s'),
					'restored_as_id'   => $new_company_id,
				]);

			$liveDb->transComplete();

			if ($liveDb->transStatus() === false) {
				$Return['error'] = 'Restore failed — transaction rolled back.';
				$this->output($Return);
				return;
			}

			// 8. Send welcome-back email
			try {
				$email = \Config\Services::email();
				$email->setTo($snapshot['admin_email']);
				$email->setSubject('Welcome Back to Rooibok HR');
				$email->setMessage(
					'<p>Dear ' . esc($snapshot['admin_first_name']) . ',</p>' .
					'<p>Your company <strong>' . esc($snapshot['company_name']) . '</strong> has been restored to the Rooibok HR system.</p>' .
					'<p>Your new subscription expires on <strong>' . esc($new_expiry) . '</strong>.</p>' .
					'<p>Please log in with your previous credentials or reset your password.</p>' .
					'<p>Thank you for returning!</p>'
				);
				$email->send();
			} catch (\Exception $e) {
				// Non-fatal — log but do not fail the restore
				log_message('error', 'Archive restore welcome email failed: ' . $e->getMessage());
			}

			$Return['result'] = 'Company "' . $snapshot['company_name'] . '" restored successfully as ID #' . $new_company_id . '. Welcome-back email sent.';

		} catch (\Exception $e) {
			$liveDb->transRollback();
			$Return['error'] = 'Restore failed: ' . $e->getMessage();
		}

		$this->output($Return);
	}
}
