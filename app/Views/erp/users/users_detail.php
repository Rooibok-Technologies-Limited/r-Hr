<?php
use App\Models\UsersModel;
use App\Models\CountryModel;
use App\Models\SuperroleModel;

$CountryModel = new CountryModel();
$SuperroleModel = new SuperroleModel();
$UsersModel = new UsersModel();
$request = \Config\Services::request();

$roles = $SuperroleModel->orderBy('role_id', 'ASC')->findAll();
$segment_id = $request->uri->getSegment(3);
$user_id = udecode($segment_id);
$result = $UsersModel->where('user_id', $user_id)->first();
if(!$result) { echo '<div class="alert alert-danger">User not found.</div>'; return; }

$all_countries = $CountryModel->orderBy('country_id', 'ASC')->findAll();
$user_role = $SuperroleModel->where('role_id', $result['user_role_id'])->first();
$country_info = $CountryModel->where('country_id', $result['country'])->first();

$is_active = $result['is_active'] == 1;
?>

<!-- Back Link -->
<a href="<?= site_url('erp/super-users'); ?>" class="btn btn-outline-secondary btn-sm mb-3">
  <i class="feather icon-arrow-left mr-1"></i> Back to Staff Users
</a>

<div class="row">
  <!-- Profile Card -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body text-center">
        <img src="<?= staff_profile_photo($result['user_id']); ?>" alt="" class="img-radius mb-3" width="90" height="90">
        <h5 class="mb-1"><?= esc($result['first_name'].' '.$result['last_name']); ?></h5>
        <p class="text-muted mb-2">@<?= esc($result['username']); ?></p>
        <span class="badge badge-<?= $is_active ? 'success' : 'danger'; ?> mb-3">
          <?= $is_active ? 'Active' : 'Inactive'; ?>
        </span>
        <?php if(!empty($user_role)): ?>
        <p class="mb-0"><span class="badge badge-light-primary"><?= esc($user_role['role_name']); ?></span></p>
        <?php endif; ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span><i class="feather icon-mail mr-2 text-muted"></i>Email</span>
          <span class="text-muted"><?= esc($result['email']); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i class="feather icon-phone mr-2 text-muted"></i>Phone</span>
          <span class="text-muted"><?= esc($result['contact_number']); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i class="feather icon-map-pin mr-2 text-muted"></i>Country</span>
          <span class="text-muted"><?= !empty($country_info) ? esc($country_info['country_name']) : 'N/A'; ?></span>
        </li>
        <?php if(!empty($result['city'])): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span><i class="feather icon-navigation mr-2 text-muted"></i>City</span>
          <span class="text-muted"><?= esc($result['city']); ?></span>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Photo Upload -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><?= lang('Users.xin_user_photo'); ?></h5>
      </div>
      <div class="card-body">
        <?php $attributes = array('name' => 'profile_photo', 'id' => 'ci_logo', 'autocomplete' => 'off'); ?>
        <?php $hidden = array('user_id' => 0, 'token' => $segment_id); ?>
        <?= form_open_multipart('erp/users/update_profile_photo', $attributes, $hidden); ?>
        <div class="form-group">
          <div class="custom-file">
            <input type="file" class="custom-file-input" name="file" accept="image/*">
            <label class="custom-file-label"><?= lang('Main.xin_choose_file'); ?></label>
          </div>
          <small class="text-muted"><?= lang('Main.xin_company_file_type'); ?></small>
        </div>
        <button type="submit" class="btn btn-primary btn-sm btn-block"><?= lang('Main.xin_save'); ?></button>
        <?= form_close(); ?>
      </div>
    </div>
  </div>

  <!-- Edit Form -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Personal Information</h5>
      </div>
      <div class="card-body">
        <?php $attributes = array('name' => 'edit_user', 'id' => 'edit_user', 'autocomplete' => 'off'); ?>
        <?php $hidden = array('token' => $segment_id); ?>
        <?= form_open_multipart('erp/users/update_user', $attributes, $hidden); ?>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_first_name'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="first_name" type="text" value="<?= esc($result['first_name']); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_last_name'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="last_name" type="text" value="<?= esc($result['last_name']); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_email'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="email" type="email" value="<?= esc($result['email']); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.dashboard_username'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="username" type="text" value="<?= esc($result['username']); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.dashboard_xin_status'); ?> <span class="text-danger">*</span></label>
              <select class="form-control" name="status">
                <option value="1" <?= $result['is_active']=='1' ? 'selected' : ''; ?>>Active</option>
                <option value="2" <?= $result['is_active']!='1' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_contact_number'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="contact_number" type="text" value="<?= esc($result['contact_number']); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_gender'); ?></label>
              <select class="form-control" name="gender">
                <option value="1" <?= $result['gender']=='1' ? 'selected' : ''; ?>>Male</option>
                <option value="2" <?= $result['gender']=='2' ? 'selected' : ''; ?>>Female</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_role'); ?> <span class="text-danger">*</span></label>
              <select class="form-control" name="role">
                <option value=""><?= lang('Main.xin_select_one'); ?></option>
                <?php foreach($roles as $role): ?>
                <option value="<?= $role['role_id']; ?>" <?= $role['role_id']==$result['user_role_id'] ? 'selected' : ''; ?>><?= esc($role['role_name']); ?></option>
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
                <option value="<?= $country['country_id']; ?>" <?= $country['country_id']==$result['country'] ? 'selected' : ''; ?>><?= esc($country['country_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <h6 class="text-muted mb-3 mt-2">Address</h6>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Main.xin_address'); ?></label>
              <input class="form-control" name="address_1" type="text" value="<?= esc($result['address_1'] ?? ''); ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Main.xin_address_2'); ?></label>
              <input class="form-control" name="address_2" type="text" value="<?= esc($result['address_2'] ?? ''); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_city'); ?></label>
              <input class="form-control" name="city" type="text" value="<?= esc($result['city'] ?? ''); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_state'); ?></label>
              <input class="form-control" name="state" type="text" value="<?= esc($result['state'] ?? ''); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_zipcode'); ?></label>
              <input class="form-control" name="zipcode" type="text" value="<?= esc($result['zipcode'] ?? ''); ?>">
            </div>
          </div>
        </div>
        <div class="text-right">
          <button type="submit" class="btn btn-primary"><?= lang('Main.xin_save'); ?></button>
        </div>
        <?= form_close(); ?>
      </div>
    </div>
  </div>
</div>
