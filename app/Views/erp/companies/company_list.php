<?php
use App\Models\ConstantsModel;
use App\Models\CountryModel;
use App\Models\MembershipModel;
use App\Models\CompanymembershipModel;
use App\Models\UsersModel;

$ConstantsModel = new ConstantsModel();
$CountryModel = new CountryModel();
$MembershipModel = new MembershipModel();
$CompanymembershipModel = new CompanymembershipModel();
$UsersModel = new UsersModel();

$company_types = $ConstantsModel->where('type','company_type')->orderBy('constants_id', 'ASC')->findAll();
$all_countries = $CountryModel->orderBy('country_id', 'ASC')->findAll();
$membership_plans = $MembershipModel->orderBy('membership_id', 'ASC')->findAll();

// Stats
$total = $UsersModel->where('user_type','company')->countAllResults();
$active = $UsersModel->where('user_type','company')->where('is_active',1)->countAllResults();
$inactive = $total - $active;
?>

<!-- Stats Row -->
<div class="row mb-3">
  <div class="col-md-3 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0"><?= $total; ?></h4>
        <small class="text-muted">Total Companies</small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0 text-success"><?= $active; ?></h4>
        <small class="text-muted">Active</small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0 text-danger"><?= $inactive; ?></h4>
        <small class="text-muted">Inactive</small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card mb-0">
      <div class="card-body py-3 text-center">
        <h4 class="mb-0 text-primary"><?= count($membership_plans); ?></h4>
        <small class="text-muted">Plans Available</small>
      </div>
    </div>
  </div>
</div>

<!-- Companies Table -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><?= lang('Company.xin_companies'); ?></h5>
    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCompanyModal">
      <i class="feather icon-plus mr-1"></i> <?= lang('Main.xin_add_new'); ?>
    </button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="xin_table" class="table table-hover">
        <thead>
          <tr>
            <th><?= lang('Company.xin_company_name'); ?></th>
            <th>Contact</th>
            <th><?= lang('Membership.xin_membership_type'); ?></th>
            <th><?= lang('Main.xin_country'); ?></th>
            <th><?= lang('Main.dashboard_xin_status'); ?></th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Add Company Modal -->
<div class="modal fade" id="addCompanyModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <?php $attributes = array('name' => 'add_company', 'id' => 'xin-form', 'autocomplete' => 'off'); ?>
      <?php $hidden = array('user_id' => 0); ?>
      <?= form_open_multipart('erp/companies/add_company', $attributes, $hidden); ?>
      <div class="modal-header">
        <h5 class="modal-title"><?= lang('Main.xin_add_new'); ?> <?= lang('Company.module_company_title'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h6 class="text-muted mb-3"><?= lang('Main.xin_employee_basic_title'); ?></h6>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Company.xin_company_name'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="company_name" type="text" placeholder="<?= lang('Company.xin_company_name'); ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Company.xin_company_type'); ?> <span class="text-danger">*</span></label>
              <select class="form-control" name="company_type">
                <option value=""><?= lang('Main.xin_select_one'); ?></option>
                <?php foreach($company_types as $ctype): ?>
                <option value="<?= $ctype['constants_id']; ?>"><?= $ctype['category_name']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.contact_first_name_error'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="first_name" type="text" placeholder="First name">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.contact_last_name_error'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="last_name" type="text" placeholder="Last name">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= lang('Main.xin_country'); ?> <span class="text-danger">*</span></label>
              <select class="form-control" name="country">
                <option value=""><?= lang('Main.xin_select_one'); ?></option>
                <?php foreach($all_countries as $country): ?>
                <option value="<?= $country['country_id']; ?>"><?= $country['country_name']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <h6 class="text-muted mb-3 mt-2"><?= lang('Membership.xin_membership_plan'); ?></h6>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Membership.xin_membership_type'); ?> <span class="text-danger">*</span></label>
              <select class="form-control" name="membership_type">
                <option value=""><?= lang('Main.xin_select_one'); ?></option>
                <?php foreach($membership_plans as $plan): ?>
                <option value="<?= $plan['membership_id']; ?>"><?= $plan['membership_type']; ?> — UGX <?= number_format($plan['price'],0); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <h6 class="text-muted mb-3 mt-2">Account &amp; Contact</h6>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Main.xin_email'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="email" type="email" placeholder="company@example.com">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Main.xin_contact_number'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="contact_number" type="text" placeholder="+256 700 000000">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Main.dashboard_username'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="username" type="text" placeholder="Min 6 characters">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Main.xin_employee_password'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="password" type="password" placeholder="Min 6 characters">
            </div>
          </div>
        </div>

        <h6 class="text-muted mb-3 mt-2"><?= lang('Company.xin_company_logo'); ?></h6>
        <div class="form-group">
          <div class="custom-file">
            <input type="file" class="custom-file-input" name="file" accept="image/*">
            <label class="custom-file-label"><?= lang('Main.xin_choose_file'); ?></label>
          </div>
          <small class="text-muted"><?= lang('Main.xin_company_file_type'); ?></small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('Main.xin_reset'); ?></button>
        <button type="submit" class="btn btn-primary"><?= lang('Main.xin_save'); ?></button>
      </div>
      <?= form_close(); ?>
    </div>
  </div>
</div>
