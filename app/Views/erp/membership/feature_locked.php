<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
$is_owner = $is_owner ?? false;
?>
<div class="row justify-content-center">
  <div class="col-md-7 col-lg-6">
    <div class="card shadow-sm text-center">
      <div class="card-body py-5">
        <div class="mb-3"><i data-feather="lock" style="width:46px;height:46px;color:#7267EF;"></i></div>
        <h4 class="mb-2"><?= esc($feature_label ?? 'This feature'); ?> is not in your plan</h4>
        <p class="text-muted mb-4">
          <?= esc($feature_label ?? 'This feature'); ?> is available on a higher plan.
          <?= $is_owner ? 'Upgrade your subscription to unlock it.' : 'Please ask your company administrator to upgrade the plan.'; ?>
        </p>
        <?php if ($is_owner): ?>
          <a href="<?= site_url('erp/subscription-list'); ?>" class="btn btn-primary"><i class="feather icon-arrow-up-circle mr-1"></i> View plans &amp; upgrade</a>
        <?php endif; ?>
        <a href="<?= site_url('erp/desk'); ?>" class="btn btn-light ml-2">Back to dashboard</a>
      </div>
    </div>
  </div>
</div>
