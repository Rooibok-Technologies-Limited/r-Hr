<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Renewal wall — where an expired company OWNER lands (CheckLogin). Self-serve:
 * pick a plan and submit a renewal request. Payment (PesaPal) wires in next.
 */
$session  = \Config\Services::session();
$plans    = $plans ?? [];
$cur      = $current_plan ?? null;
$exp      = $membership['expiry_date'] ?? null;
$currency = erp_currency();
?>
<div class="auth-wrapper" style="min-height:100vh;background:#f4f6fb;">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-9">

        <div class="text-center mb-4">
          <h3 class="mb-1"><?= esc($app_name ?? 'Rooibok HR'); ?></h3>
          <p class="text-muted mb-0">
            <?= esc($company_name ?: 'Your company'); ?> — your subscription has
            <?= (! empty($exp) && $exp < date('Y-m-d')) ? 'expired on <strong>' . esc($exp) . '</strong>' : 'ended'; ?>.
            Choose a plan below to continue.
          </p>
        </div>

        <?php if ($session->getFlashdata('renew_success')): ?>
          <div class="alert alert-success text-center"><?= esc($session->getFlashdata('renew_success')); ?></div>
        <?php endif; ?>
        <?php if ($session->getFlashdata('renew_error')): ?>
          <div class="alert alert-danger text-center"><?= esc($session->getFlashdata('renew_error')); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
          <div class="card-body">
            <?php if (empty($plans)): ?>
              <p class="text-center text-muted mb-0">No plans are configured yet. Please contact support.</p>
            <?php else: ?>
            <?= form_open('erp/renew/submit', ['id' => 'renew-form']); ?>
            <div class="row">
              <?php foreach ($plans as $i => $p):
                $isCur = $cur && (int) $cur['membership_id'] === (int) $p['membership_id'];
                $per   = ((int) ($p['plan_duration'] ?? 1) === 2) ? 'year' : 'month';
              ?>
              <div class="col-md-4 mb-3">
                <label class="d-block h-100" style="cursor:pointer;">
                  <input type="radio" name="membership_id" value="<?= (int) $p['membership_id']; ?>" class="d-none renew-plan" <?= ($i === 0 || $isCur) ? 'checked' : ''; ?>>
                  <div class="card h-100 border plan-card <?= $isCur ? 'border-primary' : ''; ?>" style="transition:.15s;">
                    <div class="card-body text-center">
                      <h5 class="mb-1"><?= esc($p['membership_type']); ?></h5>
                      <?php if ($isCur): ?><span class="badge badge-light-primary mb-2">Current plan</span><?php endif; ?>
                      <div class="h3 my-2">
                        <?= ((float) $p['price'] > 0) ? number_to_currency($p['price'], $currency, null, 0) : 'Free'; ?>
                        <?php if ((float) $p['price'] > 0): ?><small class="text-muted" style="font-size:14px">/ <?= $per; ?></small><?php endif; ?>
                      </div>
                      <p class="text-muted small mb-0"><?= (int) ($p['total_employees'] ?? 0); ?> employees</p>
                    </div>
                  </div>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="text-center mt-2">
              <button type="submit" class="btn btn-primary btn-lg px-5"><i class="feather icon-refresh-cw mr-1"></i> Renew subscription</button>
            </div>
            <?= form_close(); ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="text-center mt-3">
          <a href="<?= site_url('erp/system-logout'); ?>" class="text-muted"><i class="feather icon-log-out mr-1"></i> Log out</a>
        </div>

      </div>
    </div>
  </div>
</div>
<style>
  .plan-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.08); }
  .renew-plan:checked + .plan-card { border-color:#7267EF !important; box-shadow:0 4px 14px rgba(114,103,239,.18); }
</style>
