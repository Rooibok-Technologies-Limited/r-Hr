<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Super-admin wallet oversight (ROADMAP F2, ADR-002): read-only view of every
 * company's balance, plus master-float reconciliation (internal ledger total vs
 * the aggregator's reported balance).
 */
?>
<div class="row">
  <div class="col-md-4">
    <div class="card"><div class="card-body text-center">
      <h6 class="text-muted mb-2">Companies' available (ledger)</h6>
      <h3 class="mb-0">UGX <span id="o-avail">—</span></h3>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body text-center">
      <h6 class="text-muted mb-2">Reserved (in-flight)</h6>
      <h3 class="mb-0 text-warning">UGX <span id="o-held">—</span></h3>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body text-center">
      <h6 class="text-muted mb-2">Master float (aggregator)</h6>
      <h3 class="mb-0"><span id="o-master">—</span></h3>
      <small id="o-recon" class="d-block mt-1"></small>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Company wallets</h5>
    <button class="btn btn-sm btn-outline-primary" id="o-refresh"><i data-feather="refresh-cw"></i> Refresh</button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead><tr><th>Company ID</th><th>Currency</th><th class="text-right">Available</th><th class="text-right">Reserved</th><th>Status</th></tr></thead>
        <tbody id="o-rows"><tr><td colspan="5" class="text-center text-muted">Loading…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  var base = '<?= site_url('erp/wallet') ?>';
  function fmt(n) { return Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

  function loadCompanies() {
    fetch(base + '/companies', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var tb = document.getElementById('o-rows');
        if (!j.ok) { tb.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Access denied</td></tr>'; return; }
        document.getElementById('o-avail').textContent = fmt(j.total_avail);
        document.getElementById('o-held').textContent = fmt(j.total_held);
        if (!j.wallets.length) { tb.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No wallets yet</td></tr>'; return; }
        tb.innerHTML = j.wallets.map(function (w) {
          return '<tr><td>' + esc(w.company_id) + '</td><td>' + esc(w.currency) + '</td>'
               + '<td class="text-right">' + fmt(w.balance) + '</td>'
               + '<td class="text-right text-warning">' + fmt(w.reserved) + '</td>'
               + '<td><span class="badge badge-light-primary">' + esc(w.status) + '</span></td></tr>';
        }).join('');
      });
  }
  function loadFloat() {
    fetch(base + '/float', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        var m = document.getElementById('o-master');
        var rec = document.getElementById('o-recon');
        if (j.master_float === null) { m.textContent = j.note || 'not configured'; rec.textContent = ''; return; }
        m.textContent = j.currency + ' ' + fmt(j.master_float);
        if (j.reconciled) { rec.className = 'd-block mt-1 text-success'; rec.textContent = '✓ reconciled'; }
        else { rec.className = 'd-block mt-1 text-danger'; rec.textContent = 'Δ ' + fmt(j.difference); }
      });
  }
  document.getElementById('o-refresh').addEventListener('click', function () { loadCompanies(); loadFloat(); });
  loadCompanies();
  loadFloat();
  if (window.feather) feather.replace();
})();
</script>
