<?php
/*
* Super Admin Settings — Payments, SMS, API, Tax
*/
use App\Models\SystemModel;
use App\Models\UsersModel;

$SystemModel = new SystemModel();
$UsersModel  = new UsersModel();

$session  = \Config\Services::session();
$usession = $session->get('sup_username');
$user     = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
$settings = $SystemModel->where('setting_id', 1)->first();
$tab      = $tab ?? 'payments';
?>

<div class="row">
<div class="col-lg-12">
<div class="card">
  <div class="card-header">
    <h5>Super Admin Settings</h5>
    <ul class="nav nav-tabs card-header-tabs" id="superSettingsTabs">
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'payments' ? 'active' : '' ?>" data-toggle="tab" href="#tab-stripe">Stripe</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'mtn' ? 'active' : '' ?>" data-toggle="tab" href="#tab-mtn">MTN MoMo</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'airtel' ? 'active' : '' ?>" data-toggle="tab" href="#tab-airtel">Airtel Money</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'sms' ? 'active' : '' ?>" data-toggle="tab" href="#tab-sms">SMS</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'api' ? 'active' : '' ?>" data-toggle="tab" href="#tab-api">API &amp; Security</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'tax' ? 'active' : '' ?>" data-toggle="tab" href="#tab-tax">Tax (PAYE/NSSF)</a>
      </li>
    </ul>
  </div>
  <div class="card-body">
    <div class="tab-content">

      <!-- ─── Stripe ──────────────────────────────── -->
      <div class="tab-pane fade <?= $tab === 'payments' ? 'show active' : '' ?>" id="tab-stripe">
        <form id="form-stripe-settings" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="setting_id" value="1">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Stripe Secret Key</label>
                <input type="password" class="form-control" name="stripe_secret_key" value="<?= esc($settings['stripe_secret_key'] ?? '') ?>" placeholder="sk_live_...">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Stripe Publishable Key</label>
                <input type="password" class="form-control" name="stripe_publishable_key" value="<?= esc($settings['stripe_publishable_key'] ?? '') ?>" placeholder="pk_live_...">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Stripe Webhook Secret</label>
                <input type="password" class="form-control" name="stripe_webhook_secret" value="<?= esc($settings['stripe_webhook_secret'] ?? '') ?>" placeholder="whsec_...">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Mode</label>
                <select class="form-control" name="stripe_mode">
                  <option value="test" <?= ($settings['stripe_mode'] ?? '') === 'test' ? 'selected' : '' ?>>Test</option>
                  <option value="live" <?= ($settings['stripe_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Enable Stripe</label>
                <select class="form-control" name="stripe_active">
                  <option value="0" <?= ($settings['stripe_active'] ?? 0) == 0 ? 'selected' : '' ?>>Disabled</option>
                  <option value="1" <?= ($settings['stripe_active'] ?? 0) == 1 ? 'selected' : '' ?>>Enabled</option>
                </select>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Save Stripe Settings</button>
        </form>
      </div>

      <!-- ─── MTN MoMo ────────────────────────────── -->
      <div class="tab-pane fade <?= $tab === 'mtn' ? 'show active' : '' ?>" id="tab-mtn">
        <form id="form-mtn-settings" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="setting_id" value="1">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Subscription Key</label>
                <input type="password" class="form-control" name="mtn_subscription_key" value="<?= esc($settings['mtn_subscription_key'] ?? '') ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>API User ID</label>
                <input type="text" class="form-control" name="mtn_api_user" value="<?= esc($settings['mtn_api_user'] ?? '') ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>API Key</label>
                <input type="password" class="form-control" name="mtn_api_key" value="<?= esc($settings['mtn_api_key'] ?? '') ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Environment</label>
                <select class="form-control" name="mtn_environment">
                  <option value="sandbox" <?= ($settings['mtn_environment'] ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                  <option value="production" <?= ($settings['mtn_environment'] ?? '') === 'production' ? 'selected' : '' ?>>Production</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Enable MTN</label>
                <select class="form-control" name="mtn_active">
                  <option value="0" <?= ($settings['mtn_active'] ?? 0) == 0 ? 'selected' : '' ?>>Disabled</option>
                  <option value="1" <?= ($settings['mtn_active'] ?? 0) == 1 ? 'selected' : '' ?>>Enabled</option>
                </select>
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-group">
                <label>Callback URL</label>
                <input type="text" class="form-control" value="<?= site_url('api/v1/webhooks/mtn') ?>" readonly>
                <small class="text-muted">Copy this URL into your MTN developer dashboard.</small>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Save MTN Settings</button>
        </form>
      </div>

      <!-- ─── Airtel ──────────────────────────────── -->
      <div class="tab-pane fade <?= $tab === 'airtel' ? 'show active' : '' ?>" id="tab-airtel">
        <form id="form-airtel-settings" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="setting_id" value="1">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Client ID</label>
                <input type="text" class="form-control" name="airtel_client_id" value="<?= esc($settings['airtel_client_id'] ?? '') ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Client Secret</label>
                <input type="password" class="form-control" name="airtel_client_secret" value="<?= esc($settings['airtel_client_secret'] ?? '') ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Environment</label>
                <select class="form-control" name="airtel_environment">
                  <option value="sandbox" <?= ($settings['airtel_environment'] ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                  <option value="production" <?= ($settings['airtel_environment'] ?? '') === 'production' ? 'selected' : '' ?>>Production</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Enable Airtel</label>
                <select class="form-control" name="airtel_active">
                  <option value="0" <?= ($settings['airtel_active'] ?? 0) == 0 ? 'selected' : '' ?>>Disabled</option>
                  <option value="1" <?= ($settings['airtel_active'] ?? 0) == 1 ? 'selected' : '' ?>>Enabled</option>
                </select>
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-group">
                <label>Callback URL</label>
                <input type="text" class="form-control" value="<?= site_url('api/v1/webhooks/airtel') ?>" readonly>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Save Airtel Settings</button>
        </form>
      </div>

      <!-- ─── SMS ─────────────────────────────────── -->
      <div class="tab-pane fade <?= $tab === 'sms' ? 'show active' : '' ?>" id="tab-sms">
        <form id="form-sms-settings" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="setting_id" value="1">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Provider</label>
                <select class="form-control" name="sms_provider">
                  <option value="africastalking" <?= ($settings['sms_provider'] ?? '') === 'africastalking' ? 'selected' : '' ?>>Africa's Talking</option>
                  <option value="vonage" <?= ($settings['sms_provider'] ?? '') === 'vonage' ? 'selected' : '' ?>>Vonage (Nexmo)</option>
                  <option value="twilio" <?= ($settings['sms_provider'] ?? '') === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>API Username / SID</label>
                <input type="text" class="form-control" name="sms_username" value="<?= esc($settings['sms_username'] ?? '') ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>API Key / Auth Token</label>
                <input type="password" class="form-control" name="sms_api_key" value="<?= esc($settings['sms_api_key'] ?? '') ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Sender ID <small class="text-muted">(max 11 chars)</small></label>
                <input type="text" class="form-control" name="sms_sender_id" maxlength="11" value="<?= esc($settings['sms_sender_id'] ?? 'RooibokHR') ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>SMS Master Toggle</label>
                <select class="form-control" name="sms_active">
                  <option value="0" <?= ($settings['sms_active'] ?? 0) == 0 ? 'selected' : '' ?>>Off</option>
                  <option value="1" <?= ($settings['sms_active'] ?? 0) == 1 ? 'selected' : '' ?>>On</option>
                </select>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Save SMS Settings</button>
        </form>
      </div>

      <!-- ─── API & Security ──────────────────────── -->
      <div class="tab-pane fade <?= $tab === 'api' ? 'show active' : '' ?>" id="tab-api">
        <form id="form-api-settings" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="setting_id" value="1">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>JWT Secret</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="jwt_secret" value="<?= esc($settings['jwt_secret'] ?? '') ?>">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary" onclick="this.previousElementSibling.value = Array.from(crypto.getRandomValues(new Uint8Array(32))).map(b=>b.toString(16).padStart(2,'0')).join('')">Generate</button>
                  </div>
                </div>
                <small class="text-danger">Generating a new secret logs out all API users.</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Token Expiry (hours)</label>
                <input type="number" class="form-control" name="jwt_ttl_hours" value="<?= esc($settings['jwt_ttl_hours'] ?? 24) ?>" min="1" max="720">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>API Master Toggle</label>
                <select class="form-control" name="api_active">
                  <option value="0" <?= ($settings['api_active'] ?? 1) == 0 ? 'selected' : '' ?>>Off</option>
                  <option value="1" <?= ($settings['api_active'] ?? 1) == 1 ? 'selected' : '' ?>>On</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Rate Limit (req/min)</label>
                <input type="number" class="form-control" name="api_rate_limit" value="<?= esc($settings['api_rate_limit'] ?? 60) ?>" min="10" max="1000">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Geofence Radius (m)</label>
                <input type="number" class="form-control" name="default_geofence_radius" value="<?= esc($settings['default_geofence_radius'] ?? 300) ?>" min="50" max="5000">
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Save API Settings</button>
        </form>
      </div>

      <!-- ─── Tax (PAYE / NSSF) ───────────────────── -->
      <div class="tab-pane fade <?= $tab === 'tax' ? 'show active' : '' ?>" id="tab-tax">
        <form id="form-tax-settings" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="setting_id" value="1">
          <h6>NSSF Rates</h6>
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Employee Rate (%)</label>
                <input type="number" step="0.01" class="form-control" name="nssf_employee_rate" value="<?= esc($settings['nssf_employee_rate'] ?? '5.00') ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Employer Rate (%)</label>
                <input type="number" step="0.01" class="form-control" name="nssf_employer_rate" value="<?= esc($settings['nssf_employer_rate'] ?? '10.00') ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>NSSF Enabled</label>
                <select class="form-control" name="nssf_enabled">
                  <option value="0" <?= ($settings['nssf_enabled'] ?? 1) == 0 ? 'selected' : '' ?>>Disabled</option>
                  <option value="1" <?= ($settings['nssf_enabled'] ?? 1) == 1 ? 'selected' : '' ?>>Enabled</option>
                </select>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Save Tax Settings</button>
        </form>
        <hr>
        <h6>PAYE Tax Bands</h6>
        <p class="text-muted small">These rates apply to all companies unless overridden at company level. Manage via Super Admin &rarr; Tax Configuration.</p>
        <div id="paye-bands-table">
          <!-- PAYE bands loaded dynamically in Phase 5.3 -->
          <p class="text-muted">PAYE band management will be available in a future update.</p>
        </div>
      </div>

    </div>
  </div>
</div>
</div>
</div>

<script>
document.querySelectorAll('[id^="form-"]').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('type', 'edit_record');
        fetch(site_url + '/erp/settings/save_super_settings/', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.result) toastr.success(data.result);
            if (data.error) toastr.error(data.error);
        });
    });
});
</script>
