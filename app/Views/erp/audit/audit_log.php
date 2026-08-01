<!-- Audit Log — super-admin read-only compliance trail (ROADMAP F1) -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="feather icon-shield mr-1"></i> Audit Log</h5>
    <div>
      <button type="button" id="audit-verify" class="btn btn-outline-secondary btn-sm">
        <i class="feather icon-check-circle mr-1"></i> Verify integrity
      </button>
      <a href="<?= site_url('erp/audit-log/export'); ?>" id="audit-export" class="btn btn-outline-primary btn-sm">
        <i class="feather icon-download mr-1"></i> Export CSV
      </a>
    </div>
  </div>

  <div class="card-body">
    <!-- Filters -->
    <div class="row mb-3">
      <div class="col-md-3 col-6">
        <label class="small text-muted">Action</label>
        <select id="f-action" class="form-control form-control-sm">
          <option value="">All actions</option>
          <?php foreach (($actions ?? []) as $a): ?>
            <option value="<?= esc($a, 'attr'); ?>"><?= esc($a); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-6">
        <label class="small text-muted">Actor user id</label>
        <input id="f-actor" type="text" class="form-control form-control-sm" placeholder="e.g. 1">
      </div>
      <div class="col-md-2 col-6">
        <label class="small text-muted">From</label>
        <input id="f-from" type="date" class="form-control form-control-sm">
      </div>
      <div class="col-md-2 col-6">
        <label class="small text-muted">To</label>
        <input id="f-to" type="date" class="form-control form-control-sm">
      </div>
      <div class="col-md-3 col-12">
        <label class="small text-muted">Search (summary / entity)</label>
        <input id="f-q" type="text" class="form-control form-control-sm" placeholder="Search…">
      </div>
    </div>

    <div id="audit-integrity" class="alert d-none mb-3" role="alert"></div>

    <div class="table-responsive">
      <table id="audit_table" class="table table-hover w-100">
        <thead>
          <tr>
            <th>When</th>
            <th>Action</th>
            <th>Actor</th>
            <th>Entity</th>
            <th>Summary</th>
            <th>IP</th>
          </tr>
        </thead>
      </table>
    </div>
    <small class="text-muted">Showing the most recent 2,000 matching entries. Use filters or CSV export for more.</small>
  </div>
</div>

<script>
(function () {
  var base = '<?= site_url('erp/audit-log'); ?>';

  function params() {
    return {
      action: document.getElementById('f-action').value,
      actor_user_id: document.getElementById('f-actor').value,
      date_from: document.getElementById('f-from').value,
      date_to: document.getElementById('f-to').value,
      q: document.getElementById('f-q').value
    };
  }
  function qs(p) {
    return Object.keys(p).filter(function (k) { return p[k] !== ''; })
      .map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(p[k]); }).join('&');
  }

  var table = jQuery('#audit_table').DataTable({
    order: [[0, 'desc']],
    pageLength: 25,
    ajax: {
      url: base + '/data',
      data: function (d) { jQuery.extend(d, params()); }
    }
  });

  // Re-query on any filter change.
  ['f-action', 'f-actor', 'f-from', 'f-to', 'f-q'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', function () { table.ajax.reload(); });
    document.getElementById(id).addEventListener('keyup', function () { table.ajax.reload(); });
  });

  // CSV export carries the current filters.
  document.getElementById('audit-export').addEventListener('click', function (e) {
    e.preventDefault();
    window.location = base + '/export?' + qs(params());
  });

  // Hash-chain verification.
  document.getElementById('audit-verify').addEventListener('click', function () {
    var box = document.getElementById('audit-integrity');
    box.className = 'alert alert-info mb-3';
    box.textContent = 'Verifying…';
    jQuery.getJSON(base + '/verify', function (r) {
      if (r.ok) {
        box.className = 'alert alert-success mb-3';
        box.textContent = '✓ Chain intact — ' + r.checked + ' entries verified, no tampering detected.';
      } else {
        box.className = 'alert alert-danger mb-3';
        box.textContent = '✗ Chain broken at entry #' + r.broken_at + ' (after ' + r.checked + ' good entries). Records may have been altered.';
      }
    });
  });
})();
</script>
