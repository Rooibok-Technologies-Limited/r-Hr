<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Company self-service wallet (ROADMAP F2, ADR-002): available/reserved balance,
 * top-up (online hosted checkout when configured, else manual record), and the
 * ledger statement. Talks to the Erp\Wallet JSON endpoints via fetch.
 */
$onlineFund = ! empty($online_fund);
$isSuper    = ! empty($is_super);
?>
<div class="row">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <h6 class="text-muted mb-2">Available balance</h6>
        <h2 class="mb-0"><span id="w-currency"><?= erp_currency_symbol();?></span> <span id="w-balance">—</span></h2>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <h6 class="text-muted mb-2">Reserved (in-flight payouts)</h6>
        <h2 class="mb-0 text-warning"><span class="w-currency2"><?= erp_currency_symbol();?></span> <span id="w-reserved">—</span></h2>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <h6 class="text-muted mb-2">Total held</h6>
        <h2 class="mb-0 text-primary"><span class="w-currency2"><?= erp_currency_symbol();?></span> <span id="w-total">—</span></h2>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><h5>Top up wallet</h5></div>
      <div class="card-body">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">Amount (<?= erp_currency_symbol();?>) <span class="text-danger">*</span></label>
          <input type="number" min="1" step="0.01" class="form-control" id="topup-amount" placeholder="e.g. 500000">
        </div>
        <?php if ($isSuper): ?>
        <div class="form-group">
          <label class="form-label">Company ID (super-admin)</label>
          <input type="number" min="1" class="form-control" id="topup-company" placeholder="leave blank for your own">
        </div>
        <?php endif; ?>
        <?php if ($onlineFund): ?>
          <button class="btn btn-primary" id="btn-fund"><i data-feather="credit-card"></i> Pay online</button>
        <?php endif; ?>
        <?php if ($isSuper): ?>
          <button class="btn btn-outline-secondary" id="btn-manual"><i data-feather="edit-3"></i> Record manual top-up</button>
          <small class="d-block text-muted mt-2">Manual top-up records a finance-confirmed offline deposit (super-admin only).</small>
        <?php elseif (! $onlineFund): ?>
          <small class="d-block text-muted mt-2">Online funding is not configured yet. Contact Rooibok finance to fund your wallet.</small>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><h5>Statement</h5></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-bordered">
            <thead><tr><th>When</th><th>Type</th><th class="text-right">Amount</th><th class="text-right">Balance after</th><th>Reference</th></tr></thead>
            <tbody id="w-ledger"><tr><td colspan="5" class="text-center text-muted">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var base = '<?= site_url('erp/wallet') ?>';
  var csrf = document.querySelector('input[name="csrf_token"]').value;
  var isSuper = <?= $isSuper ? 'true' : 'false' ?>;

  function companyParam() {
    if (!isSuper) return '';
    var c = document.getElementById('topup-company');
    return (c && c.value) ? ('?company_id=' + encodeURIComponent(c.value)) : '';
  }
  function fmt(n) { return Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

  function loadBalance() {
    fetch(base + '/balance' + companyParam(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        var w = j.wallet;
        document.getElementById('w-balance').textContent = fmt(w.balance);
        document.getElementById('w-reserved').textContent = fmt(w.reserved);
        document.getElementById('w-total').textContent = fmt(w.balance + w.reserved);
        document.getElementById('w-currency').textContent = w.currency;
        document.querySelectorAll('.w-currency2').forEach(function (e) { e.textContent = w.currency; });
      });
  }
  function loadLedger() {
    fetch(base + '/statement' + companyParam(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var tb = document.getElementById('w-ledger');
        if (!j.ok || !j.transactions.length) { tb.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No transactions yet</td></tr>'; return; }
        tb.innerHTML = j.transactions.map(function (t) {
          var amt = Number(t.amount);
          var cls = amt < 0 ? 'text-danger' : 'text-success';
          return '<tr><td>' + esc(t.created_at) + '</td><td><span class="badge badge-light-primary">' + esc(t.type) + '</span></td>'
               + '<td class="text-right ' + cls + '">' + fmt(amt) + '</td>'
               + '<td class="text-right">' + fmt(t.balance_after) + '</td>'
               + '<td><small>' + esc(t.reference) + '</small></td></tr>';
        }).join('');
      });
  }
  function post(url, payload) {
    var fd = new FormData();
    fd.append('csrf_token', csrf);
    Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });
    return fetch(url, {method: 'POST', body: fd, headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'}})
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.csrf_hash) { csrf = j.csrf_hash; document.querySelector('input[name="csrf_token"]').value = j.csrf_hash; } return j; });
  }

  var fundBtn = document.getElementById('btn-fund');
  if (fundBtn) fundBtn.addEventListener('click', function () {
    var amt = document.getElementById('topup-amount').value;
    if (!amt || amt <= 0) { toastr.warning('Enter an amount'); return; }
    var p = {amount: amt};
    var c = document.getElementById('topup-company'); if (c && c.value) p.company_id = c.value;
    post(base + '/fund', p).then(function (j) {
      if (j.ok && j.link) { window.location = j.link; } else { toastr.error(j.reason || 'Could not start payment'); }
    });
  });

  document.getElementById('btn-manual').addEventListener('click', function () {
    var amt = document.getElementById('topup-amount').value;
    if (!amt || amt <= 0) { toastr.warning('Enter an amount'); return; }
    var p = {amount: amt, description: 'Manual top-up'};
    var c = document.getElementById('topup-company'); if (c && c.value) p.company_id = c.value;
    post(base + '/topup', p).then(function (j) {
      if (j.ok) { toastr.success('Wallet credited'); loadBalance(); loadLedger(); } else { toastr.error(j.reason || 'Failed'); }
    });
  });

  loadBalance();
  loadLedger();
  if (window.feather) feather.replace();
})();
</script>
