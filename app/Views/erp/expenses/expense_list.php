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

$get_animate = '';
?>
<?php if(in_array('expense2',staff_role_resource()) || $user_info['user_type'] == 'company') {?>

<div id="smartwizard-2" class="border-bottom smartwizard-example sw-main sw-theme-default mt-2">
  <ul class="nav nav-tabs step-anchor">
    <li class="nav-item active"> <a href="<?= site_url('erp/expenses');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon feather icon-file-text"></span>
      Expense Claims
      <div class="text-muted small">Manage Expenses</div>
      </a> </li>
    <?php if($user_info['user_type'] == 'company') {?>
    <li class="nav-item clickable"> <a href="<?= site_url('erp/expense-categories');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon fas fa-tags"></span>
      Categories
      <div class="text-muted small">Manage Categories</div>
      </a> </li>
    <?php } ?>
    <li class="nav-item clickable"> <a href="<?= site_url('erp/expense-report');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon fas fa-chart-bar"></span>
      Report
      <div class="text-muted small">Expense Report</div>
      </a> </li>
  </ul>
</div>
<hr class="border-light m-0 mb-3">
<?php } ?>
<div class="row m-b-1 animated fadeInRight">
  <div class="col-md-12">
    <?php if(in_array('expense2',staff_role_resource()) || $user_info['user_type'] == 'company') {?>
    <div id="add_form" class="collapse add-form <?php echo $get_animate;?>" data-parent="#accordion" style="">
      <?php $attributes = array('name' => 'add_expense', 'id' => 'xin-form', 'autocomplete' => 'off');?>
      <?php $hidden = array('_user' => 1);?>
      <?php echo form_open_multipart('erp/expenses/add', $attributes, $hidden);?>
      <div class="row">
        <div class="col-md-12">
          <div class="card mb-2">
            <div id="accordion">
              <div class="card-header">
                <h5>Submit New Expense</h5>
                <div class="card-header-right"> <a  data-toggle="collapse" href="#add_form" aria-expanded="false" class="collapsed btn btn-sm waves-effect waves-light btn-primary m-0"> <i data-feather="minus"></i>
                  <?= lang('Main.xin_hide');?>
                  </a> </div>
              </div>
              <div class="card-body">
                <div class="row">
                  <?php if($user_info['user_type'] == 'company'){?>
                  <?php $staff_info = $UsersModel->where('company_id', $usession['sup_user_id'])->where('user_type','staff')->findAll();?>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="employee_id"><?= lang('Dashboard.dashboard_employee');?> <span class="text-danger">*</span></label>
                      <select class="form-control" name="employee_id" data-plugin="select_hrm" data-placeholder="<?= lang('Dashboard.dashboard_employee');?>">
                        <?php foreach($staff_info as $staff) {?>
                        <option value="<?= $staff['user_id']?>"><?= $staff['first_name'].' '.$staff['last_name'] ?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                  <?php } ?>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="category_id">Category</label>
                      <select name="category_id" class="form-control" data-plugin="select_hrm" data-placeholder="Select Category">
                        <option value="">-- Select --</option>
                        <?php foreach($categories as $cat) {?>
                        <option value="<?= $cat['category_id']?>"><?= $cat['category_name']?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="amount">Amount <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <div class="input-group-append"><span class="input-group-text"><?= $xin_system['default_currency'];?></span></div>
                        <input class="form-control" placeholder="Amount" name="amount" type="number" step="0.01">
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="expense_date">Expense Date <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <input class="form-control date" placeholder="Expense Date" name="expense_date" type="text" value="">
                        <div class="input-group-append"><span class="input-group-text"><i class="fas fa-calendar-alt"></i></span></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group">
                      <label for="description">Description <span class="text-danger">*</span></label>
                      <textarea class="form-control" placeholder="Describe the expense" name="description" cols="30" rows="2" id="description"></textarea>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="receipt">Receipt (JPG, PNG, PDF - max 5MB)</label>
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" name="receipt">
                        <label class="custom-file-label"><?= lang('Main.xin_choose_file');?></label>
                      </div>
                    </div>
                  </div>
                  <input type="hidden" name="currency" value="<?= $xin_system['default_currency'];?>">
                </div>
              </div>
              <div class="card-footer text-right">
                <button type="reset" class="btn btn-light" href="#add_form" data-toggle="collapse" aria-expanded="false"><?= lang('Main.xin_reset');?></button>
                &nbsp;
                <button type="submit" class="btn btn-primary"><?= lang('Main.xin_save');?></button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?= form_close(); ?>
    </div>
    <?php } ?>
    <div class="card user-profile-list <?php echo $get_animate;?>">
      <div class="card-header">
        <h5>Expense Claims</h5>
        <?php if(in_array('expense2',staff_role_resource()) || $user_info['user_type'] == 'company') {?>
        <div class="card-header-right"> <a  data-toggle="collapse" href="#add_form" aria-expanded="false" class="collapsed btn waves-effect waves-light btn-primary btn-sm m-0"> <i data-feather="plus"></i>
          <?= lang('Main.xin_add_new');?>
          </a> </div>
        <?php } ?>
      </div>
      <div class="card-body">
        <div class="box-datatable table-responsive">
          <table class="datatables-demo table table-striped table-bordered" id="xin_table">
            <thead>
              <tr>
                <th>Date</th>
                <th><i class="fa fa-user small"></i> Employee</th>
                <th>Category</th>
                <th>Amount</th>
                <th>Receipt</th>
                <th>Status</th>
                <th width="150">Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade delete-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center">
        <h5>Are you sure you want to delete this expense?</h5>
        <div class="mt-3">
          <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang('Main.xin_cancel');?></button>
          <button type="button" class="btn btn-danger btn-confirm-delete">Delete</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  // approve expense
  $(document).on('click', '.approve-expense', function(){
    var id = $(this).data('record-id');
    if(confirm('Approve this expense claim?')){
      $.ajax({
        url: site_url + '/erp/expenses/approve/',
        type: 'POST',
        data: { type: 'approve_record', _token: id, csrf_token_name: csrf_hash },
        dataType: 'json',
        success: function(data){
          if(data.csrf_hash){ csrf_hash = data.csrf_hash; }
          if(data.result){
            toastr.success(data.result);
            $('#xin_table').DataTable().ajax.reload();
          }
          if(data.error){ toastr.error(data.error); }
        }
      });
    }
  });
  // reject expense
  $(document).on('click', '.reject-expense', function(){
    var id = $(this).data('record-id');
    if(confirm('Reject this expense claim?')){
      $.ajax({
        url: site_url + '/erp/expenses/reject/',
        type: 'POST',
        data: { type: 'reject_record', _token: id, csrf_token_name: csrf_hash },
        dataType: 'json',
        success: function(data){
          if(data.csrf_hash){ csrf_hash = data.csrf_hash; }
          if(data.result){
            toastr.success(data.result);
            $('#xin_table').DataTable().ajax.reload();
          }
          if(data.error){ toastr.error(data.error); }
        }
      });
    }
  });
  // delete expense
  var delete_id = '';
  $(document).on('click', '.delete-expense', function(){
    delete_id = $(this).data('record-id');
  });
  $(document).on('click', '.btn-confirm-delete', function(){
    $.ajax({
      url: site_url + '/erp/expenses/delete/',
      type: 'POST',
      data: { type: 'delete_record', _token: delete_id, csrf_token_name: csrf_hash },
      dataType: 'json',
      success: function(data){
        if(data.csrf_hash){ csrf_hash = data.csrf_hash; }
        if(data.result){
          toastr.success(data.result);
          $('.delete-modal').modal('hide');
          $('#xin_table').DataTable().ajax.reload();
        }
        if(data.error){ toastr.error(data.error); }
      }
    });
  });
});
</script>
