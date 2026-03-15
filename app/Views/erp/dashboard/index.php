<?php
use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\LanguageModel;

$SystemModel = new SystemModel();
$UsersModel = new UsersModel();
$LanguageModel = new LanguageModel();

$session = \Config\Services::session();
$usession = $session->get('sup_username');
$router = service('router');
$xin_system = $SystemModel->where('setting_id', 1)->first();
$_uid_d = (!empty($usession) && is_array($usession)) ? ($usession['sup_user_id'] ?? 0) : 0;
$user = $_uid_d ? $UsersModel->where('user_id', $_uid_d)->first() : null;
$locale = service('request')->getLocale();
?>
<?php if($session->get('unauthorized_module')){?>
<div class="alert alert-danger alert-dismissible fade show">
  <button type="button" class="close" data-dismiss="alert">×</button>
  <?= $session->get('unauthorized_module');?>
</div>
<?php } ?>
<?php
$first_date = strtotime("2021-03-11 21:00:00");
$second_date = strtotime("2021-03-11 09:00:00");
//$second_date = strtotime("2021-03-12 05:00:00");
//echo round(abs($first_date - $second_date) / 60,2) / 60 ." hours"; 
$_utype_d = !empty($user) ? $user['user_type'] : '';
if($_utype_d == 'customer'){
	$inf = 'customer';
	echo view('erp/dashboard/clients_dashboard');
} elseif($_utype_d == 'staff'){
	$inf = 'staff_dashboard';
	echo view('erp/dashboard/staff_dashboard');
} elseif($_utype_d == 'company'){
	$inf = 'company';
	echo view('erp/dashboard/company_dashboard');
} elseif($_utype_d == 'super_user'){
	$inf = 'super_admin_dashboard';
	echo view('erp/dashboard/super_admin_dashboard');
} else {
	$inf = 'staff_dashboard';
	echo view('erp/dashboard/staff_dashboard');
}
?>
<? //= $inf;?>