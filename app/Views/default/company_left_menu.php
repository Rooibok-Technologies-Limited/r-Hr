<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Admin (company) sidebar — thin shim. All structure lives in the single source
 * of truth (Config\Navigation) and is filtered by App\Libraries\NavBuilder, then
 * rendered by default/navigation.php. See audit/NAVIGATION.md.
 */
use App\Libraries\NavBuilder;
use App\Models\UsersModel;

$session   = \Config\Services::session();
$usession  = $session->get('sup_username');
$uid       = is_array($usession) ? ($usession['sup_user_id'] ?? 0) : 0;
$uinfo     = $uid ? (new UsersModel())->where('user_id', $uid)->first() : null;
$utype     = $uinfo['user_type'] ?? 'company';
$resources = function_exists('staff_role_resource') ? (array) staff_role_resource() : [];
$curPath   = trim(service('request')->getPath(), '/');

$nav_groups = (new NavBuilder($utype, $resources, $curPath))->build();
echo view('default/navigation', ['nav_groups' => $nav_groups]);
