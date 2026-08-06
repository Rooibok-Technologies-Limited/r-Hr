<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Super-admin (super_user) sidebar — thin shim over the single source of truth
 * (Config\Navigation → NavBuilder → default/navigation.php). See audit/NAVIGATION.md.
 */
use App\Libraries\NavBuilder;
use App\Models\UsersModel;

$session   = \Config\Services::session();
$usession  = function_exists('get_safe_session') ? get_safe_session() : $session->get('sup_username');
$uid       = is_array($usession) ? ($usession['sup_user_id'] ?? 0) : 0;
$uinfo     = $uid ? (new UsersModel())->where('user_id', $uid)->first() : null;
$utype     = $uinfo['user_type'] ?? 'super_user';
$curPath   = trim(service('request')->getPath(), '/');

$nav_groups = (new NavBuilder($utype, [], $curPath))->build();
echo view('default/navigation', ['nav_groups' => $nav_groups]);
