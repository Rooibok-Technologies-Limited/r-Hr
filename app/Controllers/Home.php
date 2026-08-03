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
namespace App\Controllers;
use App\Controllers\BaseController;

use App\Models\SystemModel;
use App\Models\LandingContentModel;

class Home extends BaseController {

	/**
	 * Landing page — public marketing site for visitors.
	 * Logged-in users are redirected to their dashboard.
	 */
	public function index()
	{
		$SystemModel = new SystemModel();
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = $xin_system['application_name'] ?? 'Rooibok HR';
		$data['xin_system'] = $xin_system;
		return view('frontend/home', $data);
	}

	/**
	 * Login page — shows the login form.
	 */
	public function login()
	{
		$SystemModel = new SystemModel();
		$session = \Config\Services::session();
		if($session->has('sup_username')){
			return redirect()->to(site_url('erp/desk'));
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = $xin_system['application_name'].' | '.lang('Login.xin_login_title');
		return view('erp/auth/erp_login', $data);
	}

	public function features()
	{
		$SystemModel = new SystemModel();
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'Features | ' . $xin_system['application_name'];
		$data['xin_system'] = $xin_system;
		return view('frontend/features', $data);
	}

	public function pricing()
	{
		$SystemModel = new SystemModel();
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'Pricing | ' . $xin_system['application_name'];
		$data['xin_system'] = $xin_system;
		return view('frontend/pricing', $data);
	}

	public function contact()
	{
		$SystemModel = new SystemModel();
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'Contact | ' . $xin_system['application_name'];
		$data['xin_system'] = $xin_system;
		return view('frontend/contact', $data);
	}

	public function register()
	{
		$SystemModel = new SystemModel();
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'Register | ' . $xin_system['application_name'];
		$data['xin_system'] = $xin_system;
		return view('frontend/register', $data);
	}

	/**
	 * Company self-registration (the sign-up form). Creates the company + first
	 * admin, a trial subscription, and per-tenant settings defaults, then sends
	 * them to sign in. Accepts ALL valid emails (valid_email = RFC, no allowlist).
	 */
	public function register_company()
	{
		helper(['form', 'main']);
		$UsersModel = new \App\Models\UsersModel();

		$rules = [
			'first_name'     => 'required',
			'last_name'      => 'required',
			'company_name'   => 'required',
			'email'          => 'required|valid_email|is_unique[ci_erp_users.email]',
			'contact_number' => 'required',
			'password'       => 'required|min_length[6]',
		];
		if (! $this->validate($rules)) {
			return redirect()->back()->withInput()->with('reg_error', implode(' ', $this->validator->getErrors()));
		}

		$req      = $this->request;
		$first    = strip_tags(trim($req->getPost('first_name')));
		$last     = strip_tags(trim($req->getPost('last_name')));
		$company  = strip_tags(trim($req->getPost('company_name')));
		$email    = strip_tags(trim($req->getPost('email')));
		$contact  = strip_tags(trim($req->getPost('contact_number')));
		$password = (string) $req->getPost('password');

		// Unique username from the email local-part.
		$base = preg_replace('/[^a-z0-9._]/', '', strtolower(explode('@', $email)[0])) ?: 'company';
		$username = $base; $n = 1;
		while ($UsersModel->where('username', $username)->countAllResults() > 0) { $username = $base . $n; $n++; }

		// Unique tenant slug from the company name (ADR-003 subdomain identity).
		$reserved = ['admin', 'api', 'www', 'app', 'mail', 'ftp'];
		$sbase = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($company)), '-') ?: 'company';
		$slug = $sbase; $m = 1;
		while (in_array($slug, $reserved, true) || $UsersModel->where('company_slug', $slug)->countAllResults() > 0) { $slug = $sbase . '-' . $m; $m++; }

		$UsersModel->insert([
			'company_id'       => 0,
			'company_name'     => $company,
			'first_name'       => $first,
			'last_name'        => $last,
			'user_type'        => 'company',
			'contact_number'   => $contact,
			'email'            => $email,
			'username'         => $username,
			'company_slug'     => $slug,
			'password'         => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
			'user_role_id'     => 0,
			'profile_photo'    => 'default.png',
			'gender'           => 1,
			'is_active'        => 1,
			'is_logged_in'     => '0',
			'last_login_date'  => '0',
			'last_logout_date' => '0',
			'last_login_ip'    => '0',
			'created_at'       => date('d-m-Y h:i:s'),
		]);
		$companyId = $UsersModel->insertID();
		if (! $companyId) {
			return redirect()->back()->withInput()->with('reg_error', 'Could not create your account. Please try again.');
		}

		// Trial subscription — cheapest available plan.
		$plan = (new \App\Models\MembershipModel())->orderBy('price', 'ASC')->first();
		if ($plan) {
			(new \App\Models\CompanymembershipModel())->insert([
				'company_id'        => $companyId,
				'membership_id'     => $plan['membership_id'],
				'subscription_type' => $plan['plan_duration'] ?? 1,
				'update_at'         => date('d-m-Y h:i:s'),
				'created_at'        => date('d-m-Y h:i:s'),
			]);
		}

		// Per-tenant settings defaults (Uganda-sensible).
		(new \App\Models\CompanysettingsModel())->insert([
			'company_id'              => $companyId,
			'setup_modules'           => serialize(['recruitment' => '1', 'travel' => '1', 'award' => '1', 'events' => '1', 'fmanager' => '1']),
			'login_page'              => '2',
			'default_currency'        => 'UGX',
			'default_currency_symbol' => 'UGX',
			'notification_position'   => 'toast-top-center',
			'notification_close_btn'  => 'true',
			'notification_bar'        => 'true',
			'date_format_xi'          => 'Y-m-d',
			'default_language'        => 'en',
			'system_timezone'         => 'Africa/Kampala',
			'theme_primary'           => '#7267EF',
			'updated_at'              => date('d-m-Y h:i:s'),
		]);

		try {
			service('audit')->record('company.self_registered', [
				'entity_type' => 'company', 'entity_id' => $companyId,
				'summary'     => 'Self-registration: ' . $company,
			]);
		} catch (\Throwable $e) {}

		return redirect()->to(site_url('erp/login'))->with('reg_success', 'Your account is ready — sign in with ' . esc($username) . '.');
	}

	/**
	 * Privacy policy page — content from ci_landing_content.
	 */
	public function privacy()
	{
		$SystemModel         = new SystemModel();
		$LandingContentModel = new LandingContentModel();

		$xin_system = $SystemModel->where('setting_id', 1)->first();

		$data['title']   = 'Privacy Policy | ' . $xin_system['application_name'];
		$data['content'] = $LandingContentModel->getValue('legal', 'privacy') ?? '';
		$data['heading'] = 'Privacy Policy';

		return view('erp/auth/legal_page', $data);
	}

	/**
	 * Cookie policy page — content from ci_landing_content.
	 */
	public function cookies()
	{
		$SystemModel         = new SystemModel();
		$LandingContentModel = new LandingContentModel();

		$xin_system = $SystemModel->where('setting_id', 1)->first();

		$data['title']   = 'Cookie Policy | ' . $xin_system['application_name'];
		$data['content'] = $LandingContentModel->getValue('legal', 'cookies') ?? '';
		$data['heading'] = 'Cookie Policy';

		return view('erp/auth/legal_page', $data);
	}

	/**
	 * Terms of service page — content from ci_landing_content.
	 */
	public function terms()
	{
		$SystemModel         = new SystemModel();
		$LandingContentModel = new LandingContentModel();

		$xin_system = $SystemModel->where('setting_id', 1)->first();

		$data['title']   = 'Terms of Service | ' . $xin_system['application_name'];
		$data['content'] = $LandingContentModel->getValue('legal', 'terms') ?? '';
		$data['heading'] = 'Terms of Service';

		return view('erp/auth/legal_page', $data);
	}

	/**
	 * Demo login — log in as the demo company user (read-only session).
	 */
	public function demo()
	{
		$UsersModel = new \App\Models\UsersModel();
		$demoUser   = $UsersModel->where('is_demo', 1)
		                          ->where('user_type', 'company')
		                          ->first();

		if (! $demoUser) {
			return redirect()->to(site_url('/'))->with('error', 'Demo not available');
		}

		$session = \Config\Services::session();
		$session->set([
			'sup_username'    => ['sup_user_id' => $demoUser['user_id']],
			'is_demo_session' => true,
		]);

		return redirect()->to(site_url('erp/desk'));
	}

	/**
	 * Attendance kiosk — fullscreen QR scanner for employee clock-in.
	 * No auth required; kiosk runs on a dedicated tablet.
	 */
	public function kiosk()
	{
		return view('erp/kiosk/attendance_kiosk');
	}

	/**
	 * Visitor kiosk — self-service visitor check-in form.
	 * No auth required; kiosk runs on a dedicated tablet.
	 */
	public function visitor_kiosk()
	{
		return view('erp/kiosk/visitor_kiosk');
	}

	/**
	 * API Documentation page — shows REST API endpoints.
	 */
	public function api_docs()
	{
		$SystemModel = new SystemModel();
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'API Documentation | ' . ($xin_system['application_name'] ?? 'Rooibok HR');
		$data['xin_system'] = $xin_system;
		return view('frontend/api_docs', $data);
	}
}
