<?php
/**
 * 2FA Setup View - Included within the profile page
 * Shows QR code, secret key, verification input, and backup codes
 */
$session = \Config\Services::session();
use App\Models\UsersModel;
$UsersModel = new UsersModel();
$usession = $session->get('sup_username');
$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
$is_2fa_enabled = !empty($user_info['totp_enabled']) && $user_info['totp_enabled'] == 1;
$is_super_user = !empty($user_info['user_type']) && $user_info['user_type'] === 'super_user';
?>
<div class="card">
  <div class="card-header">
    <h5><i class="fas fa-shield-alt mr-2"></i> Two-Factor Authentication (2FA)</h5>
  </div>
  <div class="card-body">

    <?php if ($is_2fa_enabled): ?>
    <!-- 2FA is currently enabled -->
    <div class="text-center">
      <div class="mb-3">
        <span class="badge badge-success" style="font-size: 1rem; padding: 8px 16px;">
          <i class="fas fa-check-circle mr-1"></i> 2FA is enabled
        </span>
      </div>
      <p class="text-muted">Your account is protected with two-factor authentication.</p>
      <?php if (!$is_super_user): ?>
      <button type="button" class="btn btn-danger" id="btn-disable-2fa">
        <i class="fas fa-times-circle mr-1"></i> Disable 2FA
      </button>
      <?php else: ?>
      <p class="text-warning"><i class="fas fa-info-circle mr-1"></i> 2FA is mandatory for Super Admin accounts and cannot be disabled.</p>
      <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- 2FA is not enabled - show setup -->
    <div id="2fa-step-1">
      <p class="text-muted">Add an extra layer of security to your account by enabling two-factor authentication.</p>
      <div class="text-center">
        <button type="button" class="btn btn-primary" id="btn-setup-2fa">
          <i class="fas fa-shield-alt mr-1"></i> Enable Two-Factor Authentication
        </button>
      </div>
    </div>

    <!-- Step 2: QR Code + verification -->
    <div id="2fa-step-2" style="display:none;">
      <div class="row">
        <div class="col-md-6 text-center">
          <h6>1. Scan this QR code with your authenticator app</h6>
          <p class="text-muted mb-2">(Google Authenticator, Authy, etc.)</p>
          <div class="mb-3">
            <img id="2fa-qr-image" src="" alt="QR Code" style="max-width: 200px; height: 200px; border: 1px solid #ddd; padding: 8px;">
          </div>
          <div class="mb-3">
            <label class="text-muted">Or enter this key manually:</label>
            <div class="input-group" style="max-width: 300px; margin: 0 auto;">
              <input type="text" class="form-control text-center" id="2fa-secret-text" readonly style="font-family: monospace; font-size: 1.1rem; letter-spacing: 2px;">
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="btn-copy-secret" title="Copy to clipboard">
                  <i class="fas fa-copy"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <h6>2. Enter the 6-digit code to verify</h6>
          <p class="text-muted">Enter the code shown in your authenticator app to complete the setup.</p>
          <?php $attributes = array('id' => 'form-verify-2fa-setup', 'autocomplete' => 'off');?>
          <?= form_open('erp/profile/verify-2fa-setup', $attributes);?>
          <div class="form-group">
            <input type="text" class="form-control" name="totp_code" id="setup_totp_code"
                   maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                   style="font-size: 1.5rem; text-align: center; letter-spacing: 0.5rem; max-width: 220px;"
                   inputmode="numeric" autocomplete="one-time-code">
          </div>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check mr-1"></i> Verify & Enable 2FA
          </button>
          <button type="button" class="btn btn-light ml-2" id="btn-cancel-2fa">Cancel</button>
          <?= form_close();?>
        </div>
      </div>
    </div>

    <!-- Step 3: Show backup codes after successful setup -->
    <div id="2fa-step-3" style="display:none;">
      <div class="text-center mb-3">
        <span class="badge badge-success" style="font-size: 1rem; padding: 8px 16px;">
          <i class="fas fa-check-circle mr-1"></i> 2FA has been enabled successfully!
        </span>
      </div>
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong>Save your backup codes!</strong> These codes can be used to access your account if you lose your authenticator device.
        Each code can only be used once. Store them in a safe place.
      </div>
      <div class="card bg-light">
        <div class="card-body">
          <div class="row" id="backup-codes-list">
            <!-- Backup codes will be inserted here -->
          </div>
        </div>
      </div>
      <div class="text-center mt-3">
        <button type="button" class="btn btn-outline-primary mr-2" id="btn-copy-codes">
          <i class="fas fa-copy mr-1"></i> Copy Codes
        </button>
        <button type="button" class="btn btn-outline-secondary" id="btn-download-codes">
          <i class="fas fa-download mr-1"></i> Download Codes
        </button>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<script type="text/javascript">
$(document).ready(function(){

	// Setup 2FA - Step 1: Generate secret & show QR
	$('#btn-setup-2fa').click(function(){
		var btn = $(this);
		btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Generating...');
		$.ajax({
			type: "POST",
			url: '<?= site_url("erp/profile/setup-2fa"); ?>',
			data: '<?= csrf_token(); ?>=<?= csrf_hash(); ?>',
			cache: false,
			success: function(res){
				if(res.error && res.error !== ''){
					toastr.error(res.error);
					btn.prop('disabled', false).html('<i class="fas fa-shield-alt mr-1"></i> Enable Two-Factor Authentication');
				} else {
					// Show QR code using Google Charts API
					var qrImageUrl = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' + encodeURIComponent(res.qr_url);
					$('#2fa-qr-image').attr('src', qrImageUrl);
					$('#2fa-secret-text').val(res.secret);
					$('#2fa-step-1').hide();
					$('#2fa-step-2').show();
				}
			}
		});
	});

	// Cancel setup
	$('#btn-cancel-2fa').click(function(){
		$('#2fa-step-2').hide();
		$('#2fa-step-1').show();
		$('#btn-setup-2fa').prop('disabled', false).html('<i class="fas fa-shield-alt mr-1"></i> Enable Two-Factor Authentication');
	});

	// Copy secret key
	$('#btn-copy-secret').click(function(){
		var secret = $('#2fa-secret-text').val();
		navigator.clipboard.writeText(secret).then(function(){
			toastr.success('Secret key copied to clipboard');
		});
	});

	// Verify 2FA setup - Step 2
	$('#form-verify-2fa-setup').submit(function(e){
		e.preventDefault();
		var obj = $(this);
		$.ajax({
			type: "POST",
			url: e.target.action,
			data: obj.serialize(),
			cache: false,
			success: function(res){
				if(res.error && res.error !== ''){
					toastr.error(res.error);
				} else {
					toastr.success(res.result);
					$('#2fa-step-2').hide();
					// Show backup codes
					var codesHtml = '';
					if(res.backup_codes && res.backup_codes.length > 0){
						window._backupCodes = res.backup_codes;
						for(var i = 0; i < res.backup_codes.length; i++){
							codesHtml += '<div class="col-md-6 col-6 mb-2"><code style="font-size:1.1rem; background:#fff; padding:4px 12px; border:1px solid #ddd; display:inline-block; width:100%; text-align:center;">' + res.backup_codes[i] + '</code></div>';
						}
					}
					$('#backup-codes-list').html(codesHtml);
					$('#2fa-step-3').show();
				}
			}
		});
	});

	// Copy backup codes
	$('#btn-copy-codes').click(function(){
		if(window._backupCodes){
			var text = 'Rooibok HR - 2FA Backup Codes\n' + '='.repeat(30) + '\n\n';
			window._backupCodes.forEach(function(code, i){
				text += (i+1) + '. ' + code + '\n';
			});
			text += '\nKeep these codes safe. Each code can only be used once.';
			navigator.clipboard.writeText(text).then(function(){
				toastr.success('Backup codes copied to clipboard');
			});
		}
	});

	// Download backup codes
	$('#btn-download-codes').click(function(){
		if(window._backupCodes){
			var text = 'Rooibok HR - 2FA Backup Codes\n' + '='.repeat(30) + '\n\n';
			window._backupCodes.forEach(function(code, i){
				text += (i+1) + '. ' + code + '\n';
			});
			text += '\nKeep these codes safe. Each code can only be used once.\nGenerated: ' + new Date().toLocaleString();
			var blob = new Blob([text], {type: 'text/plain'});
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = '2fa-backup-codes.txt';
			a.click();
			URL.revokeObjectURL(url);
		}
	});

	// Disable 2FA
	$('#btn-disable-2fa').click(function(){
		Swal.fire({
			title: 'Disable Two-Factor Authentication?',
			text: 'This will remove the extra layer of security from your account.',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#dc3545',
			confirmButtonText: 'Yes, disable it',
			cancelButtonText: 'Cancel'
		}).then(function(result){
			if(result.isConfirmed){
				$.ajax({
					type: "POST",
					url: '<?= site_url("erp/profile/disable-2fa"); ?>',
					data: '<?= csrf_token(); ?>=<?= csrf_hash(); ?>',
					cache: false,
					success: function(res){
						if(res.error && res.error !== ''){
							toastr.error(res.error);
						} else {
							toastr.success(res.result);
							setTimeout(function(){ location.reload(); }, 1500);
						}
					}
				});
			}
		});
	});
});
</script>