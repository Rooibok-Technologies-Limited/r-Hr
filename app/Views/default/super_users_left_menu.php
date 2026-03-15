<?php
use App\Models\SystemModel;
use App\Models\UsersModel;

$SystemModel = new SystemModel();
$UsersModel = new UsersModel();
$session = \Config\Services::session();
$usession = get_safe_session();
$user_info = $usession ? $UsersModel->where('user_id', $usession['sup_user_id'])->first() : null;

$p = $path_url ?? '';
function sa_active($path_url, $match) { return $path_url === $match ? 'active' : ''; }
function sa_sub_active($path_url, $match) { return $path_url === $match ? 'active' : ''; }
?>

<ul class="pc-navbar">

  <!-- OVERVIEW -->
  <li class="pc-item pc-caption"><label>Overview</label></li>
  <li class="pc-item <?= sa_active($p, 'dashboard'); ?>">
    <a href="<?= site_url('erp/desk'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="home"></i></span><span class="pc-mtext">Dashboard</span></a>
  </li>

  <!-- COMPANY MANAGEMENT -->
  <li class="pc-item pc-caption"><label>Company Management</label></li>
  <li class="pc-item <?= sa_active($p, 'companies'); ?>">
    <a href="<?= site_url('erp/companies-list'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="briefcase"></i></span><span class="pc-mtext">All Companies</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'membership'); ?>">
    <a href="<?= site_url('erp/membership-list'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="layers"></i></span><span class="pc-mtext">Subscription Plans</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'all_subscription_invoices'); ?>">
    <a href="<?= site_url('erp/all-subscription-invoices'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="file-text"></i></span><span class="pc-mtext">All Invoices</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'invoice_payments'); ?>">
    <a href="<?= site_url('erp/billing-invoices'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="credit-card"></i></span><span class="pc-mtext">Payment History</span></a>
  </li>

  <!-- USERS & ACCESS -->
  <li class="pc-item pc-caption"><label>Users &amp; Access</label></li>
  <li class="pc-item <?= sa_active($p, 'users'); ?>">
    <a href="<?= site_url('erp/super-users'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="user-plus"></i></span><span class="pc-mtext">Staff Users</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'user_roles'); ?>">
    <a href="<?= site_url('erp/users-role'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="shield"></i></span><span class="pc-mtext">User Roles</span></a>
  </li>

  <!-- CONTENT -->
  <li class="pc-item pc-caption"><label>Content</label></li>
  <li class="pc-item <?= sa_active($p, 'landing_cms'); ?>">
    <a href="<?= site_url('erp/landing-page'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="layout"></i></span><span class="pc-mtext">Landing Page CMS</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'broadcasts'); ?>">
    <a href="<?= site_url('erp/broadcasts'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="send"></i></span><span class="pc-mtext">Broadcasts</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'broadcast_templates'); ?>">
    <a href="<?= site_url('erp/broadcasts/templates'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="copy"></i></span><span class="pc-mtext">Broadcast Templates</span></a>
  </li>

  <!-- CONFIGURATION -->
  <li class="pc-item pc-caption"><label>Configuration</label></li>
  <li class="pc-item <?= sa_active($p, 'settings'); ?>">
    <a href="<?= site_url('erp/system-settings'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="settings"></i></span><span class="pc-mtext">General Settings</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'constants'); ?>">
    <a href="<?= site_url('erp/system-constants'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="sliders"></i></span><span class="pc-mtext">Constants</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'email_template'); ?>">
    <a href="<?= site_url('erp/email-templates'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="mail"></i></span><span class="pc-mtext">Email Templates</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'sms_template'); ?>">
    <a href="<?= site_url('erp/sms-templates'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="message-circle"></i></span><span class="pc-mtext">SMS Templates</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'theme_settings'); ?>">
    <a href="<?= site_url('erp/theme-settings'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="eye"></i></span><span class="pc-mtext">Theme Settings</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'payment_settings'); ?>">
    <a href="<?= site_url('erp/system-payment-settings'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="credit-card"></i></span><span class="pc-mtext">Payment Gateways</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'tax_settings'); ?>">
    <a href="<?= site_url('erp/system-tax-settings'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="percent"></i></span><span class="pc-mtext">Tax (PAYE/NSSF)</span></a>
  </li>

  <!-- TOOLS -->
  <li class="pc-item pc-caption"><label>Tools</label></li>
  <li class="pc-item <?= sa_active($p, 'database_backup'); ?>">
    <a href="<?= site_url('erp/system-backup'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="database"></i></span><span class="pc-mtext">Database Backup</span></a>
  </li>
  <li class="pc-item <?= sa_active($p, 'archive_dashboard'); ?>">
    <a href="<?= site_url('erp/archive'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="archive"></i></span><span class="pc-mtext">Archive Portal</span></a>
  </li>
  <li class="pc-item">
    <a href="<?= site_url('api/docs'); ?>" class="pc-link" target="_blank"><span class="pc-micon"><i data-feather="book-open"></i></span><span class="pc-mtext">API Documentation</span></a>
  </li>

  <!-- LOGOUT -->
  <li class="pc-item pc-caption"><label>&nbsp;</label></li>
  <li class="pc-item">
    <a href="<?= site_url('erp/system-logout'); ?>" class="pc-link"><span class="pc-micon"><i data-feather="log-out"></i></span><span class="pc-mtext">Logout</span></a>
  </li>

</ul>
