<?php
/**
 * Phase 10.7 — Archive Cross-Table Search
 */
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-search"></i> Archive Search</h5>
        <div class="card-header-right">
          <a href="<?= site_url('erp/archive'); ?>" class="btn btn-sm btn-light-primary"><i class="feather icon-arrow-left"></i> Back</a>
        </div>
      </div>
      <div class="card-body">
        <form method="get" action="<?= site_url('erp/archive/search'); ?>">
          <div class="row">
            <div class="col-md-3 mb-3">
              <label>Company</label>
              <select name="company_id" class="form-control">
                <option value="">All Companies</option>
                <?php foreach ($companies as $c): ?>
                <option value="<?= $c['source_company_id']; ?>" <?= ($f_company_id == $c['source_company_id']) ? 'selected' : ''; ?>><?= esc($c['company_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-3">
              <label>Employee Name</label>
              <input type="text" name="employee_name" class="form-control" value="<?= esc($f_employee); ?>" placeholder="Search name...">
            </div>
            <div class="col-md-2 mb-3">
              <label>Date From</label>
              <input type="date" name="date_from" class="form-control" value="<?= esc($f_date_from); ?>">
            </div>
            <div class="col-md-2 mb-3">
              <label>Date To</label>
              <input type="date" name="date_to" class="form-control" value="<?= esc($f_date_to); ?>">
            </div>
            <div class="col-md-2 mb-3">
              <label>Record Type</label>
              <select name="record_type" class="form-control">
                <option value="all" <?= ($f_record_type == 'all') ? 'selected' : ''; ?>>All Types</option>
                <option value="attendance" <?= ($f_record_type == 'attendance') ? 'selected' : ''; ?>>Attendance</option>
                <option value="payroll" <?= ($f_record_type == 'payroll') ? 'selected' : ''; ?>>Payroll</option>
                <option value="leaves" <?= ($f_record_type == 'leaves') ? 'selected' : ''; ?>>Leaves</option>
                <option value="employees" <?= ($f_record_type == 'employees') ? 'selected' : ''; ?>>Employees</option>
              </select>
            </div>
            <div class="col-md-1 mb-3">
              <label>&nbsp;</label>
              <button type="submit" class="btn btn-primary btn-block"><i class="feather icon-search"></i></button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($has_search): ?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h5>Results (<?= count($results); ?> records found)</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="search-results-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Type</th>
                <th>Company ID</th>
                <th>Name / Employee</th>
                <th>Details</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $i => $r): ?>
              <tr>
                <td><?= $i + 1; ?></td>
                <td><span class="badge badge-<?= ($r['_type'] == 'Attendance') ? 'info' : (($r['_type'] == 'Payroll') ? 'success' : (($r['_type'] == 'Leave') ? 'warning' : 'primary')); ?>"><?= $r['_type']; ?></span></td>
                <td><?= $r['source_company_id']; ?></td>
                <td>
                  <?php
                  if ($r['_type'] == 'Employee') {
                      echo esc(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                  } else {
                      echo esc($r['employee_name'] ?? '--');
                  }
                  ?>
                </td>
                <td>
                  <?php
                  if ($r['_type'] == 'Attendance') {
                      echo 'Status: ' . esc($r['attendance_status'] ?? '--') . ' | Work: ' . esc($r['total_work'] ?? '--');
                  } elseif ($r['_type'] == 'Payroll') {
                      echo 'Month: ' . esc($r['payroll_month'] ?? '--') . ' | Net: ' . number_format($r['net_pay'] ?? 0, 2);
                  } elseif ($r['_type'] == 'Leave') {
                      echo esc($r['leave_type'] ?? '--') . ' | ' . ($r['days_taken'] ?? '--') . ' days | ' . esc($r['status'] ?? '--');
                  } elseif ($r['_type'] == 'Employee') {
                      echo esc($r['department'] ?? '--') . ' / ' . esc($r['designation'] ?? '--') . ' | ' . esc($r['employment_type'] ?? '--');
                  }
                  ?>
                </td>
                <td>
                  <?php
                  if ($r['_type'] == 'Attendance') echo $r['attendance_date'] ?? '--';
                  elseif ($r['_type'] == 'Payroll') echo $r['payroll_month'] ?? '--';
                  elseif ($r['_type'] == 'Leave') echo ($r['start_date'] ?? '--') . ' to ' . ($r['end_date'] ?? '--');
                  elseif ($r['_type'] == 'Employee') echo $r['date_joined'] ?? '--';
                  ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
$(document).ready(function(){ $('#search-results-table').DataTable({"pageLength": 50}); });
</script>
<?php endif; ?>
