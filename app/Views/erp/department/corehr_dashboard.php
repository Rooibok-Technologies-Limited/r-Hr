<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Core-HR overview dashboard — company-scoped headcount KPIs + recent hires.
 * Renders a clean empty-state (zeros / "No records yet") for companies with
 * no staff yet. Data supplied by Erp\Department::corehr_dashboard.
 */
$kpi    = $kpi ?? ['employees' => 0, 'active' => 0, 'inactive' => 0, 'departments' => 0, 'designations' => 0];
$people = $recent_employees ?? [];
$tiles  = [
  ['Total employees', (int) ($kpi['employees'] ?? 0),    'users',      'brand'],
  ['Active',          (int) ($kpi['active'] ?? 0),       'user-check', 'success'],
  ['Inactive',        (int) ($kpi['inactive'] ?? 0),     'user-x',     'warning'],
  ['Departments',     (int) ($kpi['departments'] ?? 0),  'git-branch', 'info'],
  ['Designations',    (int) ($kpi['designations'] ?? 0), 'award',      'info'],
];
?>
<div class="row rk-kpi-row">
  <?php foreach ($tiles as $t): ?>
  <div class="col-xl col-md-4 col-sm-6">
    <div class="card rk-kpi rk-kpi-<?= esc($t[3]) ?>"><div class="card-body">
      <div class="rk-kpi-top"><span class="rk-kpi-label"><?= esc($t[0]) ?></span><i data-feather="<?= esc($t[2]) ?>"></i></div>
      <div class="rk-kpi-value"><?= number_format($t[1]) ?></div>
    </div></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Recent employees</h5>
    <a href="<?= site_url('erp/employees') ?>" class="btn btn-sm btn-outline-primary"><i data-feather="users" class="mr-1"></i> All employees</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Status</th>
            <th class="text-right">Joined</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($people)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No records yet.</td></tr>
        <?php else: foreach ($people as $p): ?>
          <tr>
            <td><?= esc(trim(($p['first_name'] ?? '').' '.($p['last_name'] ?? '')) ?: '—') ?></td>
            <td><?= esc($p['email'] ?? '') ?: '—' ?></td>
            <td><?= esc($p['contact_number'] ?? '') ?: '—' ?></td>
            <td>
              <?php if ((int) ($p['is_active'] ?? 0) === 1): ?>
                <span class="badge badge-light-success">Active</span>
              <?php else: ?>
                <span class="badge badge-light-warning">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="text-right"><?= esc($p['created_at'] ?? '') ?: '—' ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
