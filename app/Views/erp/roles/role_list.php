<?php
use App\Models\SuperroleModel;
$SuperroleModel = new SuperroleModel();
$role_count = $SuperroleModel->countAllResults();
?>

<!-- Nav Tabs: Users | Roles -->
<ul class="nav nav-tabs mb-3">
  <?php if(in_array('6',super_user_role_resource())): ?>
  <li class="nav-item">
    <a class="nav-link" href="<?= site_url('erp/super-users'); ?>">
      <i class="feather icon-users mr-1"></i> <?= lang('Main.xin_super_users'); ?>
    </a>
  </li>
  <?php endif; ?>
  <li class="nav-item">
    <a class="nav-link active" href="<?= site_url('erp/users-role'); ?>">
      <i class="feather icon-shield mr-1"></i> <?= lang('Users.xin_hr_report_user_roles'); ?>
    </a>
  </li>
</ul>

<!-- Roles Table -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0"><?= lang('Users.xin_hr_report_user_roles'); ?></h5>
      <small class="text-muted"><?= $role_count; ?> role<?= $role_count != 1 ? 's' : ''; ?> defined</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addRoleModal">
      <i class="feather icon-plus mr-1"></i> <?= lang('Main.xin_add_new'); ?>
    </button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="xin_table" class="table table-hover">
        <thead>
          <tr>
            <th><?= lang('Users.xin_role_name'); ?></th>
            <th>Access Level</th>
            <th>Date Created</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <?php $attributes = array('name' => 'add_role', 'id' => 'xin-form', 'autocomplete' => 'off'); ?>
      <?php $hidden = array('_user' => 0); ?>
      <?= form_open('erp/users/add_role', $attributes, $hidden); ?>
      <div class="modal-header">
        <h5 class="modal-title"><?= lang('Main.xin_add_new'); ?> Role</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Users.xin_role_name'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="role_name" type="text" placeholder="e.g. Maintainer, Billing Admin">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Users.xin_role_access'); ?> <span class="text-danger">*</span></label>
              <select class="form-control" id="role_access" name="role_access">
                <option value=""><?= lang('Main.xin_select_one'); ?></option>
                <option value="1">Full Access (All Modules)</option>
                <option value="2">Custom Access (Select Below)</option>
              </select>
            </div>
          </div>
        </div>
        <h6 class="text-muted mb-3 mt-2">Module Permissions</h6>
        <div class="row">
          <div class="col-md-6">
            <input type="hidden" name="role_resources[0]" value="0">
            <?php
            $permissions_left = [
              ['id'=>1,'label'=>'Companies'],
              ['id'=>2,'label'=>'Subscriptions'],
              ['id'=>3,'label'=>'Billing Invoices'],
              ['id'=>5,'label'=>'Staff Users'],
              ['id'=>6,'label'=>'Settings'],
            ];
            foreach($permissions_left as $perm): ?>
            <div class="form-group mb-2">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input switcher-input" name="role_resources[<?= $perm['id']; ?>]" id="perm_<?= $perm['id']; ?>" value="<?= $perm['id']; ?>">
                <label class="custom-control-label" for="perm_<?= $perm['id']; ?>"><?= $perm['label']; ?></label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="col-md-6">
            <?php
            $permissions_right = [
              ['id'=>7,'label'=>'User Roles'],
              ['id'=>8,'label'=>'Database Backup'],
              ['id'=>9,'label'=>'Email Templates'],
              ['id'=>10,'label'=>'Archive Portal'],
              ['id'=>11,'label'=>'Broadcasts'],
            ];
            foreach($permissions_right as $perm): ?>
            <div class="form-group mb-2">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input switcher-input" name="role_resources[<?= $perm['id']; ?>]" id="perm_<?= $perm['id']; ?>" value="<?= $perm['id']; ?>">
                <label class="custom-control-label" for="perm_<?= $perm['id']; ?>"><?= $perm['label']; ?></label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
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
