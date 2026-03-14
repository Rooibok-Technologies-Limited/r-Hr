<?php
/**
 * Phase 10.7 — Archive Portal Dashboard
 * Tier 1 (live DB), Tier 2 (archive DB), Tier 3 (vault) stats
 */
?>
<div class="row">
  <!-- Tier 1 — Live DB -->
  <div class="col-12">
    <h5 class="mb-3"><i class="feather icon-database"></i> Tier 1 — Live Database</h5>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card feed-card">
      <div class="card-body p-t-0 p-b-0">
        <div class="row">
          <div class="col-4 bg-primary border-feed"><i class="feather icon-users f-40"></i></div>
          <div class="col-8">
            <div class="p-t-25 p-b-25">
              <h2 class="f-w-400 m-b-10"><?= $live_companies; ?></h2>
              <p class="text-muted m-0">Total <span class="text-primary f-w-400">Companies</span></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card feed-card">
      <div class="card-body p-t-0 p-b-0">
        <div class="row">
          <div class="col-4 bg-success border-feed"><i class="feather icon-user-check f-40"></i></div>
          <div class="col-8">
            <div class="p-t-25 p-b-25">
              <h2 class="f-w-400 m-b-10"><?= $live_active; ?></h2>
              <p class="text-muted m-0">Active <span class="text-success f-w-400">Companies</span></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tier 2 — Archive DB -->
  <div class="col-12 mt-3">
    <h5 class="mb-3"><i class="feather icon-archive"></i> Tier 2 — Archive Database</h5>
  </div>
  <div class="col-xl-2 col-md-4">
    <div class="card feed-card">
      <div class="card-body p-t-0 p-b-0">
        <div class="row">
          <div class="col-4 bg-warning border-feed"><i class="feather icon-briefcase f-40"></i></div>
          <div class="col-8">
            <div class="p-t-25 p-b-25">
              <h2 class="f-w-400 m-b-10"><?= $arc_snapshots; ?></h2>
              <p class="text-muted m-0 small">Snapshots</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-2 col-md-4">
    <div class="card feed-card">
      <div class="card-body p-t-0 p-b-0">
        <div class="row">
          <div class="col-4 bg-info border-feed"><i class="feather icon-users f-40"></i></div>
          <div class="col-8">
            <div class="p-t-25 p-b-25">
              <h2 class="f-w-400 m-b-10"><?= $arc_employees; ?></h2>
              <p class="text-muted m-0 small">Employees</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-2 col-md-4">
    <div class="card feed-card">
      <div class="card-body p-t-0 p-b-0">
        <div class="row">
          <div class="col-4 bg-secondary border-feed"><i class="feather icon-clock f-40"></i></div>
          <div class="col-8">
            <div class="p-t-25 p-b-25">
              <h2 class="f-w-400 m-b-10"><?= number_format($arc_attendance); ?></h2>
              <p class="text-muted m-0 small">Attendance</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-2 col-md-4">
    <div class="card feed-card">
      <div class="card-body p-t-0 p-b-0">
        <div class="row">
          <div class="col-4 bg-success border-feed"><i class="fas fa-money-bill-wave f-40"></i></div>
          <div class="col-8">
            <div class="p-t-25 p-b-25">
              <h2 class="f-w-400 m-b-10"><?= number_format($arc_payroll); ?></h2>
              <p class="text-muted m-0 small">Payroll</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-2 col-md-4">
    <div class="card feed-card">
      <div class="card-body p-t-0 p-b-0">
        <div class="row">
          <div class="col-4 bg-danger border-feed"><i class="feather icon-calendar f-40"></i></div>
          <div class="col-8">
            <div class="p-t-25 p-b-25">
              <h2 class="f-w-400 m-b-10"><?= number_format($arc_leaves); ?></h2>
              <p class="text-muted m-0 small">Leaves</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-2 col-md-4">
    <div class="card feed-card">
      <div class="card-body p-t-0 p-b-0">
        <div class="row">
          <div class="col-4 bg-primary border-feed"><i class="feather icon-mail f-40"></i></div>
          <div class="col-8">
            <div class="p-t-25 p-b-25">
              <h2 class="f-w-400 m-b-10"><?= number_format($arc_contacts); ?></h2>
              <p class="text-muted m-0 small">Contacts</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tier 3 — Vault -->
  <div class="col-12 mt-3">
    <h5 class="mb-3"><i class="feather icon-hard-drive"></i> Tier 3 — Vault</h5>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card feed-card">
      <div class="card-body p-t-0 p-b-0">
        <div class="row">
          <div class="col-4 bg-dark border-feed"><i class="feather icon-package f-40"></i></div>
          <div class="col-8">
            <div class="p-t-25 p-b-25">
              <h2 class="f-w-400 m-b-10"><?= $arc_vault; ?></h2>
              <p class="text-muted m-0">Vault <span class="text-dark f-w-400">Bundles</span></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card">
      <div class="card-body">
        <h6 class="text-muted mb-2">Last Rotation</h6>
        <h4 class="f-w-400"><?= is_string($last_rotation_date) && $last_rotation_date !== 'Never' ? date('d M Y H:i', strtotime($last_rotation_date)) : $last_rotation_date; ?></h4>
      </div>
    </div>
  </div>
</div>

<!-- Quick links -->
<div class="row mt-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><h5>Archive Portal</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-2 col-sm-4 mb-3 text-center">
            <a href="<?= site_url('erp/archive/companies'); ?>" class="btn btn-light-primary btn-block p-3">
              <i class="feather icon-briefcase f-24 d-block mb-2"></i> Companies
            </a>
          </div>
          <div class="col-md-2 col-sm-4 mb-3 text-center">
            <a href="<?= site_url('erp/archive/search'); ?>" class="btn btn-light-warning btn-block p-3">
              <i class="feather icon-search f-24 d-block mb-2"></i> Search
            </a>
          </div>
          <div class="col-md-2 col-sm-4 mb-3 text-center">
            <a href="<?= site_url('erp/archive/contacts'); ?>" class="btn btn-light-info btn-block p-3">
              <i class="feather icon-mail f-24 d-block mb-2"></i> Contacts
            </a>
          </div>
          <div class="col-md-2 col-sm-4 mb-3 text-center">
            <a href="<?= site_url('erp/archive/vault'); ?>" class="btn btn-light-dark btn-block p-3">
              <i class="feather icon-hard-drive f-24 d-block mb-2"></i> Vault
            </a>
          </div>
          <div class="col-md-2 col-sm-4 mb-3 text-center">
            <a href="<?= site_url('erp/archive/settings'); ?>" class="btn btn-light-secondary btn-block p-3">
              <i class="feather icon-settings f-24 d-block mb-2"></i> Settings
            </a>
          </div>
          <div class="col-md-2 col-sm-4 mb-3 text-center">
            <button type="button" class="btn btn-light-danger btn-block p-3" id="btn-trigger-archive">
              <i class="feather icon-rotate-cw f-24 d-block mb-2"></i> Trigger Archive
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('#btn-trigger-archive').on('click', function(){
    if(!confirm('Run archive process now? This may take a few minutes.')) return;
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="feather icon-loader spin"></i> Running...');
    $.ajax({
      url: '<?= site_url("erp/archive/trigger"); ?>',
      type: 'POST',
      data: {type:'trigger', '<?= csrf_token(); ?>':'<?= csrf_hash(); ?>'},
      dataType: 'json',
      success: function(data){
        btn.prop('disabled', false).html('<i class="feather icon-rotate-cw f-24 d-block mb-2"></i> Trigger Archive');
        if(data.result){ toastr.success(data.result); location.reload(); }
        if(data.error) toastr.error(data.error);
      },
      error: function(){ btn.prop('disabled', false); toastr.error('Request failed.'); }
    });
  });
});
</script>
