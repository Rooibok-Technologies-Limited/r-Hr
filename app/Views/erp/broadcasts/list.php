<?php
use App\Models\UsersModel;
$session = \Config\Services::session();
$usession = $session->get('sup_username');
$UsersModel = new UsersModel();
$current_user = $user_info ?? $UsersModel->where('user_id', $usession['sup_user_id'])->first();
$broadcasts = $broadcasts ?? [];
?>

<div class="card user-profile-list">
  <div class="card-header">
    <h5><i class="feather icon-radio"></i> Broadcasts</h5>
    <div class="card-header-right">
      <a href="<?= site_url('erp/broadcasts/create'); ?>" class="btn waves-effect waves-light btn-primary btn-sm m-0">
        <i data-feather="plus"></i> New Broadcast
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="box-datatable table-responsive">
      <table class="datatables-demo table table-striped table-bordered" id="xin_table">
        <thead>
          <tr>
            <th>#</th>
            <th>Subject</th>
            <th>Type</th>
            <th>Audience</th>
            <th>Recipients</th>
            <th>Channels</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; foreach ($broadcasts as $b): ?>
          <?php
            $channels = $b['channels'] ?? [];
            if (is_string($channels)) {
                $channels = json_decode($channels, true) ?: [];
            }
            $channelBadges = '';
            foreach ($channels as $ch) {
                $color = match($ch) {
                    'email' => 'info',
                    'sms'   => 'warning',
                    'inapp' => 'success',
                    default => 'secondary',
                };
                $channelBadges .= '<span class="badge badge-' . $color . ' mr-1">' . ucfirst($ch) . '</span>';
            }

            $statusColor = match($b['status'] ?? 'draft') {
                'draft'   => 'secondary',
                'queued'  => 'warning',
                'sending' => 'info',
                'sent'    => 'success',
                'failed'  => 'danger',
                default   => 'secondary',
            };

            $audienceLabel = match($b['audience_type'] ?? '') {
                'all_employees'     => 'All Employees',
                'department'        => 'Department(s)',
                'individual'        => 'Individual(s)',
                'all_company_admins' => 'All Company Admins',
                default             => ucfirst($b['audience_type'] ?? '-'),
            };
          ?>
          <tr>
            <td><?= $i ?></td>
            <td>
              <a href="<?= site_url('erp/broadcasts/details/' . $b['broadcast_id']); ?>">
                <?= esc($b['subject'] ?? '(no subject)') ?>
              </a>
            </td>
            <td><span class="badge badge-primary"><?= ucfirst(esc($b['broadcast_type'] ?? 'memo')) ?></span></td>
            <td><?= $audienceLabel ?></td>
            <td class="text-center"><?= (int)($b['total_recipients'] ?? 0) ?></td>
            <td><?= $channelBadges ?></td>
            <td><span class="badge badge-<?= $statusColor ?>"><?= ucfirst(esc($b['status'] ?? 'draft')) ?></span></td>
            <td><?= isset($b['created_at']) ? date('d M Y H:i', strtotime($b['created_at'])) : '-' ?></td>
            <td>
              <a href="<?= site_url('erp/broadcasts/details/' . $b['broadcast_id']); ?>"
                 class="btn icon-btn btn-sm btn-light-primary waves-effect waves-light"
                 data-toggle="tooltip" title="View Details">
                <i class="feather icon-eye"></i>
              </a>
            </td>
          </tr>
          <?php $i++; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (! empty($templates)): ?>
<div class="card user-profile-list mt-3">
  <div class="card-header">
    <h5><i class="feather icon-file-text"></i> Broadcast Templates</h5>
  </div>
  <div class="card-body">
    <div class="box-datatable table-responsive">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>Template Name</th>
            <th>Subject</th>
            <th>Category</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <?php $j = 1; foreach ($templates as $t): ?>
          <tr>
            <td><?= $j ?></td>
            <td><?= esc($t['template_name'] ?? '') ?></td>
            <td><?= esc($t['subject'] ?? '') ?></td>
            <td><?= esc($t['category'] ?? 'general') ?></td>
            <td><?= isset($t['created_at']) ? date('d M Y', strtotime($t['created_at'])) : '-' ?></td>
          </tr>
          <?php $j++; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
