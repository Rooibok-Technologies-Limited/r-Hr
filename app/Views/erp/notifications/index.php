<?php
$db = \Config\Database::connect();
$session = \Config\Services::session();
$usession = get_safe_session();
$uid = $usession ? $usession['sup_user_id'] : 0;

$notifications = $db->table('ci_notifications')
    ->groupStart()
        ->where('user_id', $uid)
        ->orWhere('user_id', 0)
    ->groupEnd()
    ->orderBy('created_at', 'DESC')
    ->get()->getResultArray();

$unread = 0;
foreach($notifications as $n) { if($n['is_read'] == 0) $unread++; }
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0">Notifications</h5>
      <small class="text-muted"><?= count($notifications); ?> total, <?= $unread; ?> unread</small>
    </div>
    <div>
      <button class="btn btn-outline-primary btn-sm mr-1" id="np-mark-all">
        <i class="feather icon-check mr-1"></i>Mark All Read
      </button>
      <button class="btn btn-outline-danger btn-sm" id="np-delete-all">
        <i class="feather icon-trash-2 mr-1"></i>Clear All
      </button>
    </div>
  </div>
  <div class="card-body p-0">
    <?php if(empty($notifications)): ?>
    <div class="p-5 text-center text-muted">
      <i class="feather icon-bell" data-feather="bell"></i>
      <p class="mt-3">No notifications</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Notification</th>
            <th>Date</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($notifications as $n): ?>
          <tr class="<?= $n['is_read'] == 0 ? 'table-active' : ''; ?>" id="notif-row-<?= $n['notification_id']; ?>">
            <td>
              <strong><?= esc($n['title']); ?></strong>
              <?php if(!empty($n['body'])): ?>
              <br><small class="text-muted"><?= esc($n['body']); ?></small>
              <?php endif; ?>
            </td>
            <td>
              <small><?= date('d M Y H:i', strtotime($n['created_at'])); ?></small>
            </td>
            <td>
              <?php if($n['is_read'] == 0): ?>
              <span class="badge badge-light-primary">Unread</span>
              <?php else: ?>
              <span class="badge badge-light-secondary">Read</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if(!empty($n['link'])): ?>
              <a href="<?= esc($n['link']); ?>" class="btn btn-sm btn-light-primary mr-1" title="Go to"><i class="feather icon-external-link"></i></a>
              <?php endif; ?>
              <?php if($n['is_read'] == 0): ?>
              <button class="btn btn-sm btn-light-success np-mark-read mr-1" data-id="<?= $n['notification_id']; ?>" title="Mark read"><i class="feather icon-check"></i></button>
              <?php endif; ?>
              <button class="btn btn-sm btn-light-danger np-delete" data-id="<?= $n['notification_id']; ?>" title="Delete"><i class="feather icon-trash-2"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
$(document).ready(function(){
  var csrf = '<?= csrf_token(); ?>=<?= csrf_hash(); ?>';

  // Mark single as read
  $(document).on('click', '.np-mark-read', function(){
    var id = $(this).data('id');
    var row = $('#notif-row-'+id);
    $.post('<?= site_url("erp/notifications/mark-read"); ?>', csrf+'&id='+id, function(){
      row.removeClass('table-active');
      row.find('.badge').removeClass('badge-light-primary').addClass('badge-light-secondary').text('Read');
      row.find('.np-mark-read').remove();
    });
  });

  // Delete single
  $(document).on('click', '.np-delete', function(){
    var id = $(this).data('id');
    $.post('<?= site_url("erp/notifications/delete"); ?>', csrf+'&id='+id, function(){
      $('#notif-row-'+id).fadeOut(300, function(){ $(this).remove(); });
    });
  });

  // Mark all read
  $('#np-mark-all').click(function(){
    $.post('<?= site_url("erp/notifications/mark-all-read"); ?>', csrf, function(){
      location.reload();
    });
  });

  // Delete all
  $('#np-delete-all').click(function(){
    if(!confirm('Delete all notifications?')) return;
    $.post('<?= site_url("erp/notifications/delete-all"); ?>', csrf, function(){
      location.reload();
    });
  });
});
</script>
