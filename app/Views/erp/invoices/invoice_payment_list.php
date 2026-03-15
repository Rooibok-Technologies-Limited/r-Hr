<?php
$db = \Config\Database::connect();

$_total_q = $db->query("SELECT COUNT(*) AS c FROM ci_finance_membership_invoices");
$total = $_total_q ? (int)($_total_q->getRow()->c ?? 0) : 0;
$_rev_q = $db->query("SELECT COALESCE(SUM(membership_price),0) AS s FROM ci_finance_membership_invoices");
$total_revenue = $_rev_q ? (float)($_rev_q->getRow()->s ?? 0) : 0;
?>

<!-- Stats Row -->
<div class="row mb-3">
  <div class="col-md-4 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0"><?= $total; ?></h4>
        <small class="text-muted">Total Payments</small>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0 text-success">UGX <?= number_format($total_revenue, 0); ?></h4>
        <small class="text-muted">Total Collected</small>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-12">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0 text-primary"><?= $total > 0 ? 'UGX '.number_format($total_revenue / $total, 0) : '0'; ?></h4>
        <small class="text-muted">Average Payment</small>
      </div>
    </div>
  </div>
</div>

<!-- Payment History Table -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><?= lang('Invoices.xin_billing_invoices'); ?></h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="xin_table" class="table table-hover">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Company</th>
            <th>Plan</th>
            <th>Amount (UGX)</th>
            <th>Payment Method</th>
            <th>Date</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
