<?php
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
 
use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\RolesModel;
use App\Models\CountryModel;
use App\Models\MembershipModel;
use App\Models\CompanymembershipModel;


class Membership extends BaseController {
	
	public function index()
	{		
		
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$UsersModel = new UsersModel();
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Membership.xin_membership_plans').' | '.$xin_system['application_name'];
		$data['path_url'] = 'membership';
		$data['breadcrumbs'] = lang('Membership.xin_membership_plans');
		$data['subview'] = view('erp/membership/membership_list', $data);
		return view('erp/layout/layout_main', $data); //page load
		
	}
	public function membership_details()
	{		
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$UsersModel = new UsersModel();
		$usession = $session->get('sup_username');
		$MembershipModel = new MembershipModel();
		$request = \Config\Services::request();
		$ifield_id = udecode($request->uri->getSegment(3));
		$isegment_val = $MembershipModel->where('membership_id', $ifield_id)->first();
		if(!$isegment_val){
			$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Membership.xin_view_membership').' | '.$xin_system['application_name'];
		$data['path_url'] = 'membership_detail';
		$data['breadcrumbs'] = lang('Membership.xin_view_membership');

		$data['subview'] = view('erp/membership/membership_detail', $data);
		return view('erp/layout/layout_main', $data); //page load
	}
	// list
	public function membership_list()
     {

		$session = \Config\Services::session();
		$usession = $session->get('sup_username');	
		$MembershipModel = new MembershipModel();
		$SystemModel = new SystemModel();
		$membership = $MembershipModel->orderBy('membership_id', 'ASC')->findAll();
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data = array();
        foreach($membership as $r) {
			// Plan name with badge
			$plan_name = '<strong>'.esc($r['membership_type']).'</strong>';

			// Duration
			if($r['plan_duration']==1){
				$plan_duration = '<span class="badge badge-light-info">Monthly</span>';
			} else if($r['plan_duration']==2){
				$plan_duration = '<span class="badge badge-light-primary">Yearly</span>';
			} else {
				$plan_duration = '<span class="badge badge-light-success">Unlimited</span>';
			}

			// Price
			$price = '<strong>UGX '.number_format((float)$r['price'], 0).'</strong>';

			// Actions
			$actions = '<div class="text-center">'
				.'<a href="'.site_url('erp/membership-detail/'.uencode($r['membership_id'])).'" class="btn btn-sm btn-light-primary mr-1" title="View"><i class="feather icon-eye"></i></a>';
			if($r['membership_id'] != 1){
				$actions .= '<button type="button" class="btn btn-sm btn-light-danger delete" data-toggle="modal" data-target=".delete-modal" data-record-id="'.uencode($r['membership_id']).'" title="Delete"><i class="feather icon-trash-2"></i></button>';
			}
			$actions .= '</div>';

			$data[] = array(
				$plan_name,
				esc($r['subscription_id']),
				$plan_duration,
				$price,
				$r['total_employees'],
				$actions,
			);
		}
          $output = array(
               //"draw" => $draw,
			   "data" => $data
            );
          echo json_encode($output);
          exit();
     }
	public function add_membership() {
			
		$validation =  \Config\Services::validation();
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$MembershipModel = new MembershipModel();
	
		if ($this->request->getMethod() === 'post') {
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();
			// set rules
			$validation->setRules([
					'membership_type' => 'required',
					'price' => 'required',
					'plan_duration' => 'required',
					'total_employees' => 'required'
				],
				[   // Errors
					'membership_type' => [
						'required' => lang('Membership.xin_membership_type_error_field'),
					],
					'price' => [
						'required' => lang('Main.xin_error_field_text'),
					],
					'plan_duration' => [
						'required' => lang('Main.xin_error_field_text'),
					],
					'total_employees' => [
						'required' => lang('Main.xin_error_field_text'),
					]
				]
			);
			$validation->withRequest($this->request)->run();
			//check error
			if ($validation->hasError('membership_type')) {
				$Return['error'] = $validation->getError('membership_type');
			} elseif($validation->hasError('price')){
				$Return['error'] = $validation->getError('price');
			} elseif($validation->hasError('plan_duration')){
				$Return['error'] = $validation->getError('plan_duration');
			} elseif ($validation->hasError('total_employees')) {
				$Return['error'] = $validation->getError('total_employees');
			}
			if($Return['error']!=''){
				$this->output($Return);
			}
		}
		$membership_type = strip_tags(trim($this->request->getPost('membership_type')));
		$price = $this->numericPost('price');
		$plan_duration = strip_tags(trim($this->request->getPost('plan_duration')));
		$total_employees = strip_tags(trim($this->request->getPost('total_employees')));
		$description = strip_tags(trim($this->request->getPost('description')));	
		//$ar_role_resources = serialize($role_resources);
		$subscription_id = generate_subscription_id();
		$data = [
            'subscription_id' => $subscription_id,
			'membership_type' => $membership_type,
			'price' => $price,
			'plan_duration'  => $plan_duration,
			'total_employees'  => $total_employees,
			'description'  => $description,
			'created_at' => date('d-m-Y h:i:s'),
        ];
		$MembershipModel = new MembershipModel();
        $result = $MembershipModel->insert($data);	
		$Return['csrf_hash'] = csrf_hash();	
		if ($result == TRUE) {
			$Return['result'] = lang('Membership.xin_membership_added_success');
		} else {
			$Return['error'] = lang('Main.xin_error_msg');
		}
		$this->output($Return);
		exit;
	} 
	// update record
	public function update_membership() {
			
		$validation =  \Config\Services::validation();
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$MembershipModel = new MembershipModel();
		if ($this->request->getPost('type') === 'edit_record') {
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();
			// set rules
			$validation->setRules([
					'membership_type' => 'required',
					'price' => 'required',
					'plan_duration' => 'required',
					'total_employees' => 'required'
				],
				[   // Errors
					'membership_type' => [
						'required' => lang('Membership.xin_membership_type_error_field'),
					],
					'price' => [
						'required' => lang('Membership.xin_membership_mprice_error_field'),
					],
					'plan_duration' => [
						'required' => lang('Membership.xin_membership_yprice_error_field'),
					],
					'total_employees' => [
						'required' => lang('Users.xin_role_error_access'),
					]
				]
			);
			$validation->withRequest($this->request)->run();
			//check error
			if ($validation->hasError('membership_type')) {
				$Return['error'] = $validation->getError('membership_type');
			} elseif($validation->hasError('price')){
				$Return['error'] = $validation->getError('price');
			} elseif($validation->hasError('plan_duration')){
				$Return['error'] = $validation->getError('plan_duration');
			} elseif ($validation->hasError('total_employees')) {
				$Return['error'] = $validation->getError('total_employees');
			}
			if($Return['error']!=''){
				$this->output($Return);
			}
			$membership_type = strip_tags(trim($this->request->getPost('membership_type')));
			$price = $this->numericPost('price');
			$plan_duration = strip_tags(trim($this->request->getPost('plan_duration')));
			$total_employees = strip_tags(trim($this->request->getPost('total_employees')));
			$description = strip_tags(trim($this->request->getPost('description')));		
			$id = udecode(strip_tags(trim($this->request->getPost('token'))));			
			$data = [
				'membership_type' => $membership_type,
				'price' => $price,
				'plan_duration'  => $plan_duration,
				'total_employees'  => $total_employees,
				'description'  => $description
			];
			$MembershipModel = new MembershipModel();
			$Return['csrf_hash'] = csrf_hash();
			$result = $MembershipModel->update($id, $data);
			if ($result == TRUE) {
				$Return['result'] = lang('Membership.xin_membership_updated_success');
			} else {
				$Return['error'] = lang('Main.xin_error_msg');
			}
			$this->output($Return);
			exit;
		} else {
			$Return['error'] = lang('Main.xin_error_msg');
			$this->output($Return);
			exit;
		}
		
	} 
	// read record
	public function read()
	{
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$id = $request->getGet('field_id');
		$data = [
				'field_id' => $id,
			];
		if($session->has('sup_username')){
			return view('erp/membership/dialog_membership', $data);
		} else {
			return redirect()->to(site_url('erp/login'));
		}
	}
	public function membership_type_chart() {
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){ 
			return redirect()->to(site_url('erp/login'));
		}		
		$RolesModel = new RolesModel();
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		$MembershipModel = new MembershipModel();
		$CompanymembershipModel = new CompanymembershipModel();
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		$get_data = $MembershipModel->orderBy('membership_id', 'ASC')->findAll();
		$data = array();
		$Return = array('iseries'=>'', 'ilabels'=>'');
		$title_info = array();
		$series_info = array();
		foreach($get_data as $r){
			$comp_count = $CompanymembershipModel->where('membership_id',$r['membership_id'])->countAllResults();
			if($comp_count > 0){
				$title_info[] = $r['membership_type'];
				$series_info[] = $comp_count;
			}
		}				  
		$Return['iseries'] = $series_info;
		$Return['ilabels'] = $title_info;
		$Return['total_label'] = lang('Main.xin_total');
		$this->output($Return);
		exit;
	}
	public function membership_by_country_chart() {
		
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if(!$session->has('sup_username')){ 
			return redirect()->to(site_url('erp/login'));
		}		
		$RolesModel = new RolesModel();
		$UsersModel = new UsersModel();
		$SystemModel = new SystemModel();
		$CountryModel = new CountryModel();
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		$get_data = $CountryModel->orderBy('country_id', 'ASC')->findAll();
		$data = array();
		$Return = array('iseries'=>'', 'ilabels'=>'');
		$title_info = array();
		$series_info = array();
		foreach($get_data as $r){
			$comp_count = $UsersModel->where('country',$r['country_id'])->where('user_type','company')->countAllResults();
			if($comp_count > 0){
				$title_info[] = $r['country_name'];
				$series_info[] = $comp_count;
			}
		}				  
		$Return['iseries'] = $series_info;
		$Return['ilabels'] = $title_info;
		$this->output($Return);
		exit;
	}
	// ===================================================================
	// Subscription Invoice Methods — Phase 4.5
	// ===================================================================

	/**
	 * Show invoice history for the logged-in company
	 */
	public function invoice_history()
	{
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$usession = $session->get('sup_username');
		if (!$session->has('sup_username')) {
			return redirect()->to(site_url('erp/login'));
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'Subscription Invoices | ' . $xin_system['application_name'];
		$data['path_url'] = 'subscription_invoices';
		$data['breadcrumbs'] = 'Subscription Invoices';
		$data['subview'] = view('erp/membership/invoice_history', $data);
		return view('erp/layout/layout_main', $data);
	}

	/**
	 * AJAX: Return invoice history data for the logged-in company (DataTables)
	 */
	public function invoice_history_list()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (!$session->has('sup_username')) {
			return redirect()->to(site_url('erp/login'));
		}

		$db = \Config\Database::connect();
		$SystemModel = new SystemModel();
		$xin_system = $SystemModel->where('setting_id', 1)->first();

		$companyId = $usession['sup_user_id'];
		$invoices = $db->table('ci_subscription_invoices')
			->where('company_id', $companyId)
			->orderBy('invoice_id', 'DESC')
			->get()
			->getResultArray();

		$data = array();
		foreach ($invoices as $r) {
			$statusBadge = ($r['status'] === 'paid')
				? '<span class="badge badge-success">Paid</span>'
				: '<span class="badge badge-warning">' . ucfirst($r['status']) . '</span>';

			$downloadBtn = '<a href="' . site_url('erp/subscription-invoice-download/' . $r['invoice_id']) . '" class="btn btn-sm btn-icon btn-light-primary" data-toggle="tooltip" title="Download PDF"><i class="feather icon-download"></i></a>';

			$amount = number_format((float)$r['amount'], 0, '.', ',') . ' ' . $r['currency'];

			$data[] = array(
				'<strong>' . esc($r['invoice_number']) . '</strong>',
				date('d M Y', strtotime($r['created_at'])),
				esc($r['plan_name']),
				$amount,
				esc($r['payment_method']),
				$statusBadge,
				$downloadBtn,
			);
		}

		$output = array('data' => $data);
		echo json_encode($output);
		exit();
	}

	/**
	 * Download a subscription invoice PDF (verify company ownership)
	 */
	public function download_invoice($invoiceId = null)
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (!$session->has('sup_username')) {
			return redirect()->to(site_url('erp/login'));
		}

		$db = \Config\Database::connect();
		$invoice = $db->table('ci_subscription_invoices')
			->where('invoice_id', (int)$invoiceId)
			->get()
			->getRowArray();

		if (!$invoice) {
			$session->setFlashdata('unauthorized_module', 'Invoice not found.');
			return redirect()->to(site_url('erp/desk'));
		}

		// Super admin (user_type = 'super') can download any invoice; companies can only download their own
		$UsersModel = new UsersModel();
		$user = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if ($user['user_type'] !== 'super' && (int)$invoice['company_id'] !== (int)$usession['sup_user_id']) {
			$session->setFlashdata('unauthorized_module', 'You do not have permission to download this invoice.');
			return redirect()->to(site_url('erp/desk'));
		}

		$pdfPath = FCPATH . $invoice['pdf_path'];
		if (!file_exists($pdfPath)) {
			$session->setFlashdata('unauthorized_module', 'Invoice PDF file not found.');
			return redirect()->to(site_url('erp/subscription-invoices'));
		}

		return $this->response
			->setHeader('Content-Type', 'application/pdf')
			->setHeader('Content-Disposition', 'attachment; filename="' . $invoice['invoice_number'] . '.pdf"')
			->setBody(file_get_contents($pdfPath));
	}

	/**
	 * Super Admin: Show all companies' subscription invoices
	 */
	public function all_invoices()
	{
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$usession = $session->get('sup_username');
		if (!$session->has('sup_username')) {
			return redirect()->to(site_url('erp/login'));
		}

		// Check super admin
		$UsersModel = new UsersModel();
		$user = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		if ($user['user_type'] !== 'super_user') {
			$session->setFlashdata('unauthorized_module', lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}

		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'All Subscription Invoices | ' . $xin_system['application_name'];
		$data['path_url'] = 'all_subscription_invoices';
		$data['breadcrumbs'] = 'All Subscription Invoices';
		$data['subview'] = view('erp/membership/all_invoices', $data);
		return view('erp/layout/layout_main', $data);
	}

	/**
	 * AJAX: Return all subscription invoices for super admin (DataTables)
	 */
	public function all_invoices_list()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (!$session->has('sup_username')) {
			return redirect()->to(site_url('erp/login'));
		}

		$db = \Config\Database::connect();
		$UsersModel = new UsersModel();
		$MembershipModel = new MembershipModel();

		$invoices = $db->table('ci_subscription_invoices')
			->orderBy('invoice_id', 'DESC')
			->get();
		$invoices = $invoices ? $invoices->getResultArray() : [];

		$data = array();
		foreach ($invoices as $r) {
			$company = $UsersModel->where('user_id', $r['company_id'])->first();
			$companyName = $company ? esc($company['company_name']) : 'N/A';

			$plan = $MembershipModel->where('membership_id', $r['membership_id'])->first();
			$planName = $plan ? esc($plan['membership_type']) : 'N/A';

			$statusBadge = ($r['status'] === 'paid')
				? '<span class="badge badge-light-success">Paid</span>'
				: '<span class="badge badge-light-warning">' . ucfirst(esc($r['status'] ?? 'pending')) . '</span>';

			$actions = '<div class="text-center">';
			if (!empty($r['pdf_path'])) {
				$actions .= '<a href="' . site_url('erp/subscription-invoice-download/' . $r['invoice_id']) . '" class="btn btn-sm btn-light-primary" title="Download PDF"><i class="feather icon-download"></i></a>';
			}
			$actions .= '</div>';

			$amount = '<strong>UGX ' . number_format((float)$r['amount'], 0) . '</strong>';
			$date = $r['issued_at'] ? date('d M Y', strtotime($r['issued_at'])) : 'N/A';

			$data[] = array(
				'<strong>' . esc($r['invoice_number'] ?? '#'.$r['invoice_id']) . '</strong>',
				$companyName,
				$date,
				$planName,
				$amount,
				esc($r['payment_method'] ?? 'N/A'),
				$statusBadge,
				$actions,
			);
		}

		$output = array('data' => $data);
		echo json_encode($output);
		exit();
	}

	 // delete record
	public function delete_membership() {
		
		if($this->request->getPost('type')=='delete_record') {
			/* Define return | here result is used to return user data and error for error message */
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$session = \Config\Services::session();
			$request = \Config\Services::request();
			$id = udecode(strip_tags(trim($this->request->getPost('_token'))));
			$Return['csrf_hash'] = csrf_hash();
			$MembershipModel = new MembershipModel();
			$result = $MembershipModel->where('membership_id', $id)->delete($id);
			if ($result == TRUE) {
				$Return['result'] = lang('Membership.xin_success_asset_deleted');
			} else {
				$Return['error'] = lang('Membership.xin_error_msg');
			}
			$this->output($Return);
		}
	}
}
