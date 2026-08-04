<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use App\Filters\CheckLogin;
use App\Filters\Noauth;
use App\Filters\SuperAuth;
use App\Filters\CompanyAuth;
use App\Filters\JwtAuth;
use App\Filters\Throttle;
use App\Filters\DemoMode;

class Filters extends BaseConfig
{
	/**
	 * Configures aliases for Filter classes to
	 * make reading things nicer and simpler.
	 *
	 * @var array
	 */
	public $aliases = [
		'csrf'     => CSRF::class,
		'toolbar'  => DebugToolbar::class,
		'honeypot' => Honeypot::class,
		'checklogin' => CheckLogin::class,
		'noauth' => Noauth::class,
		'superauth' => SuperAuth::class,
		'companyauth' => CompanyAuth::class,
		'companyarea' => \App\Filters\CompanyArea::class,
		'jwt'         => JwtAuth::class,
		'throttle'    => Throttle::class,
		'demo'        => DemoMode::class,
		'tenantguard' => \App\Filters\TenantGuard::class,
		'planfeature' => \App\Filters\PlanFeature::class,
	];

	/**
	 * List of filter aliases that are always
	 * applied before and after every request.
	 *
	 * @var array
	 */
	public $globals = [
		'before' => [
			'tenantguard',
			'honeypot',
			'csrf' => ['except' => ['api/*']],
		],
		'after'  => [
			'toolbar',
			'honeypot'
		],
	];

	/**
	 * List of filter aliases that works on a
	 * particular HTTP method (GET, POST, etc.).
	 *
	 * Example:
	 * 'post' => ['csrf', 'throttle']
	 *
	 * @var array
	 */
	public $methods = [];

	/**
	 * List of filter aliases that should run on any
	 * before or after URI patterns.
	 *
	 * Example:
	 * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
	 *
	 * @var array
	 */
	public $filters = [
		// Feature-gating by plan tier — runs alongside each route's checklogin.
		'planfeature' => ['before' => [
			'erp/payroll*', 'erp/disbursements*', 'erp/payout-methods*', 'erp/advance-salary*', 'erp/loan-request*',
			'erp/jobs*', 'erp/recruitment*',
			'erp/performance*', 'erp/goals*', 'erp/goal-type*', 'erp/competencies*',
			'erp/training*',
			'erp/projects*', 'erp/tasks*',
			'erp/product*', 'erp/warehouse*',
		]],
	];
}