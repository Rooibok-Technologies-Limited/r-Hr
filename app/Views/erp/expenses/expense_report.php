<?php
use App\Models\SystemModel;
use App\Models\RolesModel;
use App\Models\UsersModel;
use App\Models\ExpenseCategoryModel;

$session = \Config\Services::session();
$usession = $session->get('sup_username');

$UsersModel = new UsersModel();
$RolesModel = new RolesModel();
$SystemModel = new SystemModel();
$ExpenseCategoryModel = new ExpenseCategoryModel();
$xin_system = erp_company_settings();
$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

if($user_info['user_type'] == 'staff'){
	$company_id = $user_info['company_id'];
} else {
	$company_id = $usession['sup_user_id'];
}
$categories = $ExpenseCategoryModel->where('company_id', $company_id)->where('is_active', 1)->findAll();
$staff_list = ($user_info['user_type'] == 'company') ? $UsersModel->where('company_id', $company_id)->where('user_type','staff')->findAll() : [];
?>

<div id="smartwizard-2" class="border-bottom smartwizard-example sw-main sw-theme-default mt-2">
  <ul class="nav nav-tabs step-anchor">
    <li class="nav-item clickable"> <a href="<?= site_url('erp/expenses');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon feather icon-file-text"></span>
      Expense Claims
      <div class="text-muted small">Manage Expenses</div>
      </a> </li>
    <?php if($user_info['user_type'] == 'company') {?>
    <li class="nav-item clickable"> <a href="<?= site_url('erp/expense-categories');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon fas fa-tags"></span>
      Categories
      <div class="text-muted small">Manage Categories</div>
      </a> </li>
    <?php } ?>
    <li class="nav-item active"> <a href="<?= site_url('erp/expense-report');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon fas fa-chart-bar"></span>
      Report
      <div class="text-muted small">Expense Report</div>
      </a> </li>
  </ul>
</div>
<hr class="border-light m-0 mb-3">

<!-- Summary Cards -->
<div class="row mb-3">
  <div class="col-md-4">
    <div class="card bg-primary text-white">
      <div class="card-body text-center">
        <h6>Total Expenses</h6>
        <h4><?= number_to_currency($total_amount, $xin_system['default_currency'], null, 2)?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card bg-success text-white">
      <div class="card-body text-center">
        <h6>Approved</h6>
        <h4><?= number_to_currency($total_approved, $xin_system['default_currency'], null, 2)?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card bg-warning text-white">
      <div class="card-body text-center">
        <h6>Pending</h6>
        <h4><?= number_to_currency($total_pending, $xin_system['default_currency'], null, 2)?></h4>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-header"><h5>Filters</h5></div>
  <div class="card-body">
    <form method="get" action="<?= site_url('erp/expense-report')?>">
      <div class="row">
        <?php if($user_info['user_type'] == 'company'){ ?>
        <div class="col-md-3">
          <div class="form-group">
            <label>Employee</label>
            <select name="employee_id" class="form-control" data-plugin="select_hrm" data-placeholder="All Employees">
              <option value="">All Employees</option>
              <?php foreach($staff_list as $staff) {?>
              <option value="<?= $staff['user_id']?>" <?= ($filter_employee == $staff['user_id']) ? 'selected' : ''?>><?= $staff['first_name'].' '.$staff['last_name']?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <?php } ?>
        <div class="col-md-3">
          <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control" data-plugin="select_hrm" data-placeholder="All Categories">
              <option value="">All Categories</option>
              <?php foreach($categories as $cat) {?>
              <option value="<?= $cat['category_id']?>" <?= ($filter_category == $cat['category_id']) ? 'selected' : ''?>><?= $cat['category_name']?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Month</label>
            <input type="month" name="month" class="form-control" value="<?= $filter_month?>">
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
              <option value="">All</option>
              <option value="pending" <?= ($filter_status == 'pending') ? 'selected' : ''?>>Pending</option>
              <option value="approved" <?= ($filter_status == 'approved') ? 'selected' : ''?>>Approved</option>
              <option value="rejected" <?= ($filter_status == 'rejected') ? 'selected' : ''?>>Rejected</option>
            </select>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>&nbsp;</label><br>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="<?= site_url('erp/expense-report')?>" class="btn btn-light btn-sm">Reset</a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Report Table -->
<div class="card user-profile-list">
  <div class="card-header"><h5>Expense Report</h5></div>
  <div class="card-body">
    <div class="box-datatable table-responsive">
      <table class="table table-striped table-bordered" id="report_table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Employee</th>
            <th>Category</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  var reportData = <?= $report_data ?>;
  $('#report_table').DataTable({
    data: reportData.data,
    order: [[0, 'desc']],
    dom: 'Bfrtip',
    buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
  });
});
</script>
