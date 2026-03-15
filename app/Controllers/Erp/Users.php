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
use CodeIgniter\HTTP\Files\UploadedFile;
 
use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\CompanyModel;
use App\Models\CountryModel;
use App\Models\ConstantsModel;
use App\Models\SuperroleModel;
use App\Models\EmailtemplatesModel;

class Users extends BaseController {
	
	public function index()
	{		
		
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$UsersModel = new UsersModel();
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Users.xin_super_users').' | '.$xin_system['application_name'];
		$data['path_url'] = 'users';
		$data['breadcrumbs'] = lang('Users.xin_super_users');
		$data['subview'] = view('erp/users/users_list', $data);
		return view('erp/layout/layout_main', $data); //page load
		
	}
	public function user_details()
	{		
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$UsersModel = new UsersModel();
		$SuperroleModel = new SuperroleModel();
		$usession = $session->get('sup_username');
		$UsersModel = new UsersModel();
		$request = \Config\Services::request();
		$ifield_id = udecode($request->uri->getSegment(3));
		$isegment_val = $UsersModel->where('user_id', $ifield_id)->first();
		if(!$isegment_val){
			$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Users.xin_view_user').' | '.$xin_system['application_name'];
		$data['path_url'] = 'user_details';
		$data['breadcrumbs'] = lang('Users.xin_view_user');

		$data['subview'] = view('erp/users/users_detail', $data);
		return view('erp/layout/layout_main', $data); //page load
	}
	public function role()
	{		
		
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$UsersModel = new UsersModel();
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Users.xin_hr_report_user_roles').' | '.$xin_system['application_name'];
		$data['path_url'] = 'user_roles';
		$data['breadcrumbs'] = lang('Users.xin_hr_report_user_roles');
		$data['subview'] = view('erp/roles/role_list', $data);
		return view('erp/layout/layout_main', $data); //page load
		
	}
	
	// list
	public function users_list()
     {

		$session = \Config\Services::session();
		$usession = $session->get('sup_username');		
		$UsersModel = new UsersModel();
		$ConstantsModel = new ConstantsModel();
		$CountryModel = new CountryModel();
		$SuperroleModel = new SuperroleModel();
		$SystemModel = new SystemModel();
		$users = $UsersModel->where('user_type', 'super_user')->orderBy('user_id', 'ASC')->findAll();
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		
		$data = array();

          foreach($users as $r) {
			$role = $SuperroleModel->where('role_id', $r['user_role_id'])->first();
			$country_info = $CountryModel->where('country_id', $r['country'])->first();

			// Name with avatar
			$avatar = staff_profile_photo($r['user_id']);
			$name = '<div class="d-flex align-items-center">'
				.'<img src="'.$avatar.'" alt="" class="img-radius mr-2" width="36" height="36">'
				.'<div><strong>'.esc($r['first_name'].' '.$r['last_name']).'</strong><br><small class="text-muted">'.esc($r['email']).'</small></div>'
				.'</div>';

			// Status
			$status = $r['is_active']==1
				? '<span class="badge badge-light-success">Active</span>'
				: '<span class="badge badge-light-danger">Inactive</span>';

			// Actions
			$actions = '<div class="text-center">'
				.'<a href="'.site_url('erp/user-detail/'.uencode($r['user_id'])).'" class="btn btn-sm btn-light-primary mr-1" title="View"><i class="feather icon-eye"></i></a>';
			if($r['user_id'] != 1){
				$actions .= '<button type="button" class="btn btn-sm btn-light-danger delete" data-toggle="modal" data-target=".delete-modal" data-record-id="'.uencode($r['user_id']).'" title="Delete"><i class="feather icon-trash-2"></i></button>';
			}
			$actions .= '</div>';

			$data[] = array(
				$name,
				esc($r['contact_number'] ?? ''),
				!empty($role) ? esc($role['role_name']) : 'N/A',
				!empty($country_info) ? esc($country_info['country_name']) : 'N/A',
				$status,
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
	public function add_user() {
			
		$validation =  \Config\Services::validation();
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$usession = $session->get('sup_username');
		if ($this->request->getPost('type') === 'add_record') {
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();
			// set rules
			$validation->setRules([
					'first_name' => 'required',
					'last_name' => 'required',
					'email' => 'required|valid_email|is_unique[ci_erp_users.email]',
					'username' => 'required|min_length[6]|is_unique[ci_erp_users.username]',
					'password' => 'required|min_length[6]',
					'contact_number' => 'required',
					'country' => 'required',
					'role' => 'required'
				],
				[   // Errors
					'first_name' => [
						'required' => lang('Main.xin_employee_error_first_name'),
					],
					'last_name' => [
						'required' => lang('Main.xin_employee_error_last_name'),
					],
					'email' => [
						'required' => lang('Main.xin_employee_error_email'),
						'valid_email' => lang('Main.xin_employee_error_invalid_email'),
						'is_unique' => lang('Main.xin_already_exist_error_email'),
					],
					'username' => [
						'required' => lang('Main.xin_employee_error_username'),
						'min_length' => lang('Main.xin_min_error_username'),
						'is_unique' => lang('Main.xin_already_exist_error_username')
					],
					'password' => [
						'required' => lang('Main.xin_employee_error_password'),
						'min_length' => lang('Login.xin_min_error_password')
					],
					'contact_number' => [
						'required' => lang('Main.xin_error_contact_field'),
					],
					'country' => [
						'required' => lang('Main.xin_error_country_field'),
					],
					'role' => [
						'required' => lang('Users.xin_employee_error_user_role'),
					]
				]
			);
			
			$validation->withRequest($this->request)->run();
			//check error
			if ($validation->hasError('first_name')) {
				$Return['error'] = $validation->getError('first_name');
			} elseif($validation->hasError('last_name')){
				$Return['error'] = $validation->getError('last_name');
			} elseif($validation->hasError('email')){
				$Return['error'] = $validation->getError('email');
			} elseif($validation->hasError('username')){
				$Return['error'] = $validation->getError('username');
			} elseif($validation->hasError('password')){
				$Return['error'] = $validation->getError('password');
			} elseif($validation->hasError('contact_number')){
				$Return['error'] = $validation->getError('contact_number');
			} elseif($validation->hasError('role')){
				$Return['error'] = $validation->getError('role');
			} elseif($validation->hasError('country')){
				$Return['error'] = $validation->getError('country');
			}
			if($Return['error']!=''){
				$this->output($Return);
			}
			$image = service('image');
			$validated = $this->validate([
				'file' => [
					'uploaded[file]',
					'mime_in[file,image/jpg,image/jpeg,image/gif,image/png]',
					'max_size[file,4096]',
				],
			]);
			if (!$validated) {
				$Return['error'] = lang('Users.xin_user_photo_field');
			} else {
				$avatar = $this->request->getFile('file');
				$file_name = $avatar->getName();
				$avatar->move('public/uploads/users/');
				$image->withFile(filesrc($file_name))
				->fit(100, 100, 'center')
				->save('public/uploads/users/thumb/'.$file_name);
			}
			if($Return['error']!=''){
				$this->output($Return);
			}
			$first_name = strip_tags(trim($this->request->getPost('first_name')));
			$last_name = strip_tags(trim($this->request->getPost('last_name')));
			$email = strip_tags(trim($this->request->getPost('email')));
			$username = strip_tags(trim($this->request->getPost('username')));
			$password = strip_tags(trim($this->request->getPost('password')));
			$contact_number = strip_tags(trim($this->request->getPost('contact_number')));
			$country = strip_tags(trim($this->request->getPost('country')));
			$role = strip_tags(trim($this->request->getPost('role')));
			$gender = strip_tags(trim($this->request->getPost('gender')));
			$address_1 = '';
			$address_2 = '';
			$city = '';
			$state = '';
			$zipcode ='';
			// company info
			$UsersModel = new UsersModel();
			$SystemModel = new SystemModel();
			$EmailtemplatesModel = new EmailtemplatesModel();
			$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
			
			$company_name = $user_info['company_name'];
			$company_type = $user_info['company_type'];
			$xin_gtax = $user_info['xin_gtax'];
			$trading_name = $user_info['trading_name'];
			$registration_no = $user_info['registration_no'];
			
			$options = array('cost' => 12);
			$password_hash = password_hash($password, PASSWORD_BCRYPT, $options);
			$xin_system = $SystemModel->where('setting_id', 1)->first();
			$data = [
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'email'  => $email,
				'user_type'  => 'super_user',
				'username'  => $username,
				'password'  => $password_hash,
				'contact_number'  => $contact_number,
				'country'  => $country,
				'user_role_id' => $role,
				'address_1'  => $address_1,
				'address_2'  => $address_2,
				'city'  => $city,
				'profile_photo'  => $file_name,
				'state'  => $state,
				'zipcode' => $zipcode,
				'gender' => $gender,
				'company_name' => $company_name,
				'trading_name' => $trading_name,
				'registration_no' => $registration_no,
				'government_tax' => $xin_gtax,
				'company_type_id'  => $company_type,
				'last_login_date' => '0',
				'last_logout_date' => '0',
				'last_login_ip' => '0',
				'is_logged_in' => '0',
				'is_active'  => 1,
				'company_id'  => 0,
				'added_by'  => $usession['sup_user_id'],
				'created_at' => date('d-m-Y h:i:s')
			];
			$UsersModel = new UsersModel();
			$result = $UsersModel->insert($data);	
			$Return['csrf_hash'] = csrf_hash();	
			if ($result == TRUE) {
				$Return['result'] = lang('Users.xin_success_user_added');
				if($xin_system['enable_email_notification'] == 1){
					// Send mail start
					$itemplate = $EmailtemplatesModel->where('template_id', 5)->first();
					$isubject = $itemplate['subject'];
					$ibody = html_entity_decode($itemplate['message']);
					$fbody = str_replace(array("{site_name}","{user_password}","{user_username}","{site_url}"),array($xin_system['company_name'],$password,$username,site_url()),$ibody);
					timehrm_mail_data($xin_system['email'],$xin_system['company_name'],$email,$isubject,$fbody);
					// Send mail end
				}
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
	// update record
	public function update_user() {
			
		$validation =  \Config\Services::validation();
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$usession = $session->get('sup_username');	
		if ($this->request->getPost('type') === 'edit_record') {
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();
			// set rules
			$validation->setRules([
					'first_name' => 'required',
					'last_name' => 'required',
					'email' => 'required|valid_email',
					'username' => 'required|min_length[6]',
					'contact_number' => 'required',
					'country' => 'required',
					'role' => 'required',
					'status' => 'required'
				],
				[   // Errors
					'first_name' => [
						'required' => lang('Main.xin_employee_error_first_name'),
					],
					'last_name' => [
						'required' => lang('Main.xin_employee_error_last_name'),
					],
					'email' => [
						'required' => lang('Main.xin_employee_error_email'),
						'valid_email' => lang('Main.xin_employee_error_invalid_email')
					],
					'username' => [
						'required' => lang('Main.xin_employee_error_username'),
						'min_length' => lang('Main.xin_min_error_username')
					],
					'contact_number' => [
						'required' => lang('Main.xin_error_contact_field'),
					],
					'country' => [
						'required' => lang('Main.xin_error_country_field'),
					],
					'role' => [
						'required' => lang('Users.xin_employee_error_user_role'),
					],
					'status' => [
						'required' => '{field} is required.',
					]
				]
			);
			
			$validation->withRequest($this->request)->run();
			//check error
			if ($validation->hasError('first_name')) {
				$Return['error'] = $validation->getError('first_name');
			} elseif($validation->hasError('last_name')){
				$Return['error'] = $validation->getError('last_name');
			} elseif($validation->hasError('email')){
				$Return['error'] = $validation->getError('email');
			} elseif($validation->hasError('username')){
				$Return['error'] = $validation->getError('username');
			} elseif($validation->hasError('status')){
				$Return['error'] = $validation->getError('status');
			} elseif($validation->hasError('contact_number')){
				$Return['error'] = $validation->getError('contact_number');
			} elseif($validation->hasError('role')){
				$Return['error'] = $validation->getError('role');
			} elseif($validation->hasError('country')){
				$Return['error'] = $validation->getError('country');
			} 
			if($Return['error']!=''){
				$this->output($Return);
			}

			$first_name = strip_tags(trim($this->request->getPost('first_name')));
			$last_name = strip_tags(trim($this->request->getPost('last_name')));
			$email = strip_tags(trim($this->request->getPost('email')));
			$username = strip_tags(trim($this->request->getPost('username')));
			$contact_number = strip_tags(trim($this->request->getPost('contact_number')));
			$country = strip_tags(trim($this->request->getPost('country')));
			$role = strip_tags(trim($this->request->getPost('role')));
			$gender = strip_tags(trim($this->request->getPost('gender')));
			$address_1 = strip_tags(trim($this->request->getPost('address_1')));
			$address_2 = strip_tags(trim($this->request->getPost('address_2')));
			$city = strip_tags(trim($this->request->getPost('city')));
			$state = strip_tags(trim($this->request->getPost('state')));
			$zipcode = strip_tags(trim($this->request->getPost('zipcode')));
			$status = strip_tags(trim($this->request->getPost('status')));
			$id = udecode(strip_tags(trim($this->request->getPost('token'))));	
			$data = [
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'email'  => $email,
				'username'  => $username,
				'contact_number'  => $contact_number,
				'country'  => $country,
				'user_role_id' => $role,
				'address_1'  => $address_1,
				'address_2'  => $address_2,
				'city'  => $city,
				'state'  => $state,
				'zipcode' => $zipcode,
				'gender' => $gender,
				'is_active'  => $status,
			];
			$UsersModel = new UsersModel();
			$result = $UsersModel->update($id, $data);	
			$Return['csrf_hash'] = csrf_hash();	
			if ($result == TRUE) {
				$Return['result'] = lang('Users.xin_success_user_updated');
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
	// |||user role|||
	public function add_role() {
			
		$validation =  \Config\Services::validation();
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$usession = $session->get('sup_username');	
		if ($this->request->getPost('type') === 'add_record') {
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();
			// set rules
			$validation->setRules([
					'role_name' => 'required',
					'role_access' => 'required'
				],
				[   // Errors
					'role_name' => [
						'required' => lang('Users.xin_role_error_role_name'),
					],
					'role_access' => [
						'required' => lang('Users.xin_role_error_access'),
					]
				]
			);
			
			$validation->withRequest($this->request)->run();
			//check error
			if ($validation->hasError('role_name')) {
				$Return['error'] = $validation->getError('role_name');
			} elseif ($validation->hasError('role_access')) {
				$Return['error'] = $validation->getError('role_access');
			}
			if($Return['error']!=''){
				$this->output($Return);
			}

			$role_name = strip_tags(trim($this->request->getPost('role_name')));
			$role_access = strip_tags(trim($this->request->getPost('role_access')));
			$role_resources_field = $this->request->getPost('role_resources');
			$role_resources = is_array($role_resources_field) ? implode(',', array_filter($role_resources_field)) : ($role_resources_field ?? '0');
			$data = [
				'role_name' => $role_name,
				'role_access'  => $role_access,
				'role_resources'  => $role_resources,
				'created_at' => date('d-m-Y h:i:s')
			];
			$SuperroleModel = new SuperroleModel();
			$result = $SuperroleModel->insert($data);	
			$Return['csrf_hash'] = csrf_hash();	
			if ($result == TRUE) {
				$Return['result'] = lang('Users.xin_role_success_added');
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
	// update record
	public function update_profile_photo() {
			
		$validation =  \Config\Services::validation();
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$usession = $session->get('sup_username');
		if ($this->request->getPost('type') === 'edit_record') {
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();
			// set rules
			$validated = $this->validate([
				'file' => [
					'uploaded[file]',
					'mime_in[file,image/jpg,image/jpeg,image/gif,image/png]',
					'max_size[file,4096]',
				],
			]);
			if (!$validated) {
				$Return['error'] = lang('Main.xin_error_profile_picture_field');
				$this->output($Return);
				exit;
			}

			$avatar = $this->request->getFile('file');
			$file_name = $avatar->getRandomName();
			$upload_path = FCPATH . 'uploads/users/';
			$thumb_path  = FCPATH . 'uploads/users/thumb/';

			// Ensure directories exist
			if (!is_dir($upload_path)) mkdir($upload_path, 0777, true);
			if (!is_dir($thumb_path))  mkdir($thumb_path, 0777, true);

			$avatar->move($upload_path, $file_name);

			// Create thumbnail
			try {
				$image = service('image');
				$image->withFile($upload_path . $file_name)
					->fit(100, 100, 'center')
					->save($thumb_path . $file_name);
			} catch (\Exception $e) {
				// Copy original as thumb if resize fails
				copy($upload_path . $file_name, $thumb_path . $file_name);
			}

			$id = udecode(strip_tags(trim($this->request->getPost('token'))));
			$UsersModel = new UsersModel();
			$data = ['profile_photo' => $file_name];
			$result = $UsersModel->update($id, $data);
			$Return['csrf_hash'] = csrf_hash();
			$Return['result'] = lang('Main.xin_profile_picture_success_updated');
			$this->output($Return);
			exit;
		} else {
			$Return['error'] = lang('Main.xin_error_msg');
			$this->output($Return);
			exit;
		}
	} 
	public function update_role() {
			
		$validation =  \Config\Services::validation();
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$usession = $session->get('sup_username');	
		if ($this->request->getPost('type') === 'edit_record') {
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$Return['csrf_hash'] = csrf_hash();
			// set rules
			$validation->setRules([
					'role_name' => 'required',
					'role_access' => 'required'
				],
				[   // Errors
					'role_name' => [
						'required' => lang('Users.xin_role_error_role_name'),
					],
					'role_access' => [
						'required' => lang('Users.xin_role_error_access'),
					]
				]
			);
			
			$validation->withRequest($this->request)->run();
			//check error
			if ($validation->hasError('role_name')) {
				$Return['error'] = $validation->getError('role_name');
			} elseif ($validation->hasError('role_access')) {
				$Return['error'] = $validation->getError('role_access');
			}
			if($Return['error']!=''){
				$this->output($Return);
			}

			$role_name = strip_tags(trim($this->request->getPost('role_name')));
			$role_access = strip_tags(trim($this->request->getPost('role_access')));
			$id = udecode(strip_tags(trim($this->request->getPost('token'))));
			$role_resources_field = $this->request->getPost('role_resources');
			$role_resources = is_array($role_resources_field) ? implode(',', array_filter($role_resources_field)) : ($role_resources_field ?? '0');
			$data = [
				'role_name' => $role_name,
				'role_access'  => $role_access,
				'role_resources'  => $role_resources
			];
			$SuperroleModel = new SuperroleModel();
			$result = $SuperroleModel->update($id, $data);
			$Return['csrf_hash'] = csrf_hash();	
			if ($result == TRUE) {
				$Return['result'] = lang('Users.xin_role_success_updated');
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
	
	public function roles_list()
     {

		$session = \Config\Services::session();
		$usession = $session->get('sup_username');		
		$SuperroleModel = new SuperroleModel();
		$SystemModel = new SystemModel();
		$roles = $SuperroleModel->orderBy('role_id', 'ASC')->findAll();		
		$data = array();
		
          foreach($roles as $r) {
			// Access level badge
			$role_access = ($r['role_access'] ?? '') == '1'
				? '<span class="badge badge-light-success">Full Access</span>'
				: '<span class="badge badge-light-info">Custom</span>';

			// Date
			$date = !empty($r['created_at']) ? date('d M Y', strtotime(str_replace('/','-',$r['created_at']))) : 'N/A';

			// Actions
			$actions = '<div class="text-center">'
				.'<button type="button" class="btn btn-sm btn-light-primary mr-1" data-toggle="modal" data-target=".edit-modal-data" data-field_id="'.uencode($r['role_id']).'" title="Edit"><i class="feather icon-edit"></i></button>';
			if($r['role_id'] != 1){
				$actions .= '<button type="button" class="btn btn-sm btn-light-danger delete" data-toggle="modal" data-target=".delete-modal" data-record-id="'.uencode($r['role_id']).'" title="Delete"><i class="feather icon-trash-2"></i></button>';
			}
			$actions .= '</div>';

			$data[] = array(
				'<strong>'.esc($r['role_name']).'</strong>',
				$role_access,
				$date,
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
			return view('erp/users/dialog_users', $data);
		} else {
			return redirect()->to(site_url('erp/login'));
		}
	}
	// read record
	public function read_role()
	{
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$id = $request->getGet('field_id');
		$data = [
				'field_id' => $id,
			];
		if($session->has('sup_username')){
			return view('erp/roles/dialog_role', $data);
		} else {
			return redirect()->to(site_url('erp/login'));
		}
	}
	 // delete record
	public function delete_user() {
		
		if($this->request->getPost('type')=='delete_record') {
			/* Define return | here result is used to return user data and error for error message */
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$session = \Config\Services::session();
			$request = \Config\Services::request();
			$id = udecode(strip_tags(trim($this->request->getPost('_token'))));
			$Return['csrf_hash'] = csrf_hash();
			$UsersModel = new UsersModel();
			$result = $UsersModel->where('user_id', $id)->delete($id);
			if ($result == TRUE) {
				$Return['result'] = lang('Users.xin_success_delete_user');
			} else {
				$Return['error'] = lang('Membership.xin_error_msg');
			}
			$this->output($Return);
		}
	}
	// delete record
	public function delete_role() {
		
		if($this->request->getPost('type')=='delete_record') {
			/* Define return | here result is used to return user data and error for error message */
			$Return = array('result'=>'', 'error'=>'', 'csrf_hash'=>'');
			$session = \Config\Services::session();
			$request = \Config\Services::request();
			$id = udecode(strip_tags(trim($this->request->getPost('_token'))));
			$Return['csrf_hash'] = csrf_hash();
			$SuperroleModel = new SuperroleModel();
			$result = $SuperroleModel->where('role_id', $id)->delete($id);
			if ($result == TRUE) {
				$Return['result'] = lang('Users.xin_role_success_deleted');
			} else {
				$Return['error'] = lang('Membership.xin_error_msg');
			}
			$this->output($Return);
		}
	}
}
