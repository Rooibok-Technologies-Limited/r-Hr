<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
	/**
	 * Unified notification dispatcher (in-app + email + SMS fan-out).
	 */
	public static function notifier($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('notifier');
		}

		return new \App\Libraries\Notifier();
	}

	/**
	 * Append-only, tamper-evident audit / compliance trail.
	 */
	public static function audit($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('audit');
		}

		return new \App\Libraries\Audit();
	}

	/**
	 * Employee payout destinations — capture + verification (ROADMAP F2).
	 */
	public static function payoutMethods($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('payoutMethods');
		}

		return new \App\Libraries\PayoutMethods();
	}

	/**
	 * Outbound money-movement manager (MoMo / Airtel; degrades to no-op).
	 */
	public static function disbursement($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('disbursement');
		}

		return new \App\Libraries\Disbursement\Disbursement();
	}

	/**
	 * Batch payout engine — prepare / approve / process / reconcile (ROADMAP F2).
	 */
	public static function disbursementEngine($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('disbursementEngine');
		}

		return new \App\Libraries\Disbursement\DisbursementEngine();
	}

	/**
	 * Per-company virtual wallet over the aggregator float (ROADMAP F2, ADR-002).
	 */
	public static function wallet($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('wallet');
		}

		return new \App\Libraries\WalletService();
	}

	/**
	 * Flutterwave collections (company wallet top-ups) — ROADMAP F2, ADR-002.
	 */
	public static function flutterwaveCollections($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('flutterwaveCollections');
		}

		return new \App\Libraries\FlutterwaveCollections();
	}

	/**
	 * PesaPal collections driver (wallet top-ups) — ROADMAP F2, ADR-002.
	 * Provider-specific: used by the pesapal webhook + `spark pesapal:setup`.
	 */
	public static function pesapalCollections($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('pesapalCollections');
		}

		return new \App\Libraries\Collections\PesapalCollections();
	}

	/**
	 * Tenant context (host-based multi-tenancy, ADR-003). Populated per request
	 * by the TenantResolver filter; read via service('tenant').
	 */
	public static function tenant($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('tenant');
		}

		return new \App\Libraries\Tenant();
	}

	/**
	 * Resolved wallet-funding provider (collections). Picks Flutterwave or
	 * PesaPal per the `collections_provider` setting, degrading to an
	 * unconfigured driver so callers branch on isConfigured(). ROADMAP F2.
	 */
	public static function collections($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('collections');
		}

		return (new \App\Libraries\Collections\Collections())->provider();
	}

	/**
	 * Outbound SMS gateway, selected from the `sms_gateway` system setting.
	 *
	 * Returns a NullSmsProvider (no-op) when SMS is inactive (`sms_active` off)
	 * or the selected driver is missing credentials, so callers never need to
	 * branch on configuration state.
	 */
	public static function smsProvider($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('smsProvider');
		}

		// system_setting() lives in the `main` helper, auto-loaded by
		// BaseController for web requests. This service is also resolved from the
		// CLI QueueWorker, where no controller runs, so load it explicitly here.
		helper('main');

		$active = system_setting('sms_active');
		if ($active === '' || $active === '0' || strtolower($active) === 'off') {
			return new \App\Libraries\Sms\NullSmsProvider();
		}

		$gateway = strtolower(system_setting('sms_gateway') ?: 'africastalking');

		switch ($gateway) {
			case 'africastalking':
			case 'africas_talking':
			case 'at':
				$driver = new \App\Libraries\Sms\AfricasTalkingProvider();
				return $driver->isConfigured() ? $driver : new \App\Libraries\Sms\NullSmsProvider();

			default:
				log_message('warning', '[SMS] Unknown gateway "{g}" — falling back to null provider.', ['g' => $gateway]);
				return new \App\Libraries\Sms\NullSmsProvider();
		}
	}

	/**
	 * SMTP mailer. Returns a CodeIgniter Email instance initialised from the
	 * `Config\Smtp` object (whose property names mirror the Email library), which
	 * in turn draws its credentials from `.env` (`smtp.*` keys). This backs the
	 * `email_type == 'smtp'` branch in timehrm_mail_data(); previously that branch
	 * called an undefined Services::smtp() and fataled.
	 */
	public static function smtp($getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('smtp');
		}

		return new \CodeIgniter\Email\Email(new \Config\Smtp());
	}
}
