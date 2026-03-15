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
		$session = \Config\Services::session();
		if($session->has('sup_username')){
			return redirect()->to(site_url('erp/desk'));
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = $xin_system['application_name'];
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
}
