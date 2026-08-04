<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Shown to STAFF when their company's subscription has lapsed (CheckLogin). Staff
 * cannot renew — only the company owner can — so this is a clear holding page.
 */
?>
<div class="auth-wrapper" style="min-height:100vh;background:#f4f6fb;">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm text-center">
          <div class="card-body py-5">
            <div class="mb-3"><i class="feather icon-lock" style="width:48px;height:48px;color:#e74c3c;"></i></div>
            <h4 class="mb-2">Access paused</h4>
            <p class="text-muted mb-4">
              Your company's <?= esc($app_name ?? 'Rooibok HR'); ?> subscription has expired.
              Please ask your company administrator to renew it to restore access.
            </p>
            <a href="<?= site_url('erp/system-logout'); ?>" class="btn btn-outline-secondary">
              <i class="feather icon-log-out mr-1"></i> Log out
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
