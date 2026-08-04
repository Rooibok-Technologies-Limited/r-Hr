<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * System Data Import — landing page for bulk CSV import of core HR records.
 * The upload UI is fully rendered; the server-side import pipeline is not yet
 * wired, so submit is disabled with a clear notice until the backend ships.
 */

// Supported datasets and the columns each CSV is expected to carry.
$datasets = [
    'employees' => [
        'label'   => 'Employees',
        'icon'    => 'users',
        'columns' => ['first_name', 'last_name', 'email', 'department', 'designation', 'joining_date'],
    ],
    'departments' => [
        'label'   => 'Departments',
        'icon'    => 'grid',
        'columns' => ['department_name', 'parent_department'],
    ],
    'designations' => [
        'label'   => 'Designations',
        'icon'    => 'award',
        'columns' => ['designation_name', 'department'],
    ],
    'leaves' => [
        'label'   => 'Leave Records',
        'icon'    => 'calendar',
        'columns' => ['employee_email', 'leave_type', 'start_date', 'end_date', 'reason'],
    ],
    'attendance' => [
        'label'   => 'Attendance',
        'icon'    => 'clock',
        'columns' => ['employee_email', 'date', 'clock_in', 'clock_out'],
    ],
];

$backend_ready = true; // POST processor: Erp\Application::import_process
$supported_import = ['departments', 'designations', 'employees', 'attendance', 'leaves']; // live datasets
$session = \Config\Services::session();
?>

<div class="row animated fadeInRight">
  <div class="col-12 mb-3">
    <h5 class="mb-0"><?= esc($breadcrumbs ?? 'Import Data'); ?></h5>
    <span class="text-muted small">Bulk-load core HR records from a CSV file.</span>
  </div>

  <?php if ($session->getFlashdata('import_success')): ?>
  <div class="col-12">
    <div class="alert alert-success" role="alert">
      <i data-feather="check-circle" class="mr-2" style="width:18px;height:18px"></i>
      <?= esc($session->getFlashdata('import_success')); ?>
      <?php $__errs = $session->getFlashdata('import_errors'); if (!empty($__errs)): ?>
      <ul class="mb-0 mt-2 small"><?php foreach ($__errs as $__e): ?><li><?= esc($__e); ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($session->getFlashdata('import_error')): ?>
  <div class="col-12">
    <div class="alert alert-danger" role="alert">
      <i data-feather="alert-triangle" class="mr-2" style="width:18px;height:18px"></i>
      <?= esc($session->getFlashdata('import_error')); ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="col-lg-7 mb-3">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center">
        <i data-feather="upload-cloud" class="mr-2" style="width:20px;height:20px"></i>
        <h5 class="mb-0">Upload File</h5>
      </div>
      <div class="card-body">
        <?php $attributes = ['name' => 'import_data', 'id' => 'xin-import-form', 'autocomplete' => 'off']; ?>
        <?= form_open_multipart('erp/application/import', $attributes); ?>

        <div class="form-group">
          <label for="import_type">Data Type <span class="text-danger">*</span></label>
          <select class="form-control" name="import_type" id="import_type" <?= $backend_ready ? '' : 'disabled'; ?>>
            <option value="">— Select what to import —</option>
            <?php foreach ($datasets as $key => $ds): $ok = in_array($key, $supported_import, true); ?>
            <option value="<?= esc($key, 'attr'); ?>"<?= $ok ? '' : ' disabled'; ?>><?= esc($ds['label']); ?><?= $ok ? '' : ' (coming soon)'; ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="import_file">CSV File <span class="text-danger">*</span></label>
          <div class="custom-file">
            <input type="file" class="custom-file-input" name="import_file" id="import_file" accept=".csv" <?= $backend_ready ? '' : 'disabled'; ?>>
            <label class="custom-file-label" for="import_file">Choose file…</label>
          </div>
          <small class="form-text text-muted">Maximum 5&nbsp;MB. First row must be the column header.</small>
        </div>

        <div class="form-group">
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" name="skip_duplicates" id="skip_duplicates" value="1" <?= $backend_ready ? '' : 'disabled'; ?> checked>
            <label class="custom-control-label" for="skip_duplicates">Skip rows that duplicate existing records</label>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" <?= $backend_ready ? '' : 'disabled'; ?>>
          <i data-feather="upload" style="width:16px;height:16px" class="mr-1"></i>
          <?= lang('Main.xin_save') ?? 'Import'; ?>
        </button>
        <span class="text-muted small ml-2">All datasets are live. First row must be the column header.</span>

        <?= form_close(); ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5 mb-3">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center">
        <i data-feather="file-text" class="mr-2" style="width:20px;height:20px"></i>
        <h5 class="mb-0">Supported Formats</h5>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          Files must be UTF-8 encoded <code>.csv</code> with a header row. Pick a dataset
          below to see the exact columns expected.
        </p>
        <div class="accordion" id="fmt-accordion">
          <?php $n = 0; foreach ($datasets as $key => $ds): $n++; ?>
          <div class="card mb-1">
            <div class="card-header p-2" id="fmt-h-<?= $n; ?>">
              <button class="btn btn-link btn-sm text-left p-0 d-flex align-items-center w-100" type="button"
                      data-toggle="collapse" data-target="#fmt-c-<?= $n; ?>" aria-expanded="false" aria-controls="fmt-c-<?= $n; ?>">
                <i data-feather="<?= esc($ds['icon']); ?>" style="width:16px;height:16px" class="mr-2"></i>
                <span><?= esc($ds['label']); ?></span>
                <span class="badge badge-light-secondary ml-auto"><?= count($ds['columns']); ?> cols</span>
              </button>
            </div>
            <div id="fmt-c-<?= $n; ?>" class="collapse" aria-labelledby="fmt-h-<?= $n; ?>" data-parent="#fmt-accordion">
              <div class="card-body p-2">
                <code class="small d-block text-wrap"><?= esc(implode(',', $ds['columns'])); ?></code>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function () {
  // reflect chosen filename on the bootstrap custom file input
  $(document).on('change', '.custom-file-input', function () {
    var name = this.files && this.files.length ? this.files[0].name : 'Choose file…';
    $(this).next('.custom-file-label').text(name);
  });
});
</script>
