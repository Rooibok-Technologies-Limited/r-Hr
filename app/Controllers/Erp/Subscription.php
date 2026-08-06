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
 
use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\MembershipModel;
use App\Models\CompanymembershipModel;


class Subscription extends BaseController {
	
	public function index()
	{		
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$UsersModel = new UsersModel();
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Membership.xin_my_subscription').' | '.$xin_system['application_name'];
		$data['path_url'] = 'membership';
		$data['breadcrumbs'] = lang('Membership.xin_my_subscription');
		$data['subview'] = view('erp/membership/key_membership', $data);
		return view('erp/layout/layout_main', $data); //page load
		
	}
	public function subscription_expired()
	{		
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$UsersModel = new UsersModel();
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Membership.xin_membership_plans').' | '.$xin_system['application_name'];
		$data['path_url'] = 'membership';
		$data['breadcrumbs'] = lang('Membership.xin_membership_plans');
		$data['subview'] = view('erp/membership/key_subscription_expired', $data);
		$session->destroy();
		return view('erp/layout/expired_layout_main', $data); //page load

	}

	/**
	 * Renewal wall (ADR: registration billing). Where CheckLogin sends an expired
	 * company OWNER — they stay logged in and self-serve a renewal here. Owners
	 * only; staff are bounced to the locked page.
	 */
	public function renew()
	{
		$session  = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (! $session->has('sup_username')) { return redirect()->to(site_url('erp/login')); }
		$me = (new UsersModel())->where('user_id', $usession['sup_user_id'])->first();
		if (empty($me) || $me['user_type'] !== 'company') {
			return redirect()->to(($me['user_type'] ?? '') === 'staff' ? site_url('erp/subscription-locked') : site_url('erp/desk'));
		}
		$companyId  = (int) $me['user_id'];

		// PesaPal return: it appends ?OrderTrackingId=... to the callback. Re-verify
		// server-side and activate (idempotent — belt-and-suspenders with the IPN).
		$tracking = (string) $this->request->getGet('OrderTrackingId');
		if ($tracking !== '') {
			$v = service('pesapalCollections')->verify($tracking);
			if (($v['ok'] ?? false) && ($v['status'] ?? '') === 'successful'
				&& ! empty((new \App\Libraries\SubscriptionBilling())->activateFromVerifiedPayment($v)['ok'])) {
				return redirect()->to(site_url('erp/desk'))->with('success', 'Payment received — your subscription is now active.');
			}
			$session->setFlashdata('renew_error', 'We could not confirm your payment yet. If you completed it, access will be restored shortly.');
		}

		$MembershipModel = new MembershipModel();
		$membership = (new CompanymembershipModel())->where('company_id', $companyId)->first();
		$xin_system = (new SystemModel())->where('setting_id', 1)->first();

		$data = [
			'title'        => 'Renew subscription | ' . ($xin_system['application_name'] ?? ''),
			'app_name'     => system_setting('application_name') ?: 'Rooibok HR',
			'company_name' => $me['company_name'] ?? '',
			'plans'        => $MembershipModel->orderBy('price', 'ASC')->findAll(),
			'membership'   => $membership,
			'current_plan' => $membership ? $MembershipModel->where('membership_id', $membership['membership_id'])->first() : null,
		];
		$data['subview'] = view('erp/membership/renew_wall', $data);
		return view('erp/layout/expired_layout_main', $data);
	}

	/**
	 * Owner submits a renewal choice. Payment gateway (PesaPal) is wired next; for
	 * now this records the requested plan + notifies super-admins, who confirm the
	 * renewal via the company lifecycle tools. Audited.
	 */
	public function renew_submit()
	{
		$session  = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (! $session->has('sup_username')) { return redirect()->to(site_url('erp/login')); }
		$me = (new UsersModel())->where('user_id', $usession['sup_user_id'])->first();
		if (empty($me) || $me['user_type'] !== 'company') {
			return redirect()->to(site_url('erp/desk'));
		}
		$planId = (int) $this->request->getPost('membership_id');
		$plan   = $planId > 0 ? (new MembershipModel())->where('membership_id', $planId)->first() : null;
		if (! $plan) {
			return redirect()->to(site_url('erp/renew'))->with('renew_error', 'Please choose a plan.');
		}
		$companyId = (int) $me['user_id'];
		$price     = (float) ($plan['price'] ?? 0);

		// Free plan → activate immediately (no payment needed).
		if ($price <= 0) {
			(new \App\Libraries\SubscriptionBilling())->activateFromVerifiedPayment([
				'tx_ref' => 'SUB-' . $companyId . '-' . $planId . '-' . bin2hex(random_bytes(4)),
				'amount' => 0, 'company_id' => $companyId,
			]);
			return redirect()->to(site_url('erp/desk'))->with('success', 'Your ' . $plan['membership_type'] . ' plan is now active.');
		}

		// Paid plan → PesaPal checkout when the gateway is configured.
		$col = service('collections');
		if ($col->isConfigured()) {
			$ref = 'SUB-' . $companyId . '-' . $planId . '-' . bin2hex(random_bytes(5));
			$res = $col->initiate($companyId, $price, [
				'tx_ref'       => $ref,
				'redirect_url' => site_url('erp/renew'),
				'description'  => 'Subscription: ' . $plan['membership_type'],
				'currency'     => erp_currency(),
				'email'        => $me['email'] ?? '',
				'phone'        => $me['contact_number'] ?? '',
				'name'         => $me['company_name'] ?? ('Company ' . $companyId),
			]);
			if (! empty($res['ok']) && ! empty($res['link'])) {
				return redirect()->to($res['link']); // hand off to PesaPal checkout
			}
			// initiate failed → fall through to the request-and-notify path below
			log_message('warning', '[Subscription] PesaPal initiate failed: {r}', ['r' => $res['reason'] ?? '']);
		}

		// Gateway not configured (or initiate failed) → record the request + notify
		// super-admins, who confirm the renewal via the company lifecycle tools.
		(new CompanymembershipModel())->where('company_id', $companyId)
			->set(['membership_id' => $planId, 'update_at' => date('d-m-Y h:i:s')])->update();

		try {
			$supers = (new UsersModel())->where('user_type', 'super_user')->findAll();
			service('notifier')->send(array_column($supers, 'user_id'), 'renewal_requested', [
				'title' => 'Renewal request',
				'body'  => ($me['company_name'] ?? 'A company') . ' requested to renew on the ' . $plan['membership_type'] . ' plan.',
				'link'  => site_url('erp/companies-list'),
			]);
		} catch (\Throwable $e) {}
		try {
			service('audit')->record('subscription.renewal_requested', [
				'entity_type' => 'company', 'entity_id' => $companyId, 'company_id' => $companyId,
				'summary' => 'Renewal requested: ' . $plan['membership_type'],
			]);
		} catch (\Throwable $e) {}

		return redirect()->to(site_url('erp/renew'))->with('renew_success',
			'Your renewal request for the ' . $plan['membership_type'] . ' plan has been submitted. Online payment is being set up — an administrator will confirm your renewal shortly.');
	}

	/** Staff page shown by CheckLogin when their company's subscription lapsed. */
	public function subscription_locked()
	{
		$session  = \Config\Services::session();
		if (! $session->has('sup_username')) { return redirect()->to(site_url('erp/login')); }
		$xin_system = (new SystemModel())->where('setting_id', 1)->first();
		$data = [
			'title'    => 'Subscription expired | ' . ($xin_system['application_name'] ?? ''),
			'app_name' => system_setting('application_name') ?: 'Rooibok HR',
		];
		$data['subview'] = view('erp/membership/subscription_locked', $data);
		return view('erp/layout/expired_layout_main', $data);
	}

	/**
	 * Upgrade page shown by the PlanFeature filter when a tenant hits a module its
	 * plan tier does not include.
	 */
	public function feature_locked()
	{
		$session = \Config\Services::session();
		if (! $session->has('sup_username')) { return redirect()->to(site_url('erp/login')); }
		helper('main');
		$usession = $session->get('sup_username');
		$me       = (new UsersModel())->where('user_id', $usession['sup_user_id'])->first();
		$feature  = strip_tags((string) $this->request->uri->getSegment(3));
		$xin_system = (new SystemModel())->where('setting_id', 1)->first();

		$data = [
			'title'         => 'Upgrade required | ' . ($xin_system['application_name'] ?? ''),
			'path_url'      => 'membership',
			'breadcrumbs'   => 'Upgrade required',
			'feature_label' => plan_gateable_features()[$feature] ?? 'This feature',
			'is_owner'      => ($me['user_type'] ?? '') === 'company',
		];
		$data['subview'] = view('erp/membership/feature_locked', $data);
		return view('erp/layout/layout_main', $data);
	}

	public function more_subscriptions()
	{		
		$session = \Config\Services::session();
		$SystemModel = new SystemModel();
		$UsersModel = new UsersModel();
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Membership.xin_membership_plans').' | '.$xin_system['application_name'];
		$data['path_url'] = 'membership';
		$data['breadcrumbs'] = lang('Membership.xin_membership_plans');
		$data['subview'] = view('erp/membership/key_more_membership', $data);
		return view('erp/layout/layout_main', $data); //page load
		
	}
	public function upgrade_subscription()
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
		$data['title'] = lang('Dashboard.dashboard_upgrade').' | '.$xin_system['application_name'];
		$data['path_url'] = 'membership';
		$data['breadcrumbs'] = lang('Dashboard.dashboard_upgrade');
		$data['subview'] = view('erp/membership/key_subscription', $data);
		return view('erp/layout/layout_main', $data); //page load
		
	}
}
