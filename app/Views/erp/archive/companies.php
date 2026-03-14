<?php
/**
 * Phase 10.7 — Archived Companies List
 */
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-briefcase"></i> Archived Companies</h5>
        <div class="card-header-right">
          <a href="<?= site_url('erp/archive'); ?>" class="btn btn-sm btn-light-primary"><i class="feather icon-arrow-left"></i> Back to Dashboard</a>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="arc-companies-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Company Name</th>
                <th>Admin</th>
                <th>Email</th>
                <th>Plan</th>
                <th>Months Paid</th>
                <th>Archive Date</th>
                <th>Reason</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($snapshots)): ?>
              <?php foreach ($snapshots as $i => $s): ?>
              <tr>
                <td><?= $i + 1; ?></td>
                <td><?= esc($s['company_name']); ?></td>
                <td><?= esc($s['admin_first_name'] . ' ' . $s['admin_last_name']); ?></td>
                <td><?= esc($s['admin_email']); ?></td>
                <td><span class="badge badge-info"><?= esc($s['plan_name'] ?: $s['plan_tier']); ?></span></td>
                <td><?= $s['total_months_paid'] ?: '--'; ?></td>
                <td><?= date('d M Y', strtotime($s['archived_at'])); ?></td>
                <td><?= esc($s['archive_reason'] ?: $s['cancellation_reason'] ?: '--'); ?></td>
                <td>
                  <a href="<?= site_url('erp/archive/company/' . $s['snapshot_id']); ?>" class="btn btn-sm btn-light-primary" title="View Details"><i class="feather icon-eye"></i></a>
                  <?php if (!empty($s['vault_bundle_path'])): ?>
                  <a href="<?= site_url('erp/archive/download/' . $s['snapshot_id']); ?>" class="btn btn-sm btn-light-info" title="Download Vault"><i class="feather icon-download"></i></a>
                  <?php endif; ?>
                  <?php if (empty($s['restored_at'])): ?>
                  <button type="button" class="btn btn-sm btn-light-success btn-restore" data-id="<?= $s['snapshot_id']; ?>" data-name="<?= esc($s['company_name']); ?>" title="Restore to Live"><i class="feather icon-refresh-cw"></i></button>
                  <?php else: ?>
                  <span class="badge badge-success">Restored</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="9" class="text-center">No archived companies found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Restore Company to Live</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p>Restore <strong id="restore-company-name"></strong> to the live system?</p>
        <div class="form-group">
          <label>New Subscription Expiry Date</label>
          <input type="date" class="form-control" id="restore-expiry" value="<?= date('Y-m-d', strtotime('+1 year')); ?>">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="btn-confirm-restore"><i class="feather icon-refresh-cw"></i> Restore</button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('#arc-companies-table').DataTable({
    "order": [[6, "desc"]],
    "pageLength": 25
  });

  var restoreId = null;
  $(document).on('click', '.btn-restore', function(){
    restoreId = $(this).data('id');
    $('#restore-company-name').text($(this).data('name'));
    $('#restoreModal').modal('show');
  });

  $('#btn-confirm-restore').on('click', function(){
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="feather icon-loader spin"></i> Restoring...');
    $.ajax({
      url: '<?= site_url("erp/archive/restore/"); ?>' + restoreId,
      type: 'POST',
      data: {
        type: 'restore',
        new_expiry: $('#restore-expiry').val(),
        '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
      },
      dataType: 'json',
      success: function(data){
        btn.prop('disabled', false).html('<i class="feather icon-refresh-cw"></i> Restore');
        $('#restoreModal').modal('hide');
        if(data.result){ toastr.success(data.result); setTimeout(function(){ location.reload(); }, 1500); }
        if(data.error) toastr.error(data.error);
      },
      error: function(){ btn.prop('disabled', false); toastr.error('Request failed.'); }
    });
  });
});
</script>
