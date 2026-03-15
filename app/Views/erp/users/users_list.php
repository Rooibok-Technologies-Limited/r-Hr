<?php
use App\Models\CountryModel;
use App\Models\SuperroleModel;
use App\Models\UsersModel;

$CountryModel = new CountryModel();
$SuperroleModel = new SuperroleModel();
$UsersModel = new UsersModel();
$session = \Config\Services::session();
$usession = get_safe_session();

$all_countries = $CountryModel->orderBy('country_id', 'ASC')->findAll();
$roles = $SuperroleModel->orderBy('role_id', 'ASC')->findAll();

// Stats
$total_admins = $UsersModel->where('user_type','super_user')->countAllResults();
$active_admins = $UsersModel->where('user_type','super_user')->where('is_active',1)->countAllResults();
?>

<!-- Nav Tabs: Users | Roles -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link active" href="<?= site_url('erp/super-users'); ?>">
      <i class="feather icon-users mr-1"></i> <?= lang('Main.xin_super_users'); ?>
    </a>
  </li>
  <?php if(in_array('7',super_user_role_resource())): ?>
  <li class="nav-item">
    <a class="nav-link" href="<?= site_url('erp/users-role'); ?>">
      <i class="feather icon-shield mr-1"></i> <?= lang('Users.xin_hr_report_user_roles'); ?>
    </a>
  </li>
  <?php endif; ?>
</ul>

<!-- Stats Row -->
<div class="row mb-3">
  <div class="col-md-4 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0"><?= $total_admins; ?></h4>
        <small class="text-muted">Total Admin Users</small>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0 text-success"><?= $active_admins; ?></h4>
        <small class="text-muted">Active</small>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-12">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0"><?= count($roles); ?></h4>
        <small class="text-muted">Roles Defined</small>
      </div>
    </div>
  </div>
</div>

<!-- Users Table -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><?= lang('Main.xin_super_users'); ?></h5>
    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">
      <i class="feather icon-plus mr-1"></i> <?= lang('Main.xin_add_new'); ?>
    </button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="xin_table" class="table table-hover">
        <thead>
          <tr>
            <th><?= lang('Main.xin_name'); ?></th>
            <th><?= lang('Main.xin_contact_number'); ?></th>
            <th><?= lang('Main.xin_employee_role'); ?></th>
            <th><?= lang('Main.xin_country'); ?></th>
            <th><?= lang('Main.dashboard_xin_status'); ?></th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <?php $attributes = array('name' => 'add_user', 'id' => 'xin-form', 'autocomplete' => 'off'); ?>
      <?php $hidden = array('user_id' => 0); ?>
      <?= form_open_multipart('erp/users/add_user', $attributes, $hidden); ?>
      <div class="modal-header">
        <h5 class="modal-title"><?= lang('Main.xin_add_new'); ?> <?= lang('Users.xin_user'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_first_name'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="first_name" type="text" placeholder="First name">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_last_name'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="last_name" type="text" placeholder="Last name">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_email'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="email" type="email" placeholder="admin@example.com">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.dashboard_username'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="username" type="text" placeholder="Min 6 characters">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_password'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="password" type="password" placeholder="Min 6 characters">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_contact_number'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="contact_number" type="text" placeholder="+256 700 000000">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_gender'); ?></label>
              <select class="form-control" name="gender">
                <option value="1"><?= lang('Main.xin_gender_male'); ?></option>
                <option value="2"><?= lang('Main.xin_gender_female'); ?></option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_role'); ?> <span class="text-danger">*</span></label>
              <select class="form-control" name="role">
                <option value=""><?= lang('Main.xin_select_one'); ?></option>
                <?php foreach($roles as $role): ?>
                <option value="<?= $role['role_id']; ?>"><?= esc($role['role_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_country'); ?> <span class="text-danger">*</span></label>
              <select class="form-control" name="country">
                <option value=""><?= lang('Main.xin_select_one'); ?></option>
                <?php foreach($all_countries as $country): ?>
                <option value="<?= $country['country_id']; ?>"><?= esc($country['country_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <h6 class="text-muted mb-3 mt-2"><?= lang('Users.xin_user_photo'); ?></h6>
        <div class="form-group">
          <div class="custom-file">
            <input type="file" class="custom-file-input" name="file" accept="image/*">
            <label class="custom-file-label"><?= lang('Main.xin_choose_file'); ?></label>
          </div>
          <small class="text-muted"><?= lang('Main.xin_company_file_type'); ?></small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= lang('Main.xin_save'); ?></button>
      </div>
      <?= form_close(); ?>
    </div>
  </div>
</div>
