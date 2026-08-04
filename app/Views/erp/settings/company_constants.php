<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Company Constants — lists the company's configurable types (leave types,
 * income/expense types, competencies, etc.) grouped into cards. Inline edit
 * and delete are wired to the generic Types endpoints (company-scoped).
 */

use App\Models\UsersModel;
use App\Models\ConstantsModel;

$session  = \Config\Services::session();
$usession = $session->get('sup_username');

$UsersModel      = new UsersModel();
$ConstantsModel  = new ConstantsModel();

$user_info = $UsersModel->where('user_id', $usession['sup_user_id'] ?? 0)->first();
if (($user_info['user_type'] ?? '') === 'staff') {
    $company_id = $user_info['company_id'] ?? 0;
} else {
    $company_id = $usession['sup_user_id'] ?? 0;
}

// Pull every constant owned by this company, then group by type in PHP.
$all_constants = $ConstantsModel->where('company_id', $company_id)
                                ->orderBy('type', 'ASC')
                                ->orderBy('category_name', 'ASC')
                                ->findAll();

$grouped = [];
foreach (($all_constants ?? []) as $c) {
    $grouped[$c['type'] ?? 'other'][] = $c;
}

// Friendly labels + icons for the known constant families.
$type_meta = [
    'company_type'   => ['label' => 'Company Types',       'icon' => 'briefcase',   'desc' => 'Legal entity classifications'],
    'currency_type'  => ['label' => 'Currencies',          'icon' => 'dollar-sign', 'desc' => 'Accepted currencies'],
    'leave_type'     => ['label' => 'Leave Types',         'icon' => 'calendar',    'desc' => 'Categories of employee leave'],
    'income_type'    => ['label' => 'Income Types',        'icon' => 'trending-up', 'desc' => 'Payroll income components'],
    'expense_type'   => ['label' => 'Expense Types',       'icon' => 'trending-down','desc' => 'Payroll deduction / expense components'],
    'tax_type'       => ['label' => 'Tax Types',           'icon' => 'percent',     'desc' => 'Statutory tax bands'],
    'payment_method' => ['label' => 'Payment Methods',     'icon' => 'credit-card', 'desc' => 'How payments are settled'],
    'goal_type'      => ['label' => 'Goal Types',          'icon' => 'target',      'desc' => 'Performance goal categories'],
    'training_type'  => ['label' => 'Training Types',      'icon' => 'book-open',   'desc' => 'Learning & development categories'],
    'warning_type'   => ['label' => 'Warning Types',       'icon' => 'alert-triangle','desc' => 'Disciplinary warning categories'],
    'competencies'   => ['label' => 'Competencies',        'icon' => 'award',       'desc' => 'Individual competencies'],
    'competencies2'  => ['label' => 'Org Competencies',    'icon' => 'users',       'desc' => 'Organisational competencies'],
    'religion'       => ['label' => 'Religions',           'icon' => 'globe',       'desc' => 'Employee religion options'],
];

// Order: known types first (in the map order), then any unknown types.
$ordered_types = [];
foreach (array_keys($type_meta) as $t) {
    if (isset($grouped[$t])) { $ordered_types[] = $t; }
}
foreach (array_keys($grouped) as $t) {
    if (!in_array($t, $ordered_types, true)) { $ordered_types[] = $t; }
}

$total = count($all_constants ?? []);

$is_empty = static function ($v) {
    $v = trim((string) $v);
    return $v === '' || strcasecmp($v, 'Null') === 0;
};
?>

<div id="smartwizard-constants" class="border-bottom smartwizard-example sw-main sw-theme-default mt-2">
  <ul class="nav nav-tabs step-anchor">
    <li class="nav-item clickable"> <a href="<?= site_url('erp/company-settings'); ?>" class="mb-3 nav-link"> <span class="sw-icon fas fa-cog"></span>
      <?= lang('Main.left_settings'); ?>
      <div class="text-muted small"><?= lang('Main.header_configuration'); ?></div>
      </a> </li>
    <li class="nav-item active"> <a href="<?= site_url('erp/company-constants'); ?>" class="mb-3 nav-link"> <span class="sw-icon fas fa-adjust"></span>
      <?= lang('Main.left_constants'); ?>
      <div class="text-muted small"><?= lang('Main.xin_set_up_all_types'); ?></div>
      </a> </li>
  </ul>
</div>
<hr class="border-light m-0 mb-3">

<div class="row animated fadeInRight">
  <div class="col-12 mb-3">
    <div class="d-flex align-items-center flex-wrap">
      <div class="mr-auto">
        <h5 class="mb-0"><?= esc(lang('Main.left_constants')); ?></h5>
        <span class="text-muted small">Configurable types used across HR, payroll and recruitment.</span>
      </div>
      <span class="badge badge-light-primary p-2"><?= (int) $total; ?> total entr<?= $total === 1 ? 'y' : 'ies'; ?></span>
    </div>
  </div>

  <?php if ($total === 0): ?>
  <div class="col-12">
    <div class="card">
      <div class="card-body text-center py-5">
        <i data-feather="sliders" style="width:44px;height:44px" class="text-muted mb-3"></i>
        <h5 class="mb-2">No constants defined yet</h5>
        <p class="text-muted mb-0">
          Your organisation has no custom types configured. Types such as leave, income and
          expense categories are created from their respective HR &amp; payroll modules and will
          appear here once added.
        </p>
      </div>
    </div>
  </div>
  <?php else: ?>

  <?php foreach ($ordered_types as $type):
        $rows = $grouped[$type] ?? [];
        $meta = $type_meta[$type] ?? ['label' => ucwords(str_replace('_', ' ', $type)), 'icon' => 'tag', 'desc' => ''];
  ?>
  <div class="col-lg-6 mb-3">
    <div class="card h-100 user-profile-list">
      <div class="card-header d-flex align-items-center">
        <i data-feather="<?= esc($meta['icon']); ?>" class="mr-2" style="width:18px;height:18px"></i>
        <div>
          <h5 class="mb-0"><?= esc($meta['label']); ?></h5>
          <?php if (!empty($meta['desc'])): ?><span class="text-muted small"><?= esc($meta['desc']); ?></span><?php endif; ?>
        </div>
        <span class="badge badge-secondary ml-auto"><?= count($rows); ?></span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-striped table-bordered mb-0">
            <thead>
              <tr>
                <th width="40">#</th>
                <th>Name</th>
                <th width="90" class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; foreach ($rows as $r): $cid = $r['constants_id'] ?? 0; ?>
              <tr id="const-row-<?= (int) $cid; ?>">
                <td><?= $i++; ?></td>
                <td>
                  <span class="const-name-display"><?= esc($r['category_name'] ?? ''); ?></span>
                  <input type="text" class="form-control form-control-sm const-name-edit d-none" value="<?= esc($r['category_name'] ?? '', 'attr'); ?>">
                  <?php
                    $extra = [];
                    if (!$is_empty($r['field_one'] ?? '')) { $extra[] = esc($r['field_one']); }
                    if (!$is_empty($r['field_two'] ?? '')) { $extra[] = esc($r['field_two']); }
                    if ($extra):
                  ?>
                    <span class="text-muted small d-block"><?= implode(' &middot; ', $extra); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-right text-nowrap">
                  <button type="button" class="btn icon-btn btn-sm btn-light-primary edit-const" data-id="<?= uencode($cid); ?>" title="Edit"><i class="feather icon-edit"></i></button>
                  <button type="button" class="btn icon-btn btn-sm btn-light-success save-const d-none" data-id="<?= uencode($cid); ?>" title="Save"><i class="feather icon-check"></i></button>
                  <button type="button" class="btn icon-btn btn-sm btn-light-danger delete-const" data-id="<?= uencode($cid); ?>" title="Delete"><i class="feather icon-trash-2"></i></button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($rows)): ?>
              <tr><td colspan="3" class="text-center text-muted small py-3">No entries</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>
</div>

<script>
$(document).ready(function () {
  // toggle inline edit
  $(document).on('click', '.edit-const', function () {
    var row = $(this).closest('tr');
    row.find('.const-name-display').addClass('d-none');
    row.find('.const-name-edit').removeClass('d-none').focus();
    $(this).addClass('d-none');
    row.find('.save-const').removeClass('d-none');
  });

  // save inline edit -> generic update_constants_type
  $(document).on('click', '.save-const', function () {
    var row  = $(this).closest('tr');
    var id   = $(this).data('id');
    var name = row.find('.const-name-edit').val();
    if (!name || !name.trim()) { toastr.error('Name cannot be empty'); return; }
    $.ajax({
      url: site_url + '/erp/types/update_constants_type/',
      type: 'POST',
      data: { type: 'edit_record', token: id, name: name, csrf_token_name: csrf_hash },
      dataType: 'json',
      success: function (data) {
        if (data.csrf_hash) { csrf_hash = data.csrf_hash; }
        if (data.result) { toastr.success(data.result); location.reload(); }
        if (data.error)  { toastr.error(data.error); }
      }
    });
  });

  // delete -> generic delete_type
  $(document).on('click', '.delete-const', function () {
    var id = $(this).data('id');
    if (confirm('Delete this entry?')) {
      $.ajax({
        url: site_url + '/erp/types/delete_type/',
        type: 'POST',
        data: { type: 'delete_record', _token: id, csrf_token_name: csrf_hash },
        dataType: 'json',
        success: function (data) {
          if (data.csrf_hash) { csrf_hash = data.csrf_hash; }
          if (data.result) { toastr.success(data.result); location.reload(); }
          if (data.error)  { toastr.error(data.error); }
        }
      });
    }
  });
});
</script>
