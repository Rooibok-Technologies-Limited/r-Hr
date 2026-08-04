<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
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

// This layout renders AFTER the session is destroyed (post-expiry), so no
// logged-in user is available — the old $username['sup_user_id'] lookup crashed
// on PHP 8 (offset on null). It was dead code anyway (only htmlheader + subview
// are rendered below).
?>
<?= view('default/htmlheader');?>
<?= $subview;?>