<?php
/**
 * Billing Mode Selection — Phase 3.5
 * Shown on the subscription/upgrade page
 */
use App\Models\SystemModel;
$SystemModel = new SystemModel();
$settings = $SystemModel->where('setting_id', 1)->first();
$stripe_active = ($settings['stripe_active'] ?? 0) == 1;
$mtn_active = ($settings['mtn_active'] ?? 0) == 1;
$airtel_active = ($settings['airtel_active'] ?? 0) == 1;
?>

<div class="row justify-content-center mt-4">
  <div class="col-lg-10">
    <h5 class="mb-3">Choose your billing preference</h5>

    <div class="row">
      <!-- Auto-Renew (Card Only) -->
      <?php if ($stripe_active): ?>
      <div class="col-md-6 mb-3">
        <div class="card border-primary h-100" id="billing-auto" style="cursor:pointer;">
          <div class="card-body text-center">
            <div class="mb-3">
              <i class="feather icon-refresh-cw" style="font-size:36px;color:var(--primary);"></i>
            </div>
            <h5 class="card-title">Auto-Renew</h5>
            <p class="text-muted">Card only. Charged automatically 3 days before expiry. No action needed.</p>
            <ul class="list-unstyled text-left mt-3">
              <li><i class="feather icon-check text-success mr-2"></i> Automatic renewal</li>
              <li><i class="feather icon-check text-success mr-2"></i> Never lose access</li>
              <li><i class="feather icon-check text-success mr-2"></i> Cancel anytime</li>
              <li><i class="feather icon-credit-card text-muted mr-2"></i> Visa / Mastercard</li>
            </ul>
            <input type="radio" name="billing_mode" value="auto" class="d-none billing-mode-radio">
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Manual Payment -->
      <div class="col-md-6 mb-3">
        <div class="card h-100" id="billing-manual" style="cursor:pointer;">
          <div class="card-body text-center">
            <div class="mb-3">
              <i class="feather icon-dollar-sign" style="font-size:36px;color:#6c757d;"></i>
            </div>
            <h5 class="card-title">Manual Payment</h5>
            <p class="text-muted">Pay with card, MTN MoMo, or Airtel Money. You choose when to renew.</p>
            <ul class="list-unstyled text-left mt-3">
              <?php if ($stripe_active): ?>
              <li><i class="feather icon-credit-card text-muted mr-2"></i> Visa / Mastercard</li>
              <?php endif; ?>
              <?php if ($mtn_active): ?>
              <li><img src="<?= site_url('public/assets/images/mtn-icon.png') ?>" width="16" class="mr-2" alt="MTN"> MTN Mobile Money</li>
              <?php endif; ?>
              <?php if ($airtel_active): ?>
              <li><img src="<?= site_url('public/assets/images/airtel-icon.png') ?>" width="16" class="mr-2" alt="Airtel"> Airtel Money</li>
              <?php endif; ?>
              <li><i class="feather icon-clock text-warning mr-2"></i> Renew before expiry</li>
            </ul>
            <input type="radio" name="billing_mode" value="manual" class="d-none billing-mode-radio" checked>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Method Selection (shown after billing mode chosen) -->
    <div id="payment-methods" class="mt-3" style="display:none;">
      <h5 class="mb-3">Select payment method</h5>
      <div class="row">

        <?php if ($stripe_active): ?>
        <div class="col-md-4 mb-3">
          <div class="card payment-method-card" data-method="stripe" style="cursor:pointer;">
            <div class="card-body text-center py-4">
              <i class="feather icon-credit-card mb-2" style="font-size:28px;"></i>
              <h6>Card Payment</h6>
              <small class="text-muted">Visa / Mastercard</small>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($mtn_active): ?>
        <div class="col-md-4 mb-3">
          <div class="card payment-method-card" data-method="mtn" style="cursor:pointer;">
            <div class="card-body text-center py-4">
              <div class="mb-2" style="font-size:28px;">📱</div>
              <h6>MTN MoMo</h6>
              <small class="text-muted">Mobile Money</small>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($airtel_active): ?>
        <div class="col-md-4 mb-3">
          <div class="card payment-method-card" data-method="airtel" style="cursor:pointer;">
            <div class="card-body text-center py-4">
              <div class="mb-2" style="font-size:28px;">📱</div>
              <h6>Airtel Money</h6>
              <small class="text-muted">Mobile Money</small>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- MTN Phone Input -->
      <div id="mtn-form" class="mt-3" style="display:none;">
        <div class="form-group">
          <label>MTN Phone Number</label>
          <input type="tel" class="form-control" name="mtn_phone" placeholder="256 7XX XXX XXX" maxlength="12">
          <small class="text-muted">You will receive a USSD prompt on this number to approve the payment.</small>
        </div>
        <button type="button" class="btn btn-warning" id="btn-pay-mtn">Pay with MTN MoMo</button>
      </div>

      <!-- Airtel Phone Input -->
      <div id="airtel-form" class="mt-3" style="display:none;">
        <div class="form-group">
          <label>Airtel Phone Number</label>
          <input type="tel" class="form-control" name="airtel_phone" placeholder="256 7XX XXX XXX" maxlength="12">
          <small class="text-muted">You will receive a USSD prompt on this number to approve the payment.</small>
        </div>
        <button type="button" class="btn btn-danger" id="btn-pay-airtel">Pay with Airtel Money</button>
      </div>

      <!-- Stripe Card Form -->
      <div id="stripe-form" class="mt-3" style="display:none;">
        <div id="card-element" class="form-control py-3"></div>
        <div id="card-errors" class="text-danger mt-2"></div>
        <button type="button" class="btn btn-primary mt-3" id="btn-pay-stripe">Pay with Card</button>
      </div>
    </div>

  </div>
</div>

<script>
// Billing mode selection
document.querySelectorAll('#billing-auto, #billing-manual').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('#billing-auto, #billing-manual').forEach(c => c.classList.remove('border-primary'));
        this.classList.add('border-primary');
        this.querySelector('.billing-mode-radio').checked = true;

        var mode = this.querySelector('.billing-mode-radio').value;
        var methods = document.getElementById('payment-methods');

        if (mode === 'auto') {
            // Auto-renew = Stripe only, skip method selection
            methods.style.display = 'none';
            document.getElementById('stripe-form').style.display = 'block';
        } else {
            methods.style.display = 'block';
            document.getElementById('stripe-form').style.display = 'none';
            document.getElementById('mtn-form').style.display = 'none';
            document.getElementById('airtel-form').style.display = 'none';
        }
    });
});

// Payment method selection (manual mode)
document.querySelectorAll('.payment-method-card').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('border-primary'));
        this.classList.add('border-primary');

        var method = this.getAttribute('data-method');
        document.getElementById('stripe-form').style.display = method === 'stripe' ? 'block' : 'none';
        document.getElementById('mtn-form').style.display = method === 'mtn' ? 'block' : 'none';
        document.getElementById('airtel-form').style.display = method === 'airtel' ? 'block' : 'none';
    });
});
</script>
