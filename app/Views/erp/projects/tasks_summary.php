<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Tasks summary dashboard — company-scoped task KPIs by status + recent
 * tasks. Renders a clean empty-state for companies with no tasks.
 * Data supplied by Erp\Tasks::tasks_summary.
 */
$kpi   = $kpi ?? ['total' => 0, 'not_started' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0, 'hold' => 0];
$tasks = $recent_tasks ?? [];
$tiles = [
  ['Total tasks',  (int) ($kpi['total'] ?? 0),       'check-square', 'brand'],
  ['Not started',  (int) ($kpi['not_started'] ?? 0), 'circle',       'warning'],
  ['In progress',  (int) ($kpi['in_progress'] ?? 0), 'loader',       'info'],
  ['Completed',    (int) ($kpi['completed'] ?? 0),   'check-circle', 'success'],
];
$status_labels = [
  '0' => ['Not started', 'warning'],
  '1' => ['In progress', 'primary'],
  '2' => ['Completed',   'success'],
  '3' => ['Cancelled',   'danger'],
  '4' => ['On hold',     'secondary'],
];
$badge = function ($s) use ($status_labels) {
  $m = $status_labels[(string) $s] ?? ['Unknown', 'secondary'];
  return '<span class="badge badge-light-'.$m[1].'">'.esc($m[0]).'</span>';
};
?>
<div class="row rk-kpi-row">
  <?php foreach ($tiles as $t): ?>
  <div class="col-xl-3 col-md-6">
    <div class="card rk-kpi rk-kpi-<?= esc($t[3]) ?>"><div class="card-body">
      <div class="rk-kpi-top"><span class="rk-kpi-label"><?= esc($t[0]) ?></span><i data-feather="<?= esc($t[2]) ?>"></i></div>
      <div class="rk-kpi-value"><?= number_format($t[1]) ?></div>
    </div></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Recent tasks</h5>
    <a href="<?= site_url('erp/tasks') ?>" class="btn btn-sm btn-outline-primary"><i data-feather="check-square" class="mr-1"></i> All tasks</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Task</th>
            <th>Progress</th>
            <th>Status</th>
            <th>Start</th>
            <th class="text-right">Due</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($tasks)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No records yet.</td></tr>
        <?php else: foreach ($tasks as $t): ?>
          <tr>
            <td><?= esc($t['task_name'] ?? '') ?: '—' ?></td>
            <td><?= esc((string) ($t['task_progress'] ?? '0')) ?>%</td>
            <td><?= $badge($t['task_status'] ?? '') ?></td>
            <td><?= esc($t['start_date'] ?? '') ?: '—' ?></td>
            <td class="text-right"><?= esc($t['end_date'] ?? '') ?: '—' ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
