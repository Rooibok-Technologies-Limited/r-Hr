<?php
use App\Models\SystemModel;
use App\Models\SuperroleModel;
use App\Models\UsersModel;
use App\Models\MembershipModel;
use App\Models\CompanymembershipModel;

$SystemModel = new SystemModel();
$UsersModel = new UsersModel();
$SuperroleModel = new SuperroleModel();
$MembershipModel = new MembershipModel();
$CompanymembershipModel = new CompanymembershipModel();
$session = \Config\Services::session();
$router = service('router');
$usession = $session->get('sup_username');
$_uid_m = (!empty($usession) && is_array($usession)) ? ($usession['sup_user_id'] ?? 0) : 0;
$user_info = $_uid_m ? $UsersModel->where('user_id', $_uid_m)->first() : null;
$xin_system = $SystemModel->where('setting_id', 1)->first();
$_utype = !empty($user_info) ? $user_info['user_type'] : '';
?>
<?php $arr_mod = select_module_class($router->controllerName(),$router->methodName()); ?>
<?php if($_utype == 'super_user'){ ?>
	<?php // super users menu?>
    <?= view('default/super_users_left_menu');?>
<?php } ?>
<?php if($_utype == 'company'){ ?>
	<?php // main company menu?>
    <?= view('default/company_left_menu');?>
<?php } ?>
<?php if($_utype == 'staff'){?>
	<?php // staff menu?>
    <?= view('default/staff_left_menu');?>
<?php } ?>
<?php if($_utype == 'customer'){?>
	<?php // client menu?>
    <?= view('default/client_left_menu');?>
<?php } ?>
