<?php
/**
 * Phase 10.7 — Archived Company Detail (tabbed view)
 */
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-briefcase"></i> Archive: <?= esc($snapshot['company_name']); ?></h5>
        <div class="card-header-right">
          <a href="<?= site_url('erp/archive/companies'); ?>" class="btn btn-sm btn-light-primary"><i class="feather icon-arrow-left"></i> Back</a>
          <?php if (!empty($snapshot['vault_bundle_path'])): ?>
          <a href="<?= site_url('erp/archive/download/' . $snapshot['snapshot_id']); ?>" class="btn btn-sm btn-light-info"><i class="feather icon-download"></i> Download Vault</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-profile">Profile</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-employees">Employees (<?= count($employees); ?>)</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-attendance">Attendance (<?= count($attendance); ?>)</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-payroll">Payroll (<?= count($payroll); ?>)</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-leaves">Leaves (<?= count($leaves); ?>)</a></li>
        </ul>

        <div class="tab-content mt-3">
          <!-- Profile Tab -->
          <div class="tab-pane fade show active" id="tab-profile">
            <div class="row">
              <div class="col-md-6">
                <table class="table table-bordered">
                  <tr><th width="40%">Company Name</th><td><?= esc($snapshot['company_name']); ?></td></tr>
                  <tr><th>Trading Name</th><td><?= esc($snapshot['trading_name'] ?: '--'); ?></td></tr>
                  <tr><th>Registration No</th><td><?= esc($snapshot['registration_no'] ?: '--'); ?></td></tr>
                  <tr><th>Company Type</th><td><?= esc($snapshot['company_type'] ?: '--'); ?></td></tr>
                  <tr><th>Country</th><td><?= esc($snapshot['country'] ?: '--'); ?></td></tr>
                  <tr><th>City</th><td><?= esc($snapshot['city'] ?: '--'); ?></td></tr>
                  <tr><th>Region</th><td><?= esc($snapshot['region'] ?: '--'); ?></td></tr>
                  <tr><th>Employee Count</th><td><?= $snapshot['employee_count'] ?: '--'; ?></td></tr>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-bordered">
                  <tr><th width="40%">Admin Name</th><td><?= esc($snapshot['admin_first_name'] . ' ' . $snapshot['admin_last_name']); ?></td></tr>
                  <tr><th>Admin Email</th><td><?= esc($snapshot['admin_email']); ?></td></tr>
                  <tr><th>Admin Phone</th><td><?= esc($snapshot['admin_phone'] ?: '--'); ?></td></tr>
                  <tr><th>Plan</th><td><span class="badge badge-info"><?= esc($snapshot['plan_name'] ?: '--'); ?></span> (<?= esc($snapshot['plan_tier'] ?: '--'); ?>)</td></tr>
                  <tr><th>Subscription</th><td><?= $snapshot['subscription_start'] ? date('d M Y', strtotime($snapshot['subscription_start'])) : '--'; ?> &mdash; <?= $snapshot['subscription_end'] ? date('d M Y', strtotime($snapshot['subscription_end'])) : '--'; ?></td></tr>
                  <tr><th>Months Paid</th><td><?= $snapshot['total_months_paid'] ?: '--'; ?></td></tr>
                  <tr><th>Total Revenue</th><td><?= $snapshot['total_revenue_ugx'] ? number_format($snapshot['total_revenue_ugx'], 2) . ' UGX' : '--'; ?></td></tr>
                  <tr><th>Archive Reason</th><td><?= esc($snapshot['archive_reason'] ?: $snapshot['cancellation_reason'] ?: '--'); ?></td></tr>
                  <tr><th>Archived At</th><td><?= date('d M Y H:i', strtotime($snapshot['archived_at'])); ?></td></tr>
                  <?php if (!empty($snapshot['restored_at'])): ?>
                  <tr><th>Restored At</th><td><span class="badge badge-success"><?= date('d M Y H:i', strtotime($snapshot['restored_at'])); ?></span></td></tr>
                  <?php endif; ?>
                </table>
              </div>
            </div>
          </div>

          <!-- Employees Tab -->
          <div class="tab-pane fade" id="tab-employees">
            <div class="table-responsive">
              <table class="table table-striped table-bordered datatable-basic">
                <thead>
                  <tr>
                    <th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Department</th><th>Designation</th><th>Type</th><th>Joined</th><th>Left</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($employees as $i => $e): ?>
                  <tr>
                    <td><?= $i + 1; ?></td>
                    <td><?= esc($e['first_name'] . ' ' . $e['last_name']); ?></td>
                    <td><?= esc($e['email']); ?></td>
                    <td><?= esc($e['phone'] ?: '--'); ?></td>
                    <td><?= esc($e['department'] ?: '--'); ?></td>
                    <td><?= esc($e['designation'] ?: '--'); ?></td>
                    <td><?= esc($e['employment_type'] ?: '--'); ?></td>
                    <td><?= $e['date_joined'] ? date('d M Y', strtotime($e['date_joined'])) : '--'; ?></td>
                    <td><?= $e['date_left'] ? date('d M Y', strtotime($e['date_left'])) : '--'; ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Attendance Tab -->
          <div class="tab-pane fade" id="tab-attendance">
            <div class="table-responsive">
              <table class="table table-striped table-bordered datatable-basic">
                <thead>
                  <tr>
                    <th>#</th><th>Employee</th><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Total Work</th><th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($attendance as $i => $a): ?>
                  <tr>
                    <td><?= $i + 1; ?></td>
                    <td><?= esc($a['employee_name'] ?: '--'); ?></td>
                    <td><?= $a['attendance_date']; ?></td>
                    <td><?= $a['clock_in'] ? date('H:i', strtotime($a['clock_in'])) : '--'; ?></td>
                    <td><?= $a['clock_out'] ? date('H:i', strtotime($a['clock_out'])) : '--'; ?></td>
                    <td><?= esc($a['total_work'] ?: '--'); ?></td>
                    <td><?= esc($a['attendance_status'] ?: '--'); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Payroll Tab -->
          <div class="tab-pane fade" id="tab-payroll">
            <div class="table-responsive">
              <table class="table table-striped table-bordered datatable-basic">
                <thead>
                  <tr>
                    <th>#</th><th>Employee</th><th>Month</th><th>Gross</th><th>PAYE</th><th>NSSF (Emp)</th><th>NSSF (Er)</th><th>Net Pay</th><th>Currency</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($payroll as $i => $p): ?>
                  <tr>
                    <td><?= $i + 1; ?></td>
                    <td><?= esc($p['employee_name'] ?: '--'); ?></td>
                    <td><?= esc($p['payroll_month']); ?></td>
                    <td><?= number_format($p['gross_salary'] ?? 0, 2); ?></td>
                    <td><?= number_format($p['paye_deduction'] ?? 0, 2); ?></td>
                    <td><?= number_format($p['nssf_employee'] ?? 0, 2); ?></td>
                    <td><?= number_format($p['nssf_employer'] ?? 0, 2); ?></td>
                    <td><?= number_format($p['net_pay'] ?? 0, 2); ?></td>
                    <td><?= esc($p['currency'] ?? 'UGX'); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Leaves Tab -->
          <div class="tab-pane fade" id="tab-leaves">
            <div class="table-responsive">
              <table class="table table-striped table-bordered datatable-basic">
                <thead>
                  <tr>
                    <th>#</th><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($leaves as $i => $l): ?>
                  <tr>
                    <td><?= $i + 1; ?></td>
                    <td><?= esc($l['employee_name'] ?: '--'); ?></td>
                    <td><?= esc($l['leave_type'] ?: '--'); ?></td>
                    <td><?= $l['start_date']; ?></td>
                    <td><?= $l['end_date']; ?></td>
                    <td><?= $l['days_taken'] ?: '--'; ?></td>
                    <td><?= esc($l['status'] ?: '--'); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('.datatable-basic').DataTable({"pageLength": 25, "order": []});
});
</script>
