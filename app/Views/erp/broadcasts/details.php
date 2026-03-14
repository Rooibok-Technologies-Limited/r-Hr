<?php
use App\Models\UsersModel;
$session   = \Config\Services::session();
$usession  = $session->get('sup_username');
$broadcast = $broadcast ?? [];
$logs      = $logs ?? [];
$stats     = $stats ?? ['total' => 0, 'email_sent' => 0, 'sms_sent' => 0, 'inapp_sent' => 0, 'failed' => 0];

$channels = $broadcast['channels'] ?? [];
if (is_string($channels)) {
    $channels = json_decode($channels, true) ?: [];
}

$audienceLabel = match($broadcast['audience_type'] ?? '') {
    'all_employees'      => 'All Employees',
    'department'         => 'Department(s)',
    'individual'         => 'Individual(s)',
    'all_company_admins' => 'All Company Admins',
    default              => ucfirst($broadcast['audience_type'] ?? '-'),
};

$statusColor = match($broadcast['status'] ?? 'draft') {
    'draft'   => 'secondary',
    'queued'  => 'warning',
    'sending' => 'info',
    'sent'    => 'success',
    'failed'  => 'danger',
    default   => 'secondary',
};
?>

<div class="row">
  <!-- Back button -->
  <div class="col-12 mb-3">
    <a href="<?= site_url('erp/broadcasts'); ?>" class="btn btn-sm btn-light-secondary">
      <i class="feather icon-arrow-left"></i> Back to Broadcasts
    </a>
  </div>
</div>

<!-- Broadcast info card -->
<div class="card">
  <div class="card-header">
    <h5><i class="feather icon-radio"></i> <?= esc($broadcast['subject'] ?? 'Broadcast Details') ?></h5>
    <div class="card-header-right">
      <span class="badge badge-<?= $statusColor ?> badge-lg"><?= ucfirst(esc($broadcast['status'] ?? 'draft')) ?></span>
    </div>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <table class="table table-borderless">
          <tr>
            <td class="font-weight-bold" width="160">Type:</td>
            <td><span class="badge badge-primary"><?= ucfirst(esc($broadcast['broadcast_type'] ?? 'memo')) ?></span></td>
          </tr>
          <tr>
            <td class="font-weight-bold">Audience:</td>
            <td><?= $audienceLabel ?></td>
          </tr>
          <tr>
            <td class="font-weight-bold">Channels:</td>
            <td>
              <?php foreach ($channels as $ch): ?>
                <?php $color = match($ch) { 'email' => 'info', 'sms' => 'warning', 'inapp' => 'success', default => 'secondary' }; ?>
                <span class="badge badge-<?= $color ?>"><?= ucfirst($ch) ?></span>
              <?php endforeach; ?>
            </td>
          </tr>
          <tr>
            <td class="font-weight-bold">Sent By:</td>
            <td><?= esc($broadcast['sender_name'] ?? '-') ?></td>
          </tr>
          <tr>
            <td class="font-weight-bold">Created:</td>
            <td><?= isset($broadcast['created_at']) ? date('d M Y H:i', strtotime($broadcast['created_at'])) : '-' ?></td>
          </tr>
          <?php if (! empty($broadcast['scheduled_at'])): ?>
          <tr>
            <td class="font-weight-bold">Scheduled:</td>
            <td><?= date('d M Y H:i', strtotime($broadcast['scheduled_at'])) ?></td>
          </tr>
          <?php endif; ?>
        </table>
      </div>
      <div class="col-md-6">
        <!-- Delivery stats -->
        <div class="row text-center">
          <div class="col-4 col-md-4 mb-3">
            <div class="card border shadow-none">
              <div class="card-body py-3">
                <h3 class="mb-0 text-primary"><?= $stats['total'] ?></h3>
                <small class="text-muted">Total</small>
              </div>
            </div>
          </div>
          <div class="col-4 col-md-4 mb-3">
            <div class="card border shadow-none">
              <div class="card-body py-3">
                <h3 class="mb-0 text-info"><?= $stats['email_sent'] ?></h3>
                <small class="text-muted">Email Sent</small>
              </div>
            </div>
          </div>
          <div class="col-4 col-md-4 mb-3">
            <div class="card border shadow-none">
              <div class="card-body py-3">
                <h3 class="mb-0 text-warning"><?= $stats['sms_sent'] ?></h3>
                <small class="text-muted">SMS Sent</small>
              </div>
            </div>
          </div>
          <div class="col-4 col-md-4 mb-3">
            <div class="card border shadow-none">
              <div class="card-body py-3">
                <h3 class="mb-0 text-success"><?= $stats['inapp_sent'] ?></h3>
                <small class="text-muted">In-App Sent</small>
              </div>
            </div>
          </div>
          <div class="col-4 col-md-4 mb-3">
            <div class="card border shadow-none">
              <div class="card-body py-3">
                <h3 class="mb-0 text-danger"><?= $stats['failed'] ?></h3>
                <small class="text-muted">Failed</small>
              </div>
            </div>
          </div>
          <div class="col-4 col-md-4 mb-3">
            <div class="card border shadow-none">
              <div class="card-body py-3">
                <?php $successRate = $stats['total'] > 0 ? round((($stats['total'] - $stats['failed']) / $stats['total']) * 100) : 0; ?>
                <h3 class="mb-0 text-<?= $successRate >= 90 ? 'success' : ($successRate >= 50 ? 'warning' : 'danger') ?>"><?= $successRate ?>%</h3>
                <small class="text-muted">Success Rate</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Message content -->
<div class="card">
  <div class="card-header">
    <h5>Message Content</h5>
  </div>
  <div class="card-body">
    <?php if (! empty($broadcast['body_html'])): ?>
    <div class="border rounded p-3 mb-3">
      <?= $broadcast['body_html'] ?>
    </div>
    <?php endif; ?>
    <?php if (! empty($broadcast['body_sms'])): ?>
    <div class="border rounded p-3 bg-light">
      <strong>SMS:</strong> <?= esc($broadcast['body_sms']) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Per-recipient delivery log -->
<div class="card">
  <div class="card-header">
    <h5><i class="feather icon-list"></i> Delivery Log</h5>
    <div class="card-header-right">
      <?php if ($stats['failed'] > 0): ?>
      <button type="button" class="btn btn-sm btn-outline-danger mr-2" id="btn_retry" onclick="retryFailed()">
        <i class="feather icon-refresh-cw"></i> Retry Failed (<?= $stats['failed'] ?>)
      </button>
      <?php endif; ?>
      <a href="javascript:void(0)" onclick="exportCSV()" class="btn btn-sm btn-outline-secondary">
        <i class="feather icon-download"></i> Export CSV
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="box-datatable table-responsive">
      <table class="datatables-demo table table-striped table-bordered" id="xin_table">
        <thead>
          <tr>
            <th>#</th>
            <th>Recipient</th>
            <th>Email</th>
            <th>Phone</th>
            <th>In-App</th>
            <th>Email</th>
            <th>SMS</th>
            <th>Error</th>
            <th>Sent At</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; foreach ($logs as $log): ?>
          <tr>
            <td><?= $i ?></td>
            <td><?= esc($log['recipient_name'] ?? '-') ?></td>
            <td><?= esc($log['recipient_email'] ?? '-') ?></td>
            <td><?= esc($log['recipient_phone'] ?? '-') ?></td>
            <td class="text-center">
              <?php if (($log['inapp_sent'] ?? 0) == 1): ?>
                <span class="badge badge-success">Sent</span>
              <?php else: ?>
                <span class="badge badge-light">-</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if (($log['email_sent'] ?? 0) == 1): ?>
                <span class="badge badge-success">Sent</span>
                <?php if (($log['email_opened'] ?? 0) == 1): ?>
                  <span class="badge badge-info">Opened</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge badge-light">-</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if (($log['sms_sent'] ?? 0) == 1): ?>
                <span class="badge badge-success">Sent</span>
              <?php else: ?>
                <span class="badge badge-light">-</span>
              <?php endif; ?>
              <?php if (! empty($log['sms_status'])): ?>
                <small class="text-muted d-block"><?= esc($log['sms_status']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php if (! empty($log['error_message'])): ?>
                <span class="text-danger" title="<?= esc($log['error_message']) ?>">
                  <?= esc(mb_substr($log['error_message'], 0, 50)) ?><?= mb_strlen($log['error_message']) > 50 ? '...' : '' ?>
                </span>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?= ! empty($log['sent_at']) ? date('d M Y H:i', strtotime($log['sent_at'])) : '-' ?></td>
          </tr>
          <?php $i++; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function retryFailed() {
    if (!confirm('Retry all failed deliveries for this broadcast?')) return;

    var btn = document.getElementById('btn_retry');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Retrying...';

    var formData = new FormData();
    formData.append('broadcast_id', '<?= $broadcast['broadcast_id'] ?? '' ?>');
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch('<?= site_url("erp/broadcasts/send"); ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="feather icon-refresh-cw"></i> Retry Failed';
        if (data.result) {
            alert(data.result);
            location.reload();
        } else if (data.error) {
            alert(data.error);
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="feather icon-refresh-cw"></i> Retry Failed';
        alert('Network error. Please try again.');
    });
}

function exportCSV() {
    var table = document.getElementById('xin_table');
    if (!table) return;

    var csv = [];
    var rows = table.querySelectorAll('tr');

    rows.forEach(function(row) {
        var cols = row.querySelectorAll('td, th');
        var rowData = [];
        cols.forEach(function(col) {
            var text = col.innerText.replace(/"/g, '""').replace(/\n/g, ' ').trim();
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });

    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'broadcast_log_<?= $broadcast['broadcast_id'] ?? 0 ?>.csv';
    link.click();
}
</script>
