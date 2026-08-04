<?php namespace App\Filters;
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\UsersModel;
use App\Models\CompanymembershipModel;

class CheckLogin implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Do something here
		$UsersModel = new UsersModel();
		$session = \Config\Services::session();
		if(!$session->has('sup_username')){
			$session->setFlashdata('err_not_logged_in',lang('Dashboard.err_not_logged_in'));
			return redirect()->to(site_url('erp/login'));
		}
		$usession = $session->get('sup_username');

		// Validate session structure
		if (!is_array($usession) || empty($usession['sup_user_id'])) {
			$session->destroy();
			return redirect()->to(site_url('erp/login'));
		}

		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

		// User no longer exists in DB (stale session after power loss, DB reset, etc.)
		if (empty($user_info) || $user_info['is_active'] != 1) {
			$session->destroy();
			return redirect()->to(site_url('erp/login'));
		}

		// Subscription enforcement (ADR: registration billing). A company's
		// subscription is keyed on the OWNER's user_id: an owner (user_type=company)
		// carries company_id=0 and IS the company, so the effective company id is
		// their own user_id; staff carry the owner's id in company_id.
		if ($user_info['user_type'] !== 'super_user') {
			$effCompany = ($user_info['user_type'] === 'company')
				? (int) $user_info['user_id']
				: (int) $user_info['company_id'];

			if ($effCompany > 0) {
				$membership = (new CompanymembershipModel())->where('company_id', $effCompany)->first();
				$today = date('Y-m-d');
				$expired = $membership && (
					(int) $membership['is_active'] !== 1
					|| (! empty($membership['expiry_date']) && $membership['expiry_date'] < $today)
				);

				if ($expired) {
					// Do NOT destroy the session — the owner must be able to renew.
					// Confine expired tenants to the renewal wall; exempt the wall
					// itself, its actions, the locked page and logout (no loop).
					$path   = ltrim((string) $request->getPath(), '/');
					$exempt = ['erp/renew', 'erp/subscription-locked', 'erp/system-logout', 'erp/logout'];
					$isExempt = false;
					foreach ($exempt as $ex) {
						if ($path === $ex || strpos($path, $ex . '/') === 0) { $isExempt = true; break; }
					}
					if (! $isExempt) {
						return ($user_info['user_type'] === 'company')
							? redirect()->to(site_url('erp/renew'))            // owner: self-serve renewal wall
							: redirect()->to(site_url('erp/subscription-locked')); // staff: contact-admin page
					}
				}
			}
		}
    }

    //--------------------------------------------------------------------

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}