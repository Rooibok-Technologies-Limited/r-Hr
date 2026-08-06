<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Disbursement batch dashboard (ROADMAP F2): build a payroll batch, walk it
 * through maker-checker (approve → process) and reconcile, with per-line drill-in.
 * Talks to the Erp\Disbursements JSON endpoints via fetch.
 */
$caps = $caps ?? ['per_txn' => 0, 'per_run' => 0, 'per_day' => 0];
$cap  = function ($v) { return $v > 0 ? number_format($v) : '—'; };
?>
<div class="row">
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h6 class="text-muted">Safety caps (<?= erp_currency_symbol();?>)</h6>
      <div class="d-flex justify-content-between"><span>Per transaction</span><strong><?= $cap($caps['per_txn']) ?></strong></div>
      <div class="d-flex justify-content-between"><span>Per run</span><strong><?= $cap($caps['per_run']) ?></strong></div>
      <div class="d-flex justify-content-between"><span>Per day</span><strong><?= $cap($caps['per_day']) ?></strong></div>
      <small class="text-muted">0/— = unlimited. Set in Super Admin → Settings.</small>
    </div></div>
  </div>
  <div class="col-md-8">
    <div class="card"><div class="card-header"><h5>Build payroll batch</h5></div>
      <div class="card-body">
        <?= csrf_field() ?>
        <div class="row align-items-end">
          <div class="col-md-4"><div class="form-group">
            <label class="form-label">Payroll period <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="d-period" placeholder="e.g. 2026-07">
          </div></div>
          <div class="col-md-4"><div class="form-group">
            <label class="form-label">Company ID (optional)</label>
            <input type="number" class="form-control" id="d-company" placeholder="all companies">
          </div></div>
          <div class="col-md-4"><div class="form-group">
            <button class="btn btn-primary btn-block" id="d-build"><i data-feather="plus"></i> Build batch</button>
          </div></div>
        </div>
        <div id="d-build-result" class="small"></div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Batches</h5>
    <button class="btn btn-sm btn-outline-primary" id="d-refresh"><i data-feather="refresh-cw"></i> Refresh</button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead><tr><th>#</th><th>Period</th><th>Source</th><th class="text-right">Count</th><th class="text-right">Total</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="d-rows"><tr><td colspan="7" class="text-center text-muted">Loading…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<!-- line detail -->
<div class="card d-none" id="d-detail-card">
  <div class="card-header"><h5 class="mb-0">Batch <span id="d-detail-id"></span> — lines</h5></div>
  <div class="card-body"><div class="table-responsive">
    <table class="table table-sm table-bordered">
      <thead><tr><th>Employee</th><th>Type</th><th class="text-right">Amount</th><th class="text-right">Fee</th><th>Status</th><th>Reference</th><th>Reason</th></tr></thead>
      <tbody id="d-detail-rows"></tbody>
    </table>
  </div></div>
</div>

<script>
(function () {
  var base = '<?= site_url('erp/disbursements') ?>';
  var csrf = document.querySelector('input[name="csrf_token"]').value;
  function fmt(n) { return Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
  function badge(s) {
    var m = {draft: 'secondary', approved: 'info', processing: 'warning', completed: 'success', failed: 'danger',
             created: 'secondary', pending: 'warning', successful: 'success'};
    return '<span class="badge badge-light-' + (m[s] || 'secondary') + '">' + esc(s) + '</span>';
  }
  function post(url, payload) {
    var fd = new FormData(); fd.append('csrf_token', csrf);
    Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });
    return fetch(url, {method: 'POST', body: fd, headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'}})
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.csrf_hash) { csrf = j.csrf_hash; document.querySelector('input[name="csrf_token"]').value = j.csrf_hash; } return j; });
  }

  function actionBtns(b) {
    var out = '<button class="btn btn-sm btn-outline-secondary" data-act="lines" data-id="' + b.batch_id + '">Lines</button> ';
    if (b.status === 'draft')     out += '<button class="btn btn-sm btn-info" data-act="approve" data-id="' + b.batch_id + '">Approve</button> ';
    if (b.status === 'approved' || b.status === 'processing') out += '<button class="btn btn-sm btn-primary" data-act="process" data-id="' + b.batch_id + '" data-total="' + (b.total_amount||0) + '" data-count="' + (b.total_count||0) + '">Process</button> ';
    out += '<button class="btn btn-sm btn-outline-warning" data-act="reconcile" data-id="' + b.batch_id + '">Reconcile</button>';
    return out;
  }

  function loadBatches() {
    fetch(base + '/list', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var tb = document.getElementById('d-rows');
        if (!j.ok) { tb.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Access denied</td></tr>'; return; }
        if (!j.batches.length) { tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No batches yet</td></tr>'; return; }
        tb.innerHTML = j.batches.map(function (b) {
          return '<tr><td>' + esc(b.batch_id) + '</td><td>' + esc(b.reference_period || '—') + '</td><td>' + esc(b.source) + '</td>'
               + '<td class="text-right">' + esc(b.total_count) + '</td><td class="text-right">' + fmt(b.total_amount) + '</td>'
               + '<td>' + badge(b.status) + '</td><td>' + actionBtns(b) + '</td></tr>';
        }).join('');
      });
  }

  function loadLines(id) {
    fetch(base + '/lines?batch_id=' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function (r) { return r.json(); })
      .then(function (j) {
        document.getElementById('d-detail-card').classList.remove('d-none');
        document.getElementById('d-detail-id').textContent = '#' + id;
        var tb = document.getElementById('d-detail-rows');
        tb.innerHTML = (j.lines || []).map(function (l) {
          return '<tr><td>' + esc(l.employee_id) + '</td><td>' + esc(l.type) + '</td><td class="text-right">' + fmt(l.amount) + '</td>'
               + '<td class="text-right">' + fmt(l.fee || 0) + '</td><td>' + badge(l.status) + '</td>'
               + '<td><small>' + esc(l.reference) + '</small></td><td><small class="text-danger">' + esc(l.failure_reason) + '</small></td></tr>';
        }).join('');
        document.getElementById('d-detail-card').scrollIntoView({behavior: 'smooth'});
      });
  }

  document.getElementById('d-build').addEventListener('click', function () {
    var period = document.getElementById('d-period').value.trim();
    if (!period) { toastr.warning('Enter a payroll period'); return; }
    var p = {period: period};
    var c = document.getElementById('d-company').value; if (c) p.company_id = c;
    post(base + '/build-payroll', p).then(function (j) {
      var el = document.getElementById('d-build-result');
      if (j.ok) { el.innerHTML = '<span class="text-success">Built batch #' + j.batch_id + ' — ' + j.count + ' lines, <?= erp_currency_symbol();?> ' + fmt(j.total) + '</span>'; toastr.success('Batch built'); loadBatches(); }
      else { el.innerHTML = '<span class="text-danger">' + (j.reason || 'Failed') + '</span>'; toastr.error(j.reason || 'Failed'); }
    });
  });

  document.getElementById('d-refresh').addEventListener('click', loadBatches);

  document.getElementById('d-rows').addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-act]'); if (!btn) return;
    var id = btn.getAttribute('data-id'), act = btn.getAttribute('data-act');
    if (act === 'lines') { loadLines(id); return; }
    if (act === 'approve' && !confirm('Approve batch #' + id + '? (maker-checker: you must not be the preparer)')) return;
    // S9 — disbursement moves real money and cannot be reversed: state the consequence
    // (actual amount + recipient count) and require typed confirmation.
    if (act === 'process') {
      var cur = '<?= erp_currency_symbol();?>';
      var total = fmt(parseFloat(btn.getAttribute('data-total') || '0'));
      var count = btn.getAttribute('data-count') || '0';
      var typed = window.prompt('Disburse ' + cur + ' ' + total + ' to ' + count +
        ' employee(s) from batch #' + id + '.\nThis moves real money and CANNOT be reversed.\n\nType DISBURSE to confirm:');
      if (typed === null) return;                      // cancelled
      if (typed.trim().toUpperCase() !== 'DISBURSE') { toastr.warning('Not confirmed — you must type DISBURSE.'); return; }
    }
    btn.disabled = true;                               // UI idempotency: no second click
    post(base + '/' + act, {batch_id: id}).then(function (j) {
      if (j.ok) { toastr.success(act + ' ok'); loadBatches(); }
      else { toastr.error(j.reason || (act + ' failed')); btn.disabled = false; }
    }).catch(function(){ btn.disabled = false; });
  });

  loadBatches();
  if (window.feather) feather.replace();
})();
</script>
