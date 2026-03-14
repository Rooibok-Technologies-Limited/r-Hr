<?php
/**
 * Phase 7.3: Expense Claims Module
 */
namespace App\Controllers\Erp;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Files\UploadedFile;

use App\Models\MainModel;
use App\Models\RolesModel;
use App\Models\UsersModel;
use App\Models\SystemModel;
use App\Models\ExpenseModel;
use App\Models\ExpenseCategoryModel;

class Expenses extends BaseController {

	// Expense list page (staff sees own, company sees all)
	public function index()
	{
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			$session->setFlashdata('err_not_logged_in',lang('Dashboard.err_not_logged_in'));
			return redirect()->to(site_url('erp/login'));
		}
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if($user_info['user_type'] != 'company' && $user_info['user_type'] != 'staff'){
			$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}
		if($user_info['user_type'] != 'company'){
			if(!in_array('expense2',staff_role_resource())) {
				$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
				return redirect()->to(site_url('erp/desk'));
			}
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'Expense Claims | '.$xin_system['application_name'];
		$data['path_url'] = 'expenses';
		$data['breadcrumbs'] = 'Expense Claims';

		$data['subview'] = view('erp/expenses/expense_list', $data);
		return view('erp/layout/layout_main', $data);
	}

	// AJAX DataTable data
	public function expenses_list()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			return redirect()->to(site_url('erp/login'));
		}
		$UsersModel = new UsersModel();
		$ExpenseModel = new ExpenseModel();
		$ExpenseCategoryModel = new ExpenseCategoryModel();
		$xin_system = erp_company_settings();
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

		if($user_info['user_type'] == 'staff'){
			$get_data = $ExpenseModel->where('employee_id', $user_info['user_id'])->orderBy('expense_id', 'DESC')->findAll();
		} else {
			$get_data = $ExpenseModel->where('company_id', $usession['sup_user_id'])->orderBy('expense_id', 'DESC')->findAll();
		}

		$data = array();
		foreach($get_data as $r) {
			// employee name
			$iuser_info = $UsersModel->where('user_id', $r['employee_id'])->first();
			if($iuser_info){
				$employee_name = $iuser_info['first_name'].' '.$iuser_info['last_name'];
			} else {
				$employee_name = '--';
			}
			// category
			$cat_info = $ExpenseCategoryModel->where('category_id', $r['category_id'])->first();
			$category_name = $cat_info ? $cat_info['category_name'] : '--';

			// amount
			$amount = number_to_currency($r['amount'], $r['currency'] ?? $xin_system['default_currency'], null, 2);

			// receipt link
			if(!empty($r['receipt_path'])){
				$receipt = '<a href="'.site_url('public/uploads/expenses/'.$r['receipt_path']).'" target="_blank" class="btn btn-sm btn-light-info"><i class="feather icon-paperclip"></i></a>';
			} else {
				$receipt = '--';
			}

			// status badge
			if($r['status'] == 'approved'){
				$status = '<span class="badge badge-success">Approved</span>';
			} elseif($r['status'] == 'rejected'){
				$status = '<span class="badge badge-danger">Rejected</span>';
			} else {
				$status = '<span class="badge badge-warning">Pending</span>';
			}

			// actions
			$actions = '';
			if($r['status'] == 'pending'){
				if(in_array('expense3',staff_role_resource()) || $user_info['user_type'] == 'company'){
					$actions .= '<button type="button" class="btn icon-btn btn-sm btn-light-success waves-effect waves-light approve-expense" data-record-id="'.uencode($r['expense_id']).'" data-toggle="tooltip" title="Approve"><i class="feather icon-check"></i></button> ';
					$actions .= '<button type="button" class="btn icon-btn btn-sm btn-light-warning waves-effect waves-light reject-expense" data-record-id="'.uencode($r['expense_id']).'" data-toggle="tooltip" title="Reject"><i class="feather icon-x"></i></button> ';
				}
				if(in_array('expense2',staff_role_resource()) || $user_info['user_type'] == 'company'){
					$actions .= '<button type="button" class="btn icon-btn btn-sm btn-light-danger waves-effect waves-light delete-expense" data-record-id="'.uencode($r['expense_id']).'" data-toggle="modal" data-target=".delete-modal" title="Delete"><i class="feather icon-trash-2"></i></button>';
				}
			}

			$data[] = array(
				$r['expense_date'],
				$employee_name,
				$category_name,
				$amount,
				$receipt,
				$status,
				$actions,
			);
		}
		$output = array("data" => $data);
		echo json_encode($output);
		exit();
	}

	// POST - staff submits expense
	public function add_expense()
	{
		$validation = \Config\Services::validation();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			return redirect()->to(site_url('erp/login'));
		}
		if($this->request->getPost('type') === 'add_record'){
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();

			$rules = [
				'amount' => [
					'rules'  => 'required|numeric',
					'errors' => ['required' => 'Amount is required', 'numeric' => 'Amount must be a number']
				],
				'expense_date' => [
					'rules'  => 'required',
					'errors' => ['required' => 'Expense date is required']
				],
				'description' => [
					'rules'  => 'required',
					'errors' => ['required' => 'Description is required']
				]
			];
			if(!$this->validate($rules)){
				$ruleErrors = [
					"amount" => $validation->getError('amount'),
					"expense_date" => $validation->getError('expense_date'),
					"description" => $validation->getError('description'),
				];
				foreach($ruleErrors as $err){
					$Return['error'] = $err;
					if($Return['error'] != ''){
						$this->output($Return);
					}
				}
			} else {
				// upload receipt
				$file_name = '';
				$validated_file = $this->validate([
					'receipt' => [
						'rules'  => 'uploaded[receipt]|mime_in[receipt,image/jpg,image/jpeg,image/png,application/pdf]|max_size[receipt,5120]',
						'errors' => [
							'uploaded' => 'Receipt file is required',
							'mime_in' => 'Invalid file type (JPG, PNG, PDF only)',
							'max_size' => 'File too large (max 5MB)'
						]
					]
				]);
				if($validated_file){
					$receipt = $this->request->getFile('receipt');
					$file_name = $receipt->getRandomName();
					$receipt->move('public/uploads/expenses/', $file_name);
				}

				$UsersModel = new UsersModel();
				$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

				if($user_info['user_type'] == 'staff'){
					$employee_id = $usession['sup_user_id'];
					$company_id = $user_info['company_id'];
				} else {
					$employee_id = strip_tags(trim($this->request->getPost('employee_id')));
					$company_id = $usession['sup_user_id'];
				}

				$data = [
					'company_id'   => $company_id,
					'employee_id'  => $employee_id,
					'category_id'  => strip_tags(trim($this->request->getPost('category_id'))),
					'amount'       => strip_tags(trim($this->request->getPost('amount'))),
					'currency'     => strip_tags(trim($this->request->getPost('currency'))) ?: 'UGX',
					'description'  => strip_tags(trim($this->request->getPost('description'))),
					'expense_date' => strip_tags(trim($this->request->getPost('expense_date'))),
					'receipt_path' => $file_name,
					'status'       => 'pending',
					'created_at'   => date('Y-m-d H:i:s'),
				];

				$ExpenseModel = new ExpenseModel();
				$result = $ExpenseModel->insert($data);
				$Return['csrf_hash'] = csrf_hash();
				if($result == TRUE){
					$Return['result'] = 'Expense claim submitted successfully.';
				} else {
					$Return['error'] = lang('Main.xin_error_msg');
				}
				$this->output($Return);
				exit;
			}
		} else {
			$Return = array('result'=>'', 'error'=>lang('Main.xin_error_msg'), 'csrf_hash'=>'');
			$this->output($Return);
			exit;
		}
	}

	// POST - manager/company admin approves
	public function approve_expense()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			return redirect()->to(site_url('erp/login'));
		}
		if($this->request->getPost('type') === 'approve_record'){
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();

			$UsersModel = new UsersModel();
			$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

			// only company admin or staff with expense3 permission
			if($user_info['user_type'] != 'company' && !in_array('expense3', staff_role_resource())){
				$Return['error'] = lang('Dashboard.xin_error_unauthorized_module');
				$this->output($Return);
				exit;
			}

			$id = udecode(strip_tags(trim($this->request->getPost('_token'))));
			$data = [
				'status'       => 'approved',
				'approved_by'  => $usession['sup_user_id'],
				'approved_at'  => date('Y-m-d H:i:s'),
				'payroll_month'=> date('Y-m'),
			];

			$ExpenseModel = new ExpenseModel();
			$result = $ExpenseModel->update($id, $data);
			$Return['csrf_hash'] = csrf_hash();
			if($result == TRUE){
				$Return['result'] = 'Expense claim approved successfully.';
			} else {
				$Return['error'] = lang('Main.xin_error_msg');
			}
			$this->output($Return);
			exit;
		} else {
			$Return = array('result'=>'', 'error'=>lang('Main.xin_error_msg'), 'csrf_hash'=>'');
			$this->output($Return);
			exit;
		}
	}

	// POST - reject with reason
	public function reject_expense()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			return redirect()->to(site_url('erp/login'));
		}
		if($this->request->getPost('type') === 'reject_record'){
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();

			$UsersModel = new UsersModel();
			$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

			if($user_info['user_type'] != 'company' && !in_array('expense3', staff_role_resource())){
				$Return['error'] = lang('Dashboard.xin_error_unauthorized_module');
				$this->output($Return);
				exit;
			}

			$id = udecode(strip_tags(trim($this->request->getPost('_token'))));
			$data = [
				'status' => 'rejected',
			];

			$ExpenseModel = new ExpenseModel();
			$result = $ExpenseModel->update($id, $data);
			$Return['csrf_hash'] = csrf_hash();
			if($result == TRUE){
				$Return['result'] = 'Expense claim rejected.';
			} else {
				$Return['error'] = lang('Main.xin_error_msg');
			}
			$this->output($Return);
			exit;
		} else {
			$Return = array('result'=>'', 'error'=>lang('Main.xin_error_msg'), 'csrf_hash'=>'');
			$this->output($Return);
			exit;
		}
	}

	// POST - delete (pending only)
	public function delete_expense()
	{
		if($this->request->getPost('type') == 'delete_record'){
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$session = \Config\Services::session();
			$usession = $session->get('sup_username');
			$id = udecode(strip_tags(trim($this->request->getPost('_token'))));
			$Return['csrf_hash'] = csrf_hash();

			$ExpenseModel = new ExpenseModel();
			$expense = $ExpenseModel->where('expense_id', $id)->first();

			if(!$expense || $expense['status'] != 'pending'){
				$Return['error'] = 'Only pending expenses can be deleted.';
				$this->output($Return);
				exit;
			}

			// delete receipt file if exists
			if(!empty($expense['receipt_path'])){
				$file_path = 'public/uploads/expenses/'.$expense['receipt_path'];
				if(file_exists($file_path)){
					unlink($file_path);
				}
			}

			$result = $ExpenseModel->where('expense_id', $id)->delete($id);
			if($result == TRUE){
				$Return['result'] = 'Expense claim deleted successfully.';
			} else {
				$Return['error'] = lang('Main.xin_error_msg');
			}
			$this->output($Return);
		}
	}

	// Category management page
	public function categories()
	{
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			$session->setFlashdata('err_not_logged_in',lang('Dashboard.err_not_logged_in'));
			return redirect()->to(site_url('erp/login'));
		}
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if($user_info['user_type'] != 'company'){
			$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'Expense Categories | '.$xin_system['application_name'];
		$data['path_url'] = 'expense_categories';
		$data['breadcrumbs'] = 'Expense Categories';

		$data['subview'] = view('erp/expenses/expense_categories', $data);
		return view('erp/layout/layout_main', $data);
	}

	// POST - add category
	public function add_category()
	{
		$validation = \Config\Services::validation();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			return redirect()->to(site_url('erp/login'));
		}
		if($this->request->getPost('type') === 'add_record'){
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();

			$rules = [
				'category_name' => [
					'rules'  => 'required',
					'errors' => ['required' => 'Category name is required']
				]
			];
			if(!$this->validate($rules)){
				$Return['error'] = $validation->getError('category_name');
				$this->output($Return);
			} else {
				$UsersModel = new UsersModel();
				$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
				$company_id = ($user_info['user_type'] == 'staff') ? $user_info['company_id'] : $usession['sup_user_id'];

				$data = [
					'company_id'    => $company_id,
					'category_name' => strip_tags(trim($this->request->getPost('category_name'))),
					'is_active'     => 1,
				];

				$ExpenseCategoryModel = new ExpenseCategoryModel();
				$result = $ExpenseCategoryModel->insert($data);
				$Return['csrf_hash'] = csrf_hash();
				if($result == TRUE){
					$Return['result'] = 'Category added successfully.';
				} else {
					$Return['error'] = lang('Main.xin_error_msg');
				}
				$this->output($Return);
				exit;
			}
		} else {
			$Return = array('result'=>'', 'error'=>lang('Main.xin_error_msg'), 'csrf_hash'=>'');
			$this->output($Return);
			exit;
		}
	}

	// POST - update category
	public function update_category()
	{
		$validation = \Config\Services::validation();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			return redirect()->to(site_url('erp/login'));
		}
		if($this->request->getPost('type') === 'edit_record'){
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();

			$rules = [
				'category_name' => [
					'rules'  => 'required',
					'errors' => ['required' => 'Category name is required']
				]
			];
			if(!$this->validate($rules)){
				$Return['error'] = $validation->getError('category_name');
				$this->output($Return);
			} else {
				$id = udecode(strip_tags(trim($this->request->getPost('token'))));
				$data = [
					'category_name' => strip_tags(trim($this->request->getPost('category_name'))),
					'is_active'     => strip_tags(trim($this->request->getPost('is_active'))) ?: 1,
				];

				$ExpenseCategoryModel = new ExpenseCategoryModel();
				$result = $ExpenseCategoryModel->update($id, $data);
				$Return['csrf_hash'] = csrf_hash();
				if($result == TRUE){
					$Return['result'] = 'Category updated successfully.';
				} else {
					$Return['error'] = lang('Main.xin_error_msg');
				}
				$this->output($Return);
				exit;
			}
		} else {
			$Return = array('result'=>'', 'error'=>lang('Main.xin_error_msg'), 'csrf_hash'=>'');
			$this->output($Return);
			exit;
		}
	}

	// POST - delete category
	public function delete_category()
	{
		if($this->request->getPost('type') == 'delete_record'){
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$session = \Config\Services::session();
			$usession = $session->get('sup_username');
			$id = udecode(strip_tags(trim($this->request->getPost('_token'))));
			$Return['csrf_hash'] = csrf_hash();

			$ExpenseCategoryModel = new ExpenseCategoryModel();
			$result = $ExpenseCategoryModel->where('category_id', $id)->delete($id);
			if($result == TRUE){
				$Return['result'] = 'Category deleted successfully.';
			} else {
				$Return['error'] = lang('Main.xin_error_msg');
			}
			$this->output($Return);
		}
	}

	// Report: by employee, category, month
	public function expense_report()
	{
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){
			$session->setFlashdata('err_not_logged_in',lang('Dashboard.err_not_logged_in'));
			return redirect()->to(site_url('erp/login'));
		}
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if($user_info['user_type'] != 'company' && $user_info['user_type'] != 'staff'){
			$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}
		if($user_info['user_type'] != 'company'){
			if(!in_array('expense2',staff_role_resource())) {
				$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
				return redirect()->to(site_url('erp/desk'));
			}
		}

		$ExpenseModel = new ExpenseModel();
		$ExpenseCategoryModel = new ExpenseCategoryModel();

		// determine company_id
		if($user_info['user_type'] == 'staff'){
			$company_id = $user_info['company_id'];
		} else {
			$company_id = $usession['sup_user_id'];
		}

		// get filter params
		$request = \Config\Services::request();
		$filter_employee = $request->getGet('employee_id');
		$filter_category = $request->getGet('category_id');
		$filter_month    = $request->getGet('month');
		$filter_status   = $request->getGet('status');

		$builder = $ExpenseModel->where('company_id', $company_id);
		if($user_info['user_type'] == 'staff'){
			$builder = $builder->where('employee_id', $user_info['user_id']);
		}
		if(!empty($filter_employee)){
			$builder = $builder->where('employee_id', $filter_employee);
		}
		if(!empty($filter_category)){
			$builder = $builder->where('category_id', $filter_category);
		}
		if(!empty($filter_status)){
			$builder = $builder->where('status', $filter_status);
		}
		if(!empty($filter_month)){
			$builder = $builder->where("TO_CHAR(expense_date, 'YYYY-MM') =", $filter_month);
		}
		$expenses = $builder->orderBy('expense_date', 'DESC')->findAll();

		// summary totals
		$total_amount = 0;
		$total_approved = 0;
		$total_pending = 0;
		foreach($expenses as $exp){
			$total_amount += $exp['amount'];
			if($exp['status'] == 'approved') $total_approved += $exp['amount'];
			if($exp['status'] == 'pending') $total_pending += $exp['amount'];
		}

		// build datatable data
		$table_data = array();
		foreach($expenses as $r){
			$iuser_info = $UsersModel->where('user_id', $r['employee_id'])->first();
			$employee_name = $iuser_info ? $iuser_info['first_name'].' '.$iuser_info['last_name'] : '--';
			$cat_info = $ExpenseCategoryModel->where('category_id', $r['category_id'])->first();
			$category_name = $cat_info ? $cat_info['category_name'] : '--';
			$xin_system = erp_company_settings();
			$amount = number_to_currency($r['amount'], $r['currency'] ?? $xin_system['default_currency'], null, 2);

			if($r['status'] == 'approved'){
				$status = '<span class="badge badge-success">Approved</span>';
			} elseif($r['status'] == 'rejected'){
				$status = '<span class="badge badge-danger">Rejected</span>';
			} else {
				$status = '<span class="badge badge-warning">Pending</span>';
			}

			$table_data[] = array(
				$r['expense_date'],
				$employee_name,
				$category_name,
				$amount,
				$status,
			);
		}

		$xin_system_main = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'Expense Report | '.$xin_system_main['application_name'];
		$data['path_url'] = 'expense_report';
		$data['breadcrumbs'] = 'Expense Report';
		$data['total_amount'] = $total_amount;
		$data['total_approved'] = $total_approved;
		$data['total_pending'] = $total_pending;
		$data['report_data'] = json_encode(array('data' => $table_data));
		$data['filter_employee'] = $filter_employee;
		$data['filter_category'] = $filter_category;
		$data['filter_month'] = $filter_month;
		$data['filter_status'] = $filter_status;

		$data['subview'] = view('erp/expenses/expense_report', $data);
		return view('erp/layout/layout_main', $data);
	}
}
