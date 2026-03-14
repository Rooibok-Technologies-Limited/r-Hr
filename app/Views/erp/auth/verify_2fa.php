<?php
use App\Models\SystemModel;
use App\Models\CompanysettingsModel;
$SystemModel = new SystemModel();
$CompanysettingsModel = new CompanysettingsModel();
$xin_system = $SystemModel->where('setting_id', 1)->first();
$xin_com_system = $CompanysettingsModel->where('setting_id', 1)->first();
$favicon = base_url().'/public/uploads/logo/favicon/'.$xin_system['favicon'];
$session = \Config\Services::session();
?>
<?= doctype();?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?= $title; ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<link rel="icon" type="image/x-icon" href="<?= $favicon;?>">
<link rel="stylesheet" href="<?= base_url('public/assets/fonts/font-awsome-pro/css/pro.min.css');?>">
<link rel="stylesheet" href="<?= base_url('public/assets/fonts/feather.css');?>">
<link rel="stylesheet" href="<?= base_url('public/assets/fonts/fontawesome.css');?>">
<link rel="stylesheet" href="<?= base_url('public/assets/css/style.css');?>">
<link rel="stylesheet" href="<?= base_url('public/assets/css/customizer.css');?>">
<link rel="stylesheet" href="<?= base_url('public/assets/plugins/toastr/toastr.css');?>">
<style>
.totp-input {
	font-size: 2rem;
	text-align: center;
	letter-spacing: 0.5rem;
	max-width: 280px;
	margin: 0 auto;
	padding: 12px;
	font-family: 'Courier New', monospace;
}
.backup-section {
	display: none;
	margin-top: 15px;
}
.btn-link-toggle {
	cursor: pointer;
	color: #4680ff;
	text-decoration: underline;
	font-size: 0.9rem;
}
</style>
</head>
<body>
<div class="auth-wrapper auth-v3">
  <div class="auth-content">
    <div class="card" style="max-width: 450px; margin: 80px auto;">
      <div class="card-body text-center">
        <div class="mb-4">
          <i class="fas fa-shield-alt" style="font-size: 3rem; color: #4680ff;"></i>
        </div>
        <h4 class="mb-3 f-w-600">Two-Factor Authentication</h4>
        <p class="text-muted mb-4">Enter the 6-digit code from your authenticator app to continue.</p>

        <!-- TOTP Code Form -->
        <div id="totp-section">
          <?php $attributes = array('class' => 'form-2fa', 'name' => 'form-2fa', 'id' => 'form-2fa', 'autocomplete' => 'off');?>
          <?= form_open('erp/auth/verify-2fa', $attributes);?>
          <input type="hidden" name="is_backup_code" id="is_backup_code" value="0">
          <div class="form-group">
            <input type="text" class="form-control totp-input" id="totp_code" name="totp_code"
                   maxlength="6" pattern="[0-9]{6}" placeholder="000000" autofocus
                   inputmode="numeric" autocomplete="one-time-code">
          </div>
          <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block ladda-button" data-style="expand-right">
              <span class="ladda-label">Verify</span>
            </button>
          </div>
          <?= form_close();?>
          <div class="mt-3">
            <span class="btn-link-toggle" id="show-backup">Use backup code instead</span>
          </div>
        </div>

        <!-- Backup Code Form -->
        <div class="backup-section" id="backup-section">
          <?php $attributes2 = array('class' => 'form-2fa-backup', 'name' => 'form-2fa-backup', 'id' => 'form-2fa-backup', 'autocomplete' => 'off');?>
          <?= form_open('erp/auth/verify-2fa', $attributes2);?>
          <input type="hidden" name="is_backup_code" value="1">
          <div class="form-group">
            <label class="text-muted">Enter one of your backup codes</label>
            <input type="text" class="form-control" id="backup_code" name="totp_code"
                   maxlength="8" placeholder="Backup code" style="text-align:center; font-size:1.2rem; letter-spacing:0.2rem;">
          </div>
          <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block ladda-button" data-style="expand-right">
              <span class="ladda-label">Verify Backup Code</span>
            </button>
          </div>
          <?= form_close();?>
          <div class="mt-3">
            <span class="btn-link-toggle" id="show-totp">Use authenticator code instead</span>
          </div>
        </div>

        <div class="mt-4">
          <a href="<?= site_url('erp/');?>" class="text-muted">Back to Login</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= base_url('public/assets/js/vendor-all.min.js');?>"></script>
<script src="<?= base_url('public/assets/js/plugins/bootstrap.min.js');?>"></script>
<script src="<?= base_url('public/assets/js/plugins/feather.min.js');?>"></script>
<script src="<?= base_url('public/assets/js/pcoded.min.js');?>"></script>
<script src="<?= base_url();?>/public/assets/plugins/toastr/toastr.js"></script>
<script src="<?= base_url();?>/public/assets/plugins/sweetalert2/sweetalert2@10.js"></script>
<link rel="stylesheet" href="<?= base_url();?>/public/assets/plugins/ladda/ladda.css">
<script src="<?= base_url();?>/public/assets/plugins/spin/spin.js"></script>
<script src="<?= base_url();?>/public/assets/plugins/ladda/ladda.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	Ladda.bind('button[type=submit]');
	toastr.options.closeButton = <?= $xin_system['notification_close_btn'];?>;
	toastr.options.progressBar = <?= $xin_system['notification_bar'];?>;
	toastr.options.timeOut = 3000;
	toastr.options.preventDuplicates = true;
	toastr.options.positionClass = "<?= $xin_system['notification_position'];?>";

	var desk_url = '<?= site_url('erp/desk'); ?>';

	// Toggle between TOTP and backup code sections
	$('#show-backup').click(function(){
		$('#totp-section').hide();
		$('#backup-section').show();
	});
	$('#show-totp').click(function(){
		$('#backup-section').hide();
		$('#totp-section').show();
	});

	// Handle both forms
	$('#form-2fa, #form-2fa-backup').submit(function(e){
		e.preventDefault();
		var obj = $(this);
		$.ajax({
			type: "POST",
			url: e.target.action,
			data: obj.serialize()+"&is_ajax=1",
			cache: false,
			success: function(JSON){
				if(JSON.error != ''){
					toastr.error(JSON.error);
					$('input[name="csrf_token"]').val(JSON.csrf_hash);
					Ladda.stopAll();
				} else {
					toastr.clear();
					$('input[name="csrf_token"]').val(JSON.csrf_hash);
					Ladda.stopAll();
					Swal.fire({
						title: JSON.result,
						html: 'Redirecting to dashboard...',
						timer: 2000,
						icon: "success",
						showConfirmButton: false,
						timerProgressBar: true,
						onBeforeOpen: function(){ Swal.showLoading(); },
						onClose: function(){ window.location = desk_url; }
					});
				}
			}
		});
	});
});
</script>
</body>
</html>