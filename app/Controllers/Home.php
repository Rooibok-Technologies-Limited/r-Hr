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

	public function index()
	{
		$SystemModel = new SystemModel();
		$session = \Config\Services::session();
		if($session->has('sup_username')){
			return redirect()->to(site_url('erp/desk?module=dashboard'));
		}
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = $xin_system['application_name'].' | '.lang('Login.xin_login_title');

		// Load landing page content from CMS
		$LandingContentModel = new LandingContentModel();
		$data['hero']         = $LandingContentModel->getSection('hero');
		$data['features']     = $LandingContentModel->getJson('features', 'cards');
		$data['stats']        = $LandingContentModel->getJson('stats', 'figures');
		$data['testimonials'] = $LandingContentModel->getJson('testimonials', 'items');
		$data['faq']          = $LandingContentModel->getJson('faq', 'items');
		$data['contact']      = $LandingContentModel->getSection('contact');
		$data['footer']       = $LandingContentModel->getSection('footer');
		$data['seo']          = $LandingContentModel->getSection('seo');

		return view('erp/auth/erp_login',$data);
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
}
