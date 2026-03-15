<?php
// Shared configuration navigation tabs
// Usage: include at top of settings views
// Set $active_tab before including: 'settings', 'constants', 'email', 'sms', 'payments', 'tax'
$active_tab = $active_tab ?? 'settings';
$_user_type = $user['user_type'] ?? '';
?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $active_tab=='settings' ? 'active' : ''; ?>" href="<?= site_url('erp/system-settings'); ?>">
      <i class="feather icon-settings mr-1"></i> System
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab=='constants' ? 'active' : ''; ?>" href="<?= site_url('erp/system-constants'); ?>">
      <i class="feather icon-sliders mr-1"></i> Constants
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab=='email' ? 'active' : ''; ?>" href="<?= site_url('erp/email-templates'); ?>">
      <i class="feather icon-mail mr-1"></i> Email Templates
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab=='sms' ? 'active' : ''; ?>" href="<?= site_url('erp/sms-templates'); ?>">
      <i class="feather icon-message-circle mr-1"></i> SMS Templates
    </a>
  </li>
  <?php if($_user_type == 'super_user'): ?>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab=='payments' ? 'active' : ''; ?>" href="<?= site_url('erp/system-payment-settings'); ?>">
      <i class="feather icon-credit-card mr-1"></i> Payments
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab=='tax' ? 'active' : ''; ?>" href="<?= site_url('erp/system-tax-settings'); ?>">
      <i class="feather icon-percent mr-1"></i> Tax
    </a>
  </li>
  <?php endif; ?>
</ul>
