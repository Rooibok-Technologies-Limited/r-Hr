<?php
use App\Models\SystemModel;
use App\Models\RolesModel;
use App\Models\UsersModel;
use App\Models\ConstantsModel;

$SystemModel = new SystemModel();
$UserRolesModel = new RolesModel();
$UsersModel = new UsersModel();
$ConstantsModel = new ConstantsModel();

$xin_system = $SystemModel->where('setting_id', 1)->first();
$role_user = $UserRolesModel->where('role_id', 1)->first();

$session = \Config\Services::session();
$router = service('router');

$username = $session->get('sup_username');
$user_id = (!empty($username) && is_array($username)) ? ($username['sup_user_id'] ?? 0) : 0;
$user_info = $user_id ? $UsersModel->where('user_id', $user_id)->first() : null;
?>
<?php echo view('erp/layout/layout_main_company'); ?>