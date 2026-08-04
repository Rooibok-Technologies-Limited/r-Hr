<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Helpdesk overview dashboard — company-scoped support-ticket KPIs + recent
 * tickets. Renders a clean empty-state for companies with no tickets.
 * Data supplied by Erp\Tickets::helpdesk_dashboard.
 */
$kpi     = $kpi ?? ['total' => 0, 'open' => 0, 'closed' => 0, 'pending' => 0];
$tickets = $recent_tickets ?? [];
$tiles   = [
  ['Total tickets', (int) ($kpi['total'] ?? 0),   'inbox',        'brand'],
  ['Open',          (int) ($kpi['open'] ?? 0),     'alert-circle', 'info'],
  ['Pending',       (int) ($kpi['pending'] ?? 0),  'clock',        'warning'],
  ['Closed',        (int) ($kpi['closed'] ?? 0),   'check-circle', 'success'],
];
$badge = function ($s) {
  if ((string) $s === '1') { return '<span class="badge badge-light-info">Open</span>'; }
  if ((string) $s === '2') { return '<span class="badge badge-light-success">Closed</span>'; }
  return '<span class="badge badge-light-warning">Pending</span>';
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
    <h5 class="mb-0">Recent tickets</h5>
    <a href="<?= site_url('erp/tickets') ?>" class="btn btn-sm btn-outline-primary"><i data-feather="inbox" class="mr-1"></i> All tickets</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Code</th>
            <th>Subject</th>
            <th>Priority</th>
            <th>Status</th>
            <th class="text-right">Created</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No records yet.</td></tr>
        <?php else: foreach ($tickets as $t): ?>
          <tr>
            <td><?= esc($t['ticket_code'] ?? '') ?: '—' ?></td>
            <td><?= esc($t['subject'] ?? '') ?: '—' ?></td>
            <td><?= esc(ucfirst((string) ($t['ticket_priority'] ?? ''))) ?: '—' ?></td>
            <td><?= $badge($t['ticket_status'] ?? '') ?></td>
            <td class="text-right"><?= esc($t['created_at'] ?? '') ?: '—' ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
