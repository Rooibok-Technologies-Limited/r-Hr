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
}
