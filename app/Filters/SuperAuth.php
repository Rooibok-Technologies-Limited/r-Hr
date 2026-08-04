<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\UsersModel;

class SuperAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
		$session  = \Config\Services::session();
		$usession = $session->get('sup_username');
		// Not logged in at all -> send to login (avoids a desk->login bounce).
		if (empty($usession) || empty($usession['sup_user_id'])) {
			return redirect()->to(site_url('erp/login'));
		}
		// Fail-closed: a missing/deleted user row or any non-super type is denied
		// (never dereference a null $user_info — that would 500 instead of deny).
		$user_info = (new UsersModel())->where('user_id', $usession['sup_user_id'])->first();
		if (empty($user_info) || ($user_info['user_type'] ?? '') !== 'super_user') {
			$session->setFlashdata('unauthorized_module', lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}
    }

    //--------------------------------------------------------------------

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}