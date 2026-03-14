<?php
/**
 * Phase 10.7 — Archive Settings
 * Retention periods, B2 credentials, auto-archive toggle
 */
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-settings"></i> Archive Settings</h5>
        <div class="card-header-right">
          <a href="<?= site_url('erp/archive'); ?>" class="btn btn-sm btn-light-primary"><i class="feather icon-arrow-left"></i> Back</a>
        </div>
      </div>
      <div class="card-body">
        <form id="archive-settings-form">
          <input type="hidden" name="<?= csrf_token(); ?>" value="<?= csrf_hash(); ?>">

          <h6 class="mb-3">Retention Periods</h6>
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="form-group">
                <label>Attendance Records (months)</label>
                <input type="number" name="retention_attendance" class="form-control" value="24" min="6" max="120">
                <small class="text-muted">Records older than this are moved to Tier 2 archive.</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Payroll Records (months)</label>
                <input type="number" name="retention_payroll" class="form-control" value="36" min="12" max="120">
                <small class="text-muted">Payroll records retention period.</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Leave Records (months)</label>
                <input type="number" name="retention_leaves" class="form-control" value="24" min="6" max="120">
                <small class="text-muted">Leave records retention period.</small>
              </div>
            </div>
          </div>

          <hr>
          <h6 class="mb-3">Company Archive Policy</h6>
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="form-group">
                <label>Days after expiry to auto-archive</label>
                <input type="number" name="archive_after_days" class="form-control" value="90" min="30" max="365">
                <small class="text-muted">Companies are archived this many days after subscription expires.</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Auto-Archive</label>
                <select name="auto_archive_enabled" class="form-control">
                  <option value="1">Enabled</option>
                  <option value="0">Disabled</option>
                </select>
                <small class="text-muted">Enable automatic archiving via scheduled job.</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Vault Bundle Format</label>
                <select name="vault_format" class="form-control">
                  <option value="zip">ZIP</option>
                  <option value="tar.gz">TAR.GZ</option>
                </select>
              </div>
            </div>
          </div>

          <hr>
          <h6 class="mb-3">Backblaze B2 Cloud Storage (Tier 3)</h6>
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="form-group">
                <label>B2 Key ID</label>
                <input type="text" name="b2_key_id" class="form-control" placeholder="Application Key ID">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>B2 Application Key</label>
                <input type="password" name="b2_app_key" class="form-control" placeholder="Application Key">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>B2 Bucket Name</label>
                <input type="text" name="b2_bucket" class="form-control" placeholder="rooibok-vault">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <button type="submit" class="btn btn-primary"><i class="feather icon-save"></i> Save Settings</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('#archive-settings-form').on('submit', function(e){
    e.preventDefault();
    toastr.info('Archive settings saved. (Settings persistence will be implemented with system configuration table.)');
  });
});
</script>
