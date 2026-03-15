<?php
$xin_system = $xin_system ?? [];
$xin_com = $xin_com ?? [];
$current_header = $xin_com['header_background'] ?? '';
$current_login = $xin_com['login_page'] ?? '2';
$current_login_text = $xin_com['login_page_text'] ?? '';
$current_auth_bg = $xin_system['auth_background'] ?? '';
$current_primary = $xin_com['theme_primary'] ?? '#7267EF';
$current_secondary = $xin_com['theme_secondary'] ?? '#6c757d';
$current_success = $xin_com['theme_success'] ?? '#17C666';
$current_sidebar = $xin_com['theme_sidebar'] ?? 'light';

// Preset palettes
$palettes = [
    ['name'=>'Default Purple', 'primary'=>'#7267EF','secondary'=>'#6c757d','success'=>'#17C666','header'=>''],
    ['name'=>'Ocean Blue',     'primary'=>'#4680ff','secondary'=>'#5b6b79','success'=>'#2ca87f','header'=>'bg-dark'],
    ['name'=>'Forest Green',   'primary'=>'#28a745','secondary'=>'#6c757d','success'=>'#20c997','header'=>'bg-dark'],
    ['name'=>'Sunset Orange',  'primary'=>'#ff6b35','secondary'=>'#495057','success'=>'#51cf66','header'=>'bg-dark'],
    ['name'=>'Royal Indigo',   'primary'=>'#6610f2','secondary'=>'#868e96','success'=>'#38d9a9','header'=>'bg-dark'],
    ['name'=>'Corporate Blue', 'primary'=>'#1565c0','secondary'=>'#546e7a','success'=>'#00c853','header'=>'bg-primary'],
    ['name'=>'Minimal Dark',   'primary'=>'#e0e0e0','secondary'=>'#9e9e9e','success'=>'#69f0ae','header'=>'bg-dark'],
    ['name'=>'Uganda Gold',    'primary'=>'#d4a017','secondary'=>'#5d4037','success'=>'#43a047','header'=>'bg-dark'],
];

$auth_backgrounds = [];
$auth_dir = FCPATH . 'assets/images/auth/';
if (is_dir($auth_dir)) {
    foreach (glob($auth_dir . '*.jpg') as $file) {
        $auth_backgrounds[] = basename($file, '.jpg');
    }
}
?>

<?= form_open('erp/settings/save_theme', ['id' => 'theme-form', 'autocomplete' => 'off']); ?>

<!-- Color Palette -->
<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="feather icon-droplet mr-2"></i>Color Palette</h5></div>
  <div class="card-body">
    <p class="text-muted mb-3">Choose a preset palette or customize individual colors. Changes preview live.</p>

    <h6 class="mb-3">Preset Palettes</h6>
    <div class="row mb-4">
      <?php foreach ($palettes as $i => $pal): ?>
      <div class="col-lg-3 col-md-4 col-6 mb-3">
        <div class="sa-palette-card" data-primary="<?= $pal['primary']; ?>" data-secondary="<?= $pal['secondary']; ?>" data-success="<?= $pal['success']; ?>" data-header="<?= $pal['header']; ?>">
          <div class="sa-palette-swatches">
            <div class="sa-swatch sa-swatch-lg" data-c="<?= $pal['primary']; ?>"></div>
            <div class="sa-swatch" data-c="<?= $pal['secondary']; ?>"></div>
            <div class="sa-swatch" data-c="<?= $pal['success']; ?>"></div>
          </div>
          <div class="sa-palette-name"><?= $pal['name']; ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <h6 class="mb-3">Custom Colors</h6>
    <div class="row">
      <div class="col-md-3 col-6">
        <div class="form-group">
          <label>Primary Color</label>
          <div class="d-flex align-items-center">
            <input type="color" name="theme_primary" id="cp_primary" value="<?= $current_primary; ?>" class="sa-color-input mr-2">
            <input type="text" class="form-control form-control-sm sa-color-text" data-target="cp_primary" value="<?= $current_primary; ?>">
          </div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="form-group">
          <label>Secondary Color</label>
          <div class="d-flex align-items-center">
            <input type="color" name="theme_secondary" id="cp_secondary" value="<?= $current_secondary; ?>" class="sa-color-input mr-2">
            <input type="text" class="form-control form-control-sm sa-color-text" data-target="cp_secondary" value="<?= $current_secondary; ?>">
          </div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="form-group">
          <label>Accent / Success</label>
          <div class="d-flex align-items-center">
            <input type="color" name="theme_success" id="cp_success" value="<?= $current_success; ?>" class="sa-color-input mr-2">
            <input type="text" class="form-control form-control-sm sa-color-text" data-target="cp_success" value="<?= $current_success; ?>">
          </div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="form-group">
          <label>Header Style</label>
          <select name="header_background" class="form-control form-control-sm" id="sel_header">
            <option value="" <?= $current_header=='' ? 'selected' : ''; ?>>Light (default)</option>
            <option value="bg-dark" <?= $current_header=='bg-dark' ? 'selected' : ''; ?>>Dark</option>
            <option value="bg-primary" <?= $current_header=='bg-primary' ? 'selected' : ''; ?>>Match Primary</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Live preview bar -->
    <div class="sa-live-preview mt-3">
      <div class="sa-preview-header" id="pv_header">
        <span>Header Preview</span>
      </div>
      <div class="sa-preview-body">
        <div class="sa-preview-btn sa-pv-primary" id="pv_primary">Primary Button</div>
        <div class="sa-preview-btn sa-pv-secondary" id="pv_secondary">Secondary</div>
        <div class="sa-preview-btn sa-pv-success" id="pv_success">Accent</div>
        <div class="sa-preview-badge" id="pv_badge">Badge</div>
        <div class="sa-preview-link" id="pv_link">Link text</div>
      </div>
    </div>
  </div>
</div>

<!-- Sidebar Style -->
<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="feather icon-sidebar mr-2"></i>Sidebar Style</h5></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-4 col-6 mb-3">
        <input type="radio" name="theme_sidebar" id="sb_light" value="light" <?= $current_sidebar=='light' ? 'checked' : ''; ?> class="d-none">
        <label for="sb_light" class="sa-theme-card <?= $current_sidebar=='light' ? 'sa-selected' : ''; ?>">
          <div class="sa-sb-preview sa-sb-light"><div class="sa-sb-item"></div><div class="sa-sb-item"></div><div class="sa-sb-item"></div></div>
          <div class="sa-theme-card-label">Light Sidebar</div>
        </label>
      </div>
      <div class="col-md-4 col-6 mb-3">
        <input type="radio" name="theme_sidebar" id="sb_dark" value="dark" <?= $current_sidebar=='dark' ? 'checked' : ''; ?> class="d-none">
        <label for="sb_dark" class="sa-theme-card <?= $current_sidebar=='dark' ? 'sa-selected' : ''; ?>">
          <div class="sa-sb-preview sa-sb-dark"><div class="sa-sb-item"></div><div class="sa-sb-item"></div><div class="sa-sb-item"></div></div>
          <div class="sa-theme-card-label">Dark Sidebar</div>
        </label>
      </div>
    </div>
  </div>
</div>

<!-- Login Page Style -->
<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="feather icon-lock mr-2"></i>Login Page</h5></div>
  <div class="card-body">
    <div class="row">
      <?php
      $login_opts = [
          '1' => ['label' => 'Classic — centered card',       'cls' => 'sa-lp-classic'],
          '2' => ['label' => 'Split — image left, form right', 'cls' => 'sa-lp-split'],
          '3' => ['label' => 'Full background — overlay form', 'cls' => 'sa-lp-full'],
      ];
      foreach ($login_opts as $num => $lo): ?>
      <div class="col-md-4 mb-3">
        <input type="radio" name="login_page" id="lp_<?= $num; ?>" value="<?= $num; ?>" <?= $current_login == $num ? 'checked' : ''; ?> class="d-none">
        <label for="lp_<?= $num; ?>" class="sa-theme-card <?= $current_login == $num ? 'sa-selected' : ''; ?>">
          <div class="sa-lp-preview <?= $lo['cls']; ?>"><div class="sa-lp-a"></div><div class="sa-lp-b"></div></div>
          <div class="sa-theme-card-label">Page <?= $num; ?> — <?= $lo['label']; ?></div>
        </label>
      </div>
      <?php endforeach; ?>
    </div>
    <a href="<?= site_url('erp/login'); ?>" target="_blank" class="btn btn-outline-primary btn-sm mb-3"><i class="feather icon-external-link mr-1"></i>Preview Login Page</a>
    <div class="form-group">
      <label>Login Page Text <small class="text-muted">(version 2 only)</small></label>
      <textarea class="form-control" name="login_page_text" rows="2"><?= esc($current_login_text); ?></textarea>
    </div>
  </div>
</div>

<!-- Login Background -->
<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="feather icon-image mr-2"></i>Login Background</h5></div>
  <div class="card-body">
    <div class="row">
      <?php foreach ($auth_backgrounds as $bg): ?>
      <div class="col-xl-2 col-lg-3 col-md-4 col-6 mb-3">
        <input type="radio" name="auth_background" id="bg_<?= $bg; ?>" value="<?= $bg; ?>" <?= $current_auth_bg == $bg ? 'checked' : ''; ?> class="d-none">
        <label for="bg_<?= $bg; ?>" class="sa-theme-card sa-bg-card <?= $current_auth_bg == $bg ? 'sa-selected' : ''; ?>">
          <img src="<?= base_url('public/assets/images/auth/' . $bg . '.jpg'); ?>" alt="" class="sa-bg-img">
        </label>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="text-right mb-4">
  <button type="submit" class="btn btn-primary"><i class="feather icon-save mr-1"></i>Save Theme Settings</button>
</div>
<?= form_close(); ?>

<script>
$(document).ready(function(){
  var $header = $('header.pc-header');
  var $sidebar = $('nav.pc-sidebar');
  var origHeaderClass = $header.attr('class');
  var root = document.documentElement;

  function applyColors(p, s, a){
    root.style.setProperty('--primary', p);
    root.style.setProperty('--secondary', s);
    root.style.setProperty('--success', a);
    // Update preview bar
    $('#pv_primary').css('background', p);
    $('#pv_secondary').css('background', s);
    $('#pv_success').css('background', a);
    $('#pv_badge').css('background', p);
    $('#pv_link').css('color', p);
  }

  // Init swatches and preview
  $('.sa-swatch').each(function(){ $(this).css('background', $(this).data('c')); });
  applyColors($('#cp_primary').val(), $('#cp_secondary').val(), $('#cp_success').val());

  // Preset palette click
  $('.sa-palette-card').click(function(){
    var p=$(this).data('primary'), s=$(this).data('secondary'), a=$(this).data('success'), h=$(this).data('header');
    $('#cp_primary').val(p); $('input[data-target="cp_primary"]').val(p);
    $('#cp_secondary').val(s); $('input[data-target="cp_secondary"]').val(s);
    $('#cp_success').val(a); $('input[data-target="cp_success"]').val(a);
    $('#sel_header').val(h);
    applyColors(p, s, a);
    // Apply header
    $header.removeClass('bg-dark bg-primary bg-success bg-danger bg-info bg-warning');
    if(h) $header.addClass(h);
    if(h==='bg-primary') $header.css('background-color', p);
    // Highlight
    $('.sa-palette-card').removeClass('sa-palette-active');
    $(this).addClass('sa-palette-active');
  });

  // Color picker live update
  $('.sa-color-input').on('input', function(){
    var val = $(this).val();
    $(this).siblings('.sa-color-text').val(val);
    applyColors($('#cp_primary').val(), $('#cp_secondary').val(), $('#cp_success').val());
    if($('#sel_header').val()==='bg-primary') $header.css('background-color', $('#cp_primary').val());
  });
  $('.sa-color-text').on('input', function(){
    var target = $(this).data('target');
    $('#'+target).val($(this).val());
    applyColors($('#cp_primary').val(), $('#cp_secondary').val(), $('#cp_success').val());
  });

  // Header select
  $('#sel_header').change(function(){
    $header.removeClass('bg-dark bg-primary bg-success bg-danger bg-info bg-warning').css('background-color','');
    var v=$(this).val();
    if(v) $header.addClass(v);
    if(v==='bg-primary') $header.css('background-color', $('#cp_primary').val());
  });

  // Sidebar toggle
  $('input[name="theme_sidebar"]').change(function(){
    var v=$(this).val();
    $sidebar.removeClass('light-sidebar dark-sidebar').addClass(v+'-sidebar');
  });

  // Radio card selection
  $(document).on('change','input[type="radio"]',function(){
    var name=$(this).attr('name');
    $('input[name="'+name+'"]').next('label').removeClass('sa-selected');
    $(this).next('label').addClass('sa-selected');
  });

  // Save
  $('#theme-form').submit(function(e){
    e.preventDefault();
    $.ajax({
      url:$(this).attr('action'), type:'POST', data:$(this).serialize(), dataType:'json',
      success:function(d){
        if(d.error&&d.error!==''){toastr.error(d.error);}
        else{toastr.success(d.result||'Theme saved');}
        if(d.csrf_hash)$('input[name="csrf_token"]').val(d.csrf_hash);
        try{Ladda.stopAll();}catch(ex){}
      },
      error:function(){toastr.error('Save failed');try{Ladda.stopAll();}catch(ex){}}
    });
  });
});
</script>
