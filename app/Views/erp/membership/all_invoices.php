<?php
$db = \Config\Database::connect();

// Stats
$_total_q = $db->query("SELECT COUNT(*) AS c FROM ci_subscription_invoices");
$total_invoices = $_total_q ? (int)($_total_q->getRow()->c ?? 0) : 0;
$_paid_q = $db->query("SELECT COUNT(*) AS c FROM ci_subscription_invoices WHERE status='paid'");
$paid_count = $_paid_q ? (int)($_paid_q->getRow()->c ?? 0) : 0;
$_rev_q = $db->query("SELECT COALESCE(SUM(amount),0) AS s FROM ci_subscription_invoices WHERE status='paid'");
$total_revenue = $_rev_q ? (float)($_rev_q->getRow()->s ?? 0) : 0;
$pending_count = $total_invoices - $paid_count;
?>

<!-- Stats Row -->
<div class="row mb-3">
  <div class="col-md-3 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0"><?= $total_invoices; ?></h4>
        <small class="text-muted">Total Invoices</small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0 text-success"><?= $paid_count; ?></h4>
        <small class="text-muted">Paid</small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0 text-warning"><?= $pending_count; ?></h4>
        <small class="text-muted">Pending</small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0 text-primary">UGX <?= number_format($total_revenue, 0); ?></h4>
        <small class="text-muted">Total Revenue</small>
      </div>
    </div>
  </div>
</div>

<!-- Invoices Table -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">All Subscription Invoices</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="xin_table" class="table table-hover">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Company</th>
            <th>Date</th>
            <th>Plan</th>
            <th>Amount (UGX)</th>
            <th>Payment Method</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
