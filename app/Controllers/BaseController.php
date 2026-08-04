<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Controllers;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 *
 * @package CodeIgniter
 */

use CodeIgniter\Controller;
class BaseController extends Controller
{

	/**
	 * An array of helpers to be loaded automatically upon
	 * class instantiation. These helpers will be available
	 * to all other controllers that extend BaseController.
	 *
	 * @var array
	 */
	protected $helpers = ['form','html','inflector','number','security','text','url','string','main','filesystem','encrypt','timehr'];

	/**
	 * Constructor.
	 */
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);

		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		$language = \Config\Services::language();
		
		$UsersModel = new \App\Models\UsersModel();
		$SystemModel = new \App\Models\SystemModel();
		if(!empty($usession) && is_array($usession) && !empty($usession['sup_user_id'])){
			$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
			if(empty($user_info)){
				// Stale/invalid session — user no longer exists in DB. Destroy it.
				$session->destroy();
			} else if($user_info['user_type'] == 'super_user'){
				$xin_system = $SystemModel->where('setting_id', 1)->first();
				if(!empty($xin_system)){
					$language->setLocale($xin_system['default_language']);
					date_default_timezone_set($xin_system['system_timezone']);
				}
			} else {
				$xin_system = erp_company_settings();
				if(!empty($xin_system)){
					$language->setLocale($xin_system['default_language']);
					date_default_timezone_set($xin_system['system_timezone']);
				}
			}
			if(!empty($session->lang)){
				$language->setLocale($session->lang);
			}
		}
		//use App\Models\SystemModel;
		//--------------------------------------------------------------------
		// Preload any models, libraries, etc, here.
		//--------------------------------------------------------------------
		// E.g.:
		// $this->session = \Config\Services::session();
	}
	
	/**
	 * Strip commas from a POST value (for numeric fields like price, amount, salary)
	 */
	protected function numericPost(string $field): string {
		$val = strip_tags(trim($this->request->getPost($field) ?? ''));
		return str_replace(',', '', $val);
	}

	/**
	 * Company scope for the current session, used to tenant-guard record
	 * mutations/reads against cross-tenant IDOR. Staff resolve to their
	 * `company_id` column; company owners and super users resolve to their own
	 * `user_id` (which IS the company_id in this schema). Mirrors the inline
	 * staff/else idiom already used across the ERP save handlers, so scoped
	 * queries behave identically to the existing Finance delete exemplars.
	 * Returns 0 when unauthenticated (callers sit behind the login filter).
	 */
	protected function tenantCompanyId(): int {
		$usession = \Config\Services::session()->get('sup_username');
		$uid = (int) ($usession['sup_user_id'] ?? 0);
		if ($uid === 0) {
			return 0;
		}
		$u = (new \App\Models\UsersModel())->where('user_id', $uid)->first();
		if ($u && ($u['user_type'] ?? '') === 'staff') {
			return (int) $u['company_id'];
		}
		return $uid;
	}

	/**
	 * Ownership guard for employee-record mutations. Here $id is a target
	 * employee's user_id. Returns true when the target is the acting user
	 * themselves (owners carry company_id=0 on their own ci_erp_users row, so a
	 * plain company_id scope would lock them out of their own account) or a user
	 * belonging to the acting session's company. Use as a guard-then-act check on
	 * ci_erp_users / ci_erp_users_details writes.
	 */
	protected function ownsEmployee($id): bool {
		$id = (int) $id;
		if ($id === 0) {
			return false;
		}
		$usession = \Config\Services::session()->get('sup_username');
		$ownId = (int) ($usession['sup_user_id'] ?? 0);
		if ($id === $ownId) {
			return true;
		}
		$u = (new \App\Models\UsersModel())->select('company_id')->where('user_id', $id)->first();
		return $u !== null && (int) $u['company_id'] === $this->tenantCompanyId();
	}

	/*Function to set JSON output*/
	public function output($Return=array()){
		/*Set response header*/
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json; charset=UTF-8");
		/*Final JSON response*/
		exit(json_encode($Return));
	}
}
