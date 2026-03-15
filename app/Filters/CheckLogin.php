<?php namespace App\Filters;

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

		// Check company membership is_active (auto-disconnect on expiry).
		// Skip for super_user accounts — they are not tied to a company subscription.
		if (! empty($user_info['company_id']) && $user_info['user_type'] !== 'super_user') {
			$CompanymembershipModel = new CompanymembershipModel();
			$membership = $CompanymembershipModel->where('company_id', $user_info['company_id'])->first();
			if ($membership && $membership['is_active'] != 1) {
				$session->setFlashdata('err_not_logged_in', 'Your company subscription has expired. Please contact your administrator to renew.');
				$session->destroy();
				return redirect()->to(site_url('erp/login'));
			}
		}
    }

    //--------------------------------------------------------------------

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}