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
$categories = $ExpenseCategoryModel->where('company_id', $company_id)->findAll();

$get_animate = '';
?>

<div id="smartwizard-2" class="border-bottom smartwizard-example sw-main sw-theme-default mt-2">
  <ul class="nav nav-tabs step-anchor">
    <li class="nav-item clickable"> <a href="<?= site_url('erp/expenses');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon feather icon-file-text"></span>
      Expense Claims
      <div class="text-muted small">Manage Expenses</div>
      </a> </li>
    <li class="nav-item active"> <a href="<?= site_url('erp/expense-categories');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon fas fa-tags"></span>
      Categories
      <div class="text-muted small">Manage Categories</div>
      </a> </li>
    <li class="nav-item clickable"> <a href="<?= site_url('erp/expense-report');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon fas fa-chart-bar"></span>
      Report
      <div class="text-muted small">Expense Report</div>
      </a> </li>
  </ul>
</div>
<hr class="border-light m-0 mb-3">

<div class="row m-b-1 animated fadeInRight">
  <div class="col-md-5">
    <div class="card mb-2">
      <div class="card-header"><h5>Add New Category</h5></div>
      <div class="card-body">
        <?php $attributes = array('name' => 'add_category', 'id' => 'xin-form-category', 'autocomplete' => 'off');?>
        <?php echo form_open('erp/expenses/add-category', $attributes);?>
        <div class="form-group">
          <label for="category_name">Category Name <span class="text-danger">*</span></label>
          <input class="form-control" placeholder="e.g. Transport, Meals, Accommodation" name="category_name" type="text">
        </div>
        <button type="submit" class="btn btn-primary"><?= lang('Main.xin_save');?></button>
        <?= form_close(); ?>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card user-profile-list">
      <div class="card-header"><h5>Expense Categories</h5></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="category_table">
            <thead>
              <tr>
                <th>#</th>
                <th>Category Name</th>
                <th>Status</th>
                <th width="120">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php $i=1; foreach($categories as $cat) { ?>
              <tr id="cat-row-<?= $cat['category_id']?>">
                <td><?= $i++?></td>
                <td>
                  <span class="cat-name-display"><?= $cat['category_name']?></span>
                  <input type="text" class="form-control form-control-sm cat-name-edit d-none" value="<?= $cat['category_name']?>">
                </td>
                <td>
                  <?php if($cat['is_active'] == 1){?>
                    <span class="badge badge-success">Active</span>
                  <?php } else {?>
                    <span class="badge badge-secondary">Inactive</span>
                  <?php } ?>
                </td>
                <td>
                  <button type="button" class="btn icon-btn btn-sm btn-light-primary edit-cat" data-id="<?= uencode($cat['category_id'])?>" title="Edit"><i class="feather icon-edit"></i></button>
                  <button type="button" class="btn icon-btn btn-sm btn-light-success save-cat d-none" data-id="<?= uencode($cat['category_id'])?>" title="Save"><i class="feather icon-check"></i></button>
                  <button type="button" class="btn icon-btn btn-sm btn-light-danger delete-cat" data-id="<?= uencode($cat['category_id'])?>" title="Delete"><i class="feather icon-trash-2"></i></button>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  // inline edit toggle
  $(document).on('click', '.edit-cat', function(){
    var row = $(this).closest('tr');
    row.find('.cat-name-display').addClass('d-none');
    row.find('.cat-name-edit').removeClass('d-none');
    $(this).addClass('d-none');
    row.find('.save-cat').removeClass('d-none');
  });
  // save edit
  $(document).on('click', '.save-cat', function(){
    var row = $(this).closest('tr');
    var id = $(this).data('id');
    var name = row.find('.cat-name-edit').val();
    $.ajax({
      url: site_url + '/erp/expenses/update-category/',
      type: 'POST',
      data: { type: 'edit_record', token: id, category_name: name, is_active: 1, csrf_token_name: csrf_hash },
      dataType: 'json',
      success: function(data){
        if(data.csrf_hash){ csrf_hash = data.csrf_hash; }
        if(data.result){
          toastr.success(data.result);
          location.reload();
        }
        if(data.error){ toastr.error(data.error); }
      }
    });
  });
  // delete category
  $(document).on('click', '.delete-cat', function(){
    var id = $(this).data('id');
    if(confirm('Delete this category?')){
      $.ajax({
        url: site_url + '/erp/expenses/delete-category/',
        type: 'POST',
        data: { type: 'delete_record', _token: id, csrf_token_name: csrf_hash },
        dataType: 'json',
        success: function(data){
          if(data.csrf_hash){ csrf_hash = data.csrf_hash; }
          if(data.result){
            toastr.success(data.result);
            location.reload();
          }
          if(data.error){ toastr.error(data.error); }
        }
      });
    }
  });
});
</script>
