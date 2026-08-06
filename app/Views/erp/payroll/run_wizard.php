<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Payroll run wizard (ADR-001): period → preview → generate → approve → disburse.
 * Talks to Erp\PayrollRun JSON endpoints via fetch. The disburse step hands off
 * to the disbursement dashboard where the money maker-checker lives.
 */
$currency = $currency ?? erp_currency_symbol();
$period   = $default_period ?? date('Y-m');
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><i data-feather="dollar-sign"></i> Payroll run</h5>
        <span class="text-muted small">Period → preview → approve → disburse</span>
      </div>
      <div class="card-body">
        <?= csrf_field() ?>
        <div class="row align-items-end">
          <div class="col-md-4"><div class="form-group mb-2">
            <label class="form-label">Payroll period <span class="text-danger">*</span></label>
            <input type="month" class="form-control" id="pr-period" value="<?= esc($period) ?>">
          </div></div>
          <div class="col-md-4"><div class="form-group mb-2">
            <button class="btn btn-primary" id="pr-preview"><i data-feather="eye"></i> Preview</button>
          </div></div>
        </div>
        <div id="pr-msg" class="small mt-1"></div>
      </div>
    </div>
  </div>
</div>

<!-- Totals -->
<div class="row d-none" id="pr-kpis">
  <div class="col-md-3 col-6"><div class="card"><div class="card-body py-3">
    <h6 class="text-muted mb-1">Employees</h6><h4 class="mb-0" id="pr-k-emp">0</h4></div></div></div>
  <div class="col-md-3 col-6"><div class="card"><div class="card-body py-3">
    <h6 class="text-muted mb-1">Gross</h6><h4 class="mb-0" id="pr-k-gross">0</h4></div></div></div>
  <div class="col-md-3 col-6"><div class="card"><div class="card-body py-3">
    <h6 class="text-muted mb-1">Deductions</h6><h4 class="mb-0" id="pr-k-ded">0</h4></div></div></div>
  <div class="col-md-3 col-6"><div class="card"><div class="card-body py-3">
    <h6 class="text-muted mb-1">Net payable</h6><h4 class="mb-0 text-success" id="pr-k-net">0</h4></div></div></div>
</div>

<!-- Preview + actions -->
<div class="card d-none" id="pr-preview-card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Preview <small class="text-muted" id="pr-preview-period"></small></h5>
    <div id="pr-actions"></div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-bordered mb-0">
        <thead><tr>
          <th>Employee</th>
          <th class="text-right">Basic</th>
          <th class="text-right">Allowances</th>
          <th class="text-right">PAYE</th>
          <th class="text-right">NSSF</th>
          <th class="text-right">Other stat.</th>
          <th class="text-right">Net</th>
          <th></th>
        </tr></thead>
        <tbody id="pr-rows"><tr><td colspan="8" class="text-center text-muted">Choose a period and preview.</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Recent runs -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Recent runs</h5>
    <button class="btn btn-sm btn-outline-primary" id="pr-refresh"><i data-feather="refresh-cw"></i> Refresh</button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered mb-0">
        <thead><tr><th>Period</th><th class="text-right">Employees</th><th class="text-right">Net</th><th>Status</th><th>Batch</th></tr></thead>
        <tbody id="pr-run-rows"><tr><td colspan="5" class="text-center text-muted">Loading…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  var base = '<?= site_url('erp/payroll-run') ?>';
  var cur  = '<?= esc($currency) ?>';
  var csrf = document.querySelector('input[name="csrf_token"]').value;
  var current = { period: null, run_key: null, status: null };

  function fmt(n) { return cur + ' ' + Number(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
  function badge(s) {
    var m = {draft:'secondary', approved:'info', disbursing:'warning', completed:'success', cancelled:'danger'};
    return '<span class="badge badge-light-' + (m[s] || 'secondary') + '">' + esc(s) + '</span>';
  }
  function toast(kind, msg) { if (window.toastr) toastr[kind](msg); }
  function post(url, payload) {
    var fd = new FormData(); fd.append('csrf_token', csrf);
    Object.keys(payload || {}).forEach(function (k) { fd.append(k, payload[k]); });
    return fetch(url, {method:'POST', body:fd, headers:{'X-CSRF-TOKEN':csrf, 'X-Requested-With':'XMLHttpRequest'}})
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.csrf_hash) { csrf = j.csrf_hash; document.querySelector('input[name="csrf_token"]').value = j.csrf_hash; } return j; });
  }
  function draw() { if (window.feather) feather.replace(); }

  function renderActions() {
    var el = document.getElementById('pr-actions');
    var s = current.status;
    if (!current.run_key) {
      el.innerHTML = '<button class="btn btn-sm btn-success" id="pr-generate"><i data-feather="check"></i> Generate run</button>';
      document.getElementById('pr-generate').addEventListener('click', doGenerate);
    } else if (s === 'draft') {
      el.innerHTML = '<button class="btn btn-sm btn-info" id="pr-approve"><i data-feather="thumbs-up"></i> Approve</button> '
                   + '<button class="btn btn-sm btn-outline-danger" id="pr-cancel">Cancel</button>';
      document.getElementById('pr-approve').addEventListener('click', doApprove);
      document.getElementById('pr-cancel').addEventListener('click', doCancel);
    } else if (s === 'approved') {
      el.innerHTML = '<button class="btn btn-sm btn-primary" id="pr-disburse"><i data-feather="send"></i> Disburse</button>';
      document.getElementById('pr-disburse').addEventListener('click', doDisburse);
    } else {
      el.innerHTML = badge(s);
    }
    draw();
  }

  function doPreview() {
    var period = document.getElementById('pr-period').value.trim();
    if (!period) { toast('warning', 'Pick a period'); return; }
    post(base + '/preview', {period: period}).then(function (j) {
      if (j.error) { document.getElementById('pr-msg').innerHTML = '<span class="text-danger">' + esc(j.error) + '</span>'; return; }
      document.getElementById('pr-msg').innerHTML = '';
      current.period = j.period;
      current.run_key = j.run ? j.run.run_key : null;
      current.status  = j.run ? j.run.status : null;
      document.getElementById('pr-preview-period').textContent = j.period;
      document.getElementById('pr-kpis').classList.remove('d-none');
      document.getElementById('pr-preview-card').classList.remove('d-none');
      document.getElementById('pr-k-emp').textContent = j.totals.employees;
      document.getElementById('pr-k-gross').textContent = fmt(j.totals.gross);
      document.getElementById('pr-k-ded').textContent = fmt(j.totals.deductions);
      document.getElementById('pr-k-net').textContent = fmt(j.totals.net);
      var tb = document.getElementById('pr-rows');
      tb.innerHTML = j.rows.length ? j.rows.map(function (r) {
        return '<tr><td>' + esc(r.name) + '<br><small class="text-muted">' + esc(r.email) + '</small></td>'
             + '<td class="text-right">' + fmt(r.basic) + '</td><td class="text-right">' + fmt(r.allowances) + '</td>'
             + '<td class="text-right">' + fmt(r.paye) + '</td><td class="text-right">' + fmt(r.nssf) + '</td>'
             + '<td class="text-right">' + fmt(r.statutory) + '</td><td class="text-right font-weight-bold">' + fmt(r.net) + '</td>'
             + '<td>' + (r.already ? '<span class="badge badge-light-success">paid</span>' : '') + '</td></tr>';
      }).join('') : '<tr><td colspan="8" class="text-center text-muted">No payable employees for this period.</td></tr>';
      if (current.run_key) { document.getElementById('pr-msg').innerHTML = '<span class="text-info">A run already exists for ' + esc(j.period) + ' (' + esc(current.status) + ').</span>'; }
      renderActions();
    });
  }

  function doGenerate() {
    if (!confirm('Generate payslips for ' + current.period + '? This creates the run.')) return;
    post(base + '/generate', {period: current.period}).then(function (j) {
      if (j.error) { toast('error', j.error); return; }
      current.run_key = j.run_key; current.status = 'draft';
      toast('success', 'Run generated — ' + j.count + ' payslips, ' + fmt(j.net));
      renderActions(); loadRuns();
    });
  }
  function doApprove() {
    post(base + '/approve', {run_key: current.run_key}).then(function (j) {
      if (j.error) { toast('error', j.error); return; }
      current.status = 'approved'; toast('success', 'Run approved'); renderActions(); loadRuns();
    });
  }
  function doCancel() {
    if (!confirm('Cancel this draft run?')) return;
    post(base + '/cancel', {run_key: current.run_key}).then(function (j) {
      if (j.error) { toast('error', j.error); return; }
      current.run_key = null; current.status = null; toast('success', 'Run cancelled'); renderActions(); loadRuns();
    });
  }
  function doDisburse() {
    if (!confirm('Send ' + current.period + ' to disbursement? You will approve the money batch on the next screen.')) return;
    post(base + '/disburse', {run_key: current.run_key}).then(function (j) {
      if (j.error) { toast('error', j.error); return; }
      current.status = 'disbursing'; toast('success', 'Sent to disbursement'); renderActions(); loadRuns();
      if (j.redirect) { setTimeout(function () { window.location = j.redirect; }, 900); }
    });
  }

  function loadRuns() {
    fetch(base + '/list', {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function (r) { return r.json(); })
      .then(function (j) {
        var tb = document.getElementById('pr-run-rows');
        var runs = j.runs || [];
        tb.innerHTML = runs.length ? runs.map(function (x) {
          return '<tr><td>' + esc(x.period) + '</td><td class="text-right">' + esc(x.employee_count) + '</td>'
               + '<td class="text-right">' + fmt(x.net_total) + '</td><td>' + badge(x.status) + '</td>'
               + '<td>' + (x.batch_id ? '#' + esc(x.batch_id) : '—') + '</td></tr>';
        }).join('') : '<tr><td colspan="5" class="text-center text-muted">No runs yet.</td></tr>';
      });
  }

  document.getElementById('pr-preview').addEventListener('click', doPreview);
  document.getElementById('pr-refresh').addEventListener('click', loadRuns);
  loadRuns(); draw();
})();
</script>
