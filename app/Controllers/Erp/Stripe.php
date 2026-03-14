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

require_once APPPATH . 'ThirdParty/Stripe/init.php';


use App\Models\SystemModel;
use App\Models\MainModel;
use App\Models\UsersModel;
use App\Models\ConstantsModel;
use App\Models\MembershipModel;
use App\Models\InvoicepaymentsModel;
use App\Models\CompanymembershipModel;

class Stripe extends BaseController {

	/**
	 * Legacy one-time payment (kept for backward compatibility).
	 */
	public function payment()
	{
        $validation =  \Config\Services::validation();
		$session = \Config\Services::session();
		$request = \Config\Services::request();
		$usession = $session->get('sup_username');
		// get order info
		$token = udecode(strip_tags(trim($this->request->getPost('token'))));
		$stripe_info = udecode(strip_tags(trim($this->request->getPost('stripe_info'))));

		// Membership details
		$UsersModel = new UsersModel();
		$MembershipModel = new MembershipModel();
		$result = $MembershipModel->where('membership_id', $token)->first();
		$company_id = $UsersModel->where('user_id', $usession['sup_user_id'])->where('user_type','company')->first();

		$xin_system = erp_company_settings();
		\Stripe\Stripe::setApiKey(system_setting('stripe_secret_key'));
		$converted = currency_converter($result['price']);
		$converted = number_format($converted,2);

		$charge = \Stripe\Charge::create ([
                "amount" => $converted * 100,
                "currency" => $xin_system['default_currency'],
                "source" => strip_tags(trim($this->request->getPost('stripeToken'))),
                "description" => $result['membership_type']
        ]);
		$chargeJson = $charge->jsonSerialize();

	  $data = [
			'invoice_id'  => $chargeJson['balance_transaction'],
			'company_id' => $usession['sup_user_id'],
			'membership_id'  => $result['membership_id'],
			'subscription_id'  => $result['subscription_id'],
			'membership_type'  => $result['membership_type'],
			'subscription'  => $result['plan_duration'],
			'description'  => $result['description'],
			'membership_price'  => $converted,
			'payment_method'  => 'Stripe',
			'invoice_month'  => date('Y-m'),
			'transaction_date'  => date('Y-m-d h:i:s'),
			'created_at' => date('Y-m-d h:i:s'),
			'receipt_url'  => $chargeJson['receipt_url'],
			'source_info'  => $chargeJson['payment_method_details']['card']['brand'],
		];
	  $InvoicepaymentsModel = new InvoicepaymentsModel();
	  $result1 = $InvoicepaymentsModel->insert($data);
		$data2 = array(
		'membership_id'  => $result['membership_id'],
		'subscription_type'  => $result['plan_duration'],
		'update_at' => date('Y-m-d h:i:s'),
		'billing_mode' => 'manual',
		);
		$MainModel = new MainModel();
		$MainModel->update_company_membership($data2,$usession['sup_user_id']);
	  $session->setFlashdata('payment_made_successfully',lang('Membership.payment_made_successfully'));
	  return redirect()->to(site_url('erp/my-subscription'));
    }

	/**
	 * Create a Stripe Subscription (auto-renew flow).
	 *
	 * Expects POST data:
	 *   - token          : membership_id (encoded)
	 *   - paymentMethodId: Stripe PaymentMethod ID from Stripe.js
	 */
	public function create_subscription()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');

		if (empty($usession['sup_user_id'])) {
			$session->setFlashdata('error', 'Session expired. Please log in again.');
			return redirect()->to(site_url('erp/my-subscription'));
		}

		$membershipId   = udecode(strip_tags(trim($this->request->getPost('token'))));
		$paymentMethodId = strip_tags(trim($this->request->getPost('paymentMethodId')));

		$UsersModel     = new UsersModel();
		$MembershipModel = new MembershipModel();
		$CompanymembershipModel = new CompanymembershipModel();

		$membership = $MembershipModel->where('membership_id', $membershipId)->first();
		if (!$membership) {
			$session->setFlashdata('error', 'Invalid membership plan.');
			return redirect()->to(site_url('erp/my-subscription'));
		}

		$companyUser = $UsersModel->where('user_id', $usession['sup_user_id'])
		                          ->where('user_type', 'company')
		                          ->first();
		if (!$companyUser) {
			$session->setFlashdata('error', 'Company account not found.');
			return redirect()->to(site_url('erp/my-subscription'));
		}

		$xin_system = erp_company_settings();
		\Stripe\Stripe::setApiKey(system_setting('stripe_secret_key'));

		try {
			// Check if this company already has a Stripe customer
			$currentMembership = $CompanymembershipModel
				->where('company_id', $usession['sup_user_id'])
				->first();

			$stripeCustomerId = $currentMembership['stripe_customer_id'] ?? null;

			if (empty($stripeCustomerId)) {
				// Create a new Stripe Customer
				$customer = \Stripe\Customer::create([
					'email'          => $companyUser['email'] ?? '',
					'name'           => $companyUser['company_name'] ?? $companyUser['first_name'] ?? '',
					'payment_method' => $paymentMethodId,
					'invoice_settings' => [
						'default_payment_method' => $paymentMethodId,
					],
					'metadata' => [
						'company_id' => $usession['sup_user_id'],
					],
				]);
				$stripeCustomerId = $customer->id;
			} else {
				// Attach the new PaymentMethod and set as default
				\Stripe\PaymentMethod::retrieve($paymentMethodId)->attach([
					'customer' => $stripeCustomerId,
				]);
				\Stripe\Customer::update($stripeCustomerId, [
					'invoice_settings' => [
						'default_payment_method' => $paymentMethodId,
					],
				]);
			}

			// Create the Stripe Subscription using the plan's subscription_id (Stripe Price ID)
			$subscription = \Stripe\Subscription::create([
				'customer' => $stripeCustomerId,
				'items'    => [
					['price' => $membership['subscription_id']],
				],
				'metadata' => [
					'company_id'    => $usession['sup_user_id'],
					'membership_id' => $membershipId,
				],
				'expand' => ['latest_invoice.payment_intent'],
			]);

			// Update company membership record
			$CompanymembershipModel->where('company_id', $usession['sup_user_id'])->set([
				'membership_id'      => $membershipId,
				'stripe_customer_id' => $stripeCustomerId,
				'stripe_sub_id'      => $subscription->id,
				'billing_mode'       => 'auto',
				'auto_renew'         => 1,
				'subscription_type'  => $membership['plan_duration'],
				'updated_at'         => date('Y-m-d H:i:s'),
			])->update();

			$session->setFlashdata('payment_made_successfully', lang('Membership.payment_made_successfully'));

		} catch (\Stripe\Exception\CardException $e) {
			log_message('error', 'Stripe card error: ' . $e->getMessage());
			$session->setFlashdata('error', 'Card declined: ' . $e->getMessage());
		} catch (\Exception $e) {
			log_message('error', 'Stripe subscription error: ' . $e->getMessage());
			$session->setFlashdata('error', 'Payment failed. Please try again.');
		}

		return redirect()->to(site_url('erp/my-subscription'));
	}

	/**
	 * Cancel a Stripe Subscription (switch to manual billing).
	 *
	 * Cancels the Stripe subscription at period end, keeps the current expiry date,
	 * and sets auto_renew = 0, billing_mode = 'manual'.
	 */
	public function cancel_subscription()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');

		if (empty($usession['sup_user_id'])) {
			$session->setFlashdata('error', 'Session expired. Please log in again.');
			return redirect()->to(site_url('erp/my-subscription'));
		}

		$CompanymembershipModel = new CompanymembershipModel();
		$currentMembership = $CompanymembershipModel
			->where('company_id', $usession['sup_user_id'])
			->first();

		if (empty($currentMembership['stripe_sub_id'])) {
			$session->setFlashdata('error', 'No active subscription to cancel.');
			return redirect()->to(site_url('erp/my-subscription'));
		}

		\Stripe\Stripe::setApiKey(system_setting('stripe_secret_key'));

		try {
			// Cancel at period end so the client keeps access until expiry
			\Stripe\Subscription::update($currentMembership['stripe_sub_id'], [
				'cancel_at_period_end' => true,
			]);

			// Update local record: switch to manual, disable auto-renew
			$CompanymembershipModel->where('company_id', $usession['sup_user_id'])->set([
				'auto_renew'   => 0,
				'billing_mode' => 'manual',
				'updated_at'   => date('Y-m-d H:i:s'),
			])->update();

			$session->setFlashdata('payment_made_successfully', 'Auto-renewal cancelled. Your subscription remains active until the expiry date.');

		} catch (\Exception $e) {
			log_message('error', 'Stripe cancel error: ' . $e->getMessage());
			$session->setFlashdata('error', 'Failed to cancel subscription. Please try again.');
		}

		return redirect()->to(site_url('erp/my-subscription'));
	}

}
