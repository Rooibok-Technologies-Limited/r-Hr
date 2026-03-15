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
use CodeIgniter\I18n\Time;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
 
use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\LanguageModel;
use App\Models\MembershipModel;
use App\Models\CompanymembershipModel;

class Dashboard extends BaseController {

	public function index()
	{		
		$SystemModel = new SystemModel();
		$UsersModel = new UsersModel();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = lang('Dashboard.dashboard_title').' | '.$xin_system['application_name'];
		$data['path_url'] = 'dashboard';
		$MembershipModel = new MembershipModel();
		$CompanymembershipModel = new CompanymembershipModel();
		// check company membership plan expiry date
		$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
		$data['subview'] = view('erp/dashboard/index', $data);
		return view('erp/layout/layout_main', $data); //page load
	}
	
	/**
	 * AJAX: Revenue chart data by period (6m, 12m, all)
	 */
	public function revenue_chart()
	{
		$request = \Config\Services::request();
		$period = $request->getGet('period') ?? '6';
		$db = \Config\Database::connect();

		if ($period === 'all') {
			// For "all time", find the earliest invoice and count months from there (cap at 36)
			$earliest = $db->query("SELECT MIN(issued_at) AS e FROM ci_subscription_invoices WHERE status='paid'");
			$earliest_date = $earliest ? ($earliest->getRow()->e ?? null) : null;
			if ($earliest_date) {
				$diff = (int)ceil((time() - strtotime($earliest_date)) / (30 * 86400));
				$months_count = min(max($diff, 1), 36);
			} else {
				$months_count = 6;
			}
			$interval = "AND si.issued_at >= NOW() - INTERVAL '{$months_count} months'";
		} elseif ($period === '12') {
			$interval = "AND si.issued_at >= NOW() - INTERVAL '12 months'";
			$months_count = 12;
		} else {
			$interval = "AND si.issued_at >= NOW() - INTERVAL '6 months'";
			$months_count = 6;
		}

		$q = $db->query("SELECT TO_CHAR(si.issued_at,'YYYY-MM') AS month, m.membership_type, COALESCE(SUM(si.amount),0) AS total FROM ci_subscription_invoices si JOIN ci_membership m ON m.membership_id=si.membership_id WHERE si.status='paid' {$interval} GROUP BY month,m.membership_type ORDER BY month");
		$raw = $q ? $q->getResultArray() : [];

		$months_list = [];
		for ($i = $months_count - 1; $i >= 0; $i--) {
			$months_list[] = date('Y-m', strtotime("-{$i} months"));
		}

		$plan_types = array_values(array_unique(array_column($raw, 'membership_type')));
		if (empty($plan_types)) $plan_types = ['Pro Plan'];

		$chart = [];
		$total = 0;
		foreach ($months_list as $m) {
			$row = ['month' => date('M', strtotime($m . '-01')), 'year' => date('Y', strtotime($m . '-01'))];
			foreach ($plan_types as $p) $row[$p] = 0;
			foreach ($raw as $r) {
				if ($r['month'] === $m) {
					$row[$r['membership_type']] = (float)$r['total'];
					$total += (float)$r['total'];
				}
			}
			$chart[] = $row;
		}

		return $this->response->setJSON([
			'plan_types' => $plan_types,
			'chart' => $chart,
			'total' => $total,
			'period' => $period,
		]);
	}

	/**
	 * AJAX: Lightweight KPI refresh (numbers only, no sparklines/charts)
	 */
	public function kpi_refresh()
	{
		$db = \Config\Database::connect();
		$UsersModel = new UsersModel();

		$total_companies = $UsersModel->where('user_type','company')->countAllResults();
		$_as = $db->query("SELECT COUNT(*) AS c FROM ci_company_membership WHERE is_active=1");
		$active_subs = $_as ? (int)($_as->getRow()->c ?? 0) : 0;
		$inactive_subs = $total_companies - $active_subs;
		$activation_rate = $total_companies > 0 ? round(($active_subs/$total_companies)*100,1) : 0;

		$_rtm = $db->query("SELECT COALESCE(SUM(amount),0) AS s FROM ci_subscription_invoices WHERE status='paid' AND TO_CHAR(issued_at,'YYYY-MM')=TO_CHAR(NOW(),'YYYY-MM')");
		$rev_this_month = $_rtm ? (float)($_rtm->getRow()->s ?? 0) : 0;
		$_rlm = $db->query("SELECT COALESCE(SUM(amount),0) AS s FROM ci_subscription_invoices WHERE status='paid' AND TO_CHAR(issued_at,'YYYY-MM')=TO_CHAR(NOW()-INTERVAL '1 month','YYYY-MM')");
		$rev_last_month = $_rlm ? (float)($_rlm->getRow()->s ?? 0) : 0;
		$rev_change_pct = $rev_last_month > 0 ? round((($rev_this_month-$rev_last_month)/$rev_last_month)*100) : 0;

		$_ntm = $db->query("SELECT COUNT(*) AS c FROM ci_erp_users WHERE user_type='company' AND TO_CHAR(safe_to_timestamp(created_at),'YYYY-MM')=TO_CHAR(NOW(),'YYYY-MM')");
		$new_this_month = $_ntm ? (int)($_ntm->getRow()->c ?? 0) : 0;

		$_mrr = $db->query("SELECT COALESCE(SUM(m.price),0) AS mrr FROM ci_company_membership cm JOIN ci_membership m ON m.membership_id=cm.membership_id WHERE cm.is_active=1");
		$mrr = $_mrr ? (float)($_mrr->getRow()->mrr ?? 0) : 0;

		$_ch = $db->query("SELECT COUNT(*) AS c FROM ci_company_membership WHERE is_active=0 AND expiry_date>=DATE_TRUNC('month',NOW()) AND expiry_date<=NOW()");
		$churned = $_ch ? (int)($_ch->getRow()->c ?? 0) : 0;
		$churn_rate = $total_companies > 0 ? round(($churned/$total_companies)*100,1) : 0;

		$_fc = $db->query("SELECT COALESCE(SUM(m.price),0) AS f FROM ci_company_membership cm JOIN ci_membership m ON m.membership_id=cm.membership_id WHERE cm.is_active=1 AND cm.expiry_date>(DATE_TRUNC('month',NOW())+INTERVAL '2 months'-INTERVAL '1 day')");
		$forecast = $_fc ? (float)($_fc->getRow()->f ?? 0) : 0;

		return $this->response->setJSON([
			'total_companies'=>$total_companies,'active_subs'=>$active_subs,'inactive_subs'=>$inactive_subs,
			'rev_this_month'=>$rev_this_month,'rev_change_pct'=>$rev_change_pct,'new_this_month'=>$new_this_month,
			'activation_rate'=>$activation_rate,'mrr'=>$mrr,'churn_rate'=>$churn_rate,'forecast'=>$forecast,
		]);
	}

	/**
	 * AJAX: Global search across companies, employees, invoices
	 */
	public function global_search()
	{
		$request = \Config\Services::request();
		$q = trim($request->getGet('q') ?? '');
		if (strlen($q) < 2) {
			return $this->response->setJSON(['companies'=>[],'employees'=>[],'invoices'=>[]]);
		}

		$db = \Config\Database::connect();
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		$user_id = $usession['sup_user_id'] ?? 0;
		$UsersModel = new UsersModel();
		$user = $UsersModel->where('user_id', $user_id)->first();
		$user_type = $user['user_type'] ?? '';

		$term = '%' . $db->escapeLikeString($q) . '%';

		// Companies (super_user sees all, company sees only self)
		$companies = [];
		if ($user_type === 'super_user') {
			$cq = $db->query("SELECT user_id, company_name, email, city FROM ci_erp_users WHERE user_type='company' AND (company_name ILIKE ".$db->escape($term)." OR email ILIKE ".$db->escape($term).") LIMIT 5");
			$companies = $cq ? $cq->getResultArray() : [];
		}

		// Employees
		$emp_where = '';
		if ($user_type === 'company') {
			$emp_where = " AND company_id=" . (int)$user_id;
		} elseif ($user_type === 'staff') {
			$emp_where = " AND company_id=" . (int)($user['company_id'] ?? 0);
		}
		$eq = $db->query("SELECT user_id, first_name, last_name, email FROM ci_erp_users WHERE user_type='staff'".$emp_where." AND (first_name ILIKE ".$db->escape($term)." OR last_name ILIKE ".$db->escape($term)." OR email ILIKE ".$db->escape($term).") LIMIT 5");
		$employees = $eq ? $eq->getResultArray() : [];

		// Invoices (super_user only)
		$invoices = [];
		if ($user_type === 'super_user') {
			$iq = $db->query("SELECT si.invoice_id, si.amount, si.status, si.currency, u.company_name FROM ci_subscription_invoices si JOIN ci_erp_users u ON u.user_id=si.company_id WHERE u.company_name ILIKE ".$db->escape($term)." OR CAST(si.invoice_id AS TEXT) LIKE ".$db->escape($term)." LIMIT 5");
			$invoices = $iq ? $iq->getResultArray() : [];
		}

		return $this->response->setJSON(['companies'=>$companies,'employees'=>$employees,'invoices'=>$invoices]);
	}

	/**
	 * Notifications management page
	 */
	public function notifications_page()
	{
		$SystemModel = new SystemModel();
		$xin_system = $SystemModel->where('setting_id', 1)->first();
		$data['title'] = 'Notifications | ' . ($xin_system['application_name'] ?? 'Rooibok HR');
		$data['path_url'] = 'notifications';
		$data['breadcrumbs'] = 'Notifications';
		$data['subview'] = view('erp/notifications/index', $data);
		return view('erp/layout/layout_main', $data);
	}

	/**
	 * AJAX: Delete a notification
	 */
	public function delete_notification()
	{
		$id = $this->request->getPost('id');
		if ($id) {
			$db = \Config\Database::connect();
			$db->table('ci_notifications')->where('notification_id', (int)$id)->delete();
		}
		return $this->response->setJSON(['ok'=>true]);
	}

	/**
	 * AJAX: Delete all notifications for current user
	 */
	public function delete_all_notifications()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (!empty($usession)) {
			$db = \Config\Database::connect();
			$uid = $usession['sup_user_id'];
			$db->table('ci_notifications')
				->groupStart()
					->where('user_id', $uid)
					->orWhere('user_id', 0)
				->groupEnd()
				->delete();
		}
		return $this->response->setJSON(['ok'=>true]);
	}

	/**
	 * AJAX: Fetch notifications for current user
	 */
	public function notifications()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (empty($usession)) return $this->response->setJSON(['notifications'=>[],'count'=>0]);

		$db = \Config\Database::connect();
		$uid = $usession['sup_user_id'];

		$notifications = $db->table('ci_notifications')
			->where('user_id', $uid)
			->orWhere('user_id', 0) // system-wide notifications
			->orderBy('created_at', 'DESC')
			->limit(15)
			->get()->getResultArray();

		$unread = $db->table('ci_notifications')
			->groupStart()
				->where('user_id', $uid)
				->orWhere('user_id', 0)
			->groupEnd()
			->where('is_read', 0)
			->countAllResults();

		return $this->response->setJSON(['notifications'=>$notifications ?? [],'count'=>$unread]);
	}

	/**
	 * AJAX: Mark notification as read
	 */
	public function mark_notification_read()
	{
		$id = $this->request->getPost('id');
		if ($id) {
			$db = \Config\Database::connect();
			$db->table('ci_notifications')->where('notification_id', (int)$id)->update(['is_read' => 1]);
		}
		return $this->response->setJSON(['ok'=>true]);
	}

	/**
	 * AJAX: Mark all notifications as read
	 */
	public function mark_all_read()
	{
		$session = \Config\Services::session();
		$usession = $session->get('sup_username');
		if (!empty($usession)) {
			$db = \Config\Database::connect();
			$uid = $usession['sup_user_id'];
			$db->table('ci_notifications')
				->groupStart()
					->where('user_id', $uid)
					->orWhere('user_id', 0)
				->groupEnd()
				->update(['is_read' => 1]);
		}
		return $this->response->setJSON(['ok'=>true]);
	}

	// set new language
	public function language($real_language = "") {
        
        $session = session();
		$request = \Config\Services::request();
		if(empty($_SERVER['HTTP_REFERER'])){
			$session->setFlashdata('unauthorized_module',lang('Dashboard.xin_error_unauthorized_module'));
			return redirect()->to(site_url('erp/desk'));
		}
        $session->remove('lang');
        $session->set('lang',$real_language);
        return redirect()->to($_SERVER['HTTP_REFERER']); 
    }
}
