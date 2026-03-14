<?php
/**
 * Phase 10.7 — Vault Bundles List
 */
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-hard-drive"></i> Vault Bundles</h5>
        <div class="card-header-right">
          <a href="<?= site_url('erp/archive'); ?>" class="btn btn-sm btn-light-primary"><i class="feather icon-arrow-left"></i> Back</a>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="vault-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Company</th>
                <th>Source ID</th>
                <th>Archive Date</th>
                <th>Bundle Path</th>
                <th>Checksum (SHA-256)</th>
                <th>Size</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($bundles)): ?>
              <?php foreach ($bundles as $i => $b): ?>
              <tr>
                <td><?= $i + 1; ?></td>
                <td><?= esc($b['company_name']); ?></td>
                <td><?= $b['source_company_id']; ?></td>
                <td><?= date('d M Y', strtotime($b['archived_at'])); ?></td>
                <td><code class="small"><?= esc($b['vault_bundle_path']); ?></code></td>
                <td><code class="small"><?= esc($b['vault_checksum'] ?: '--'); ?></code></td>
                <td><?= $b['file_size']; ?></td>
                <td>
                  <a href="<?= site_url('erp/archive/download/' . $b['snapshot_id']); ?>" class="btn btn-sm btn-light-info" title="Download"><i class="feather icon-download"></i> Download</a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="8" class="text-center">No vault bundles found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('#vault-table').DataTable({"pageLength": 25, "order": [[3, "desc"]]});
});
</script>
