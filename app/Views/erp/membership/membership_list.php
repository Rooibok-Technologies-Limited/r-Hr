<?php
use App\Models\MembershipModel;
$MembershipModel = new MembershipModel();
$plan_count = $MembershipModel->countAllResults();
?>

<!-- Subscription Plans Table -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0"><?= lang('Membership.xin_membership_plans'); ?></h5>
      <small class="text-muted"><?= $plan_count; ?> plan<?= $plan_count != 1 ? 's' : ''; ?> configured</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPlanModal">
      <i class="feather icon-plus mr-1"></i> <?= lang('Membership.xin_new_subscription'); ?>
    </button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="xin_table" class="table table-hover">
        <thead>
          <tr>
            <th><?= lang('Membership.xin_membership_type'); ?></th>
            <th>Plan ID</th>
            <th><?= lang('Membership.xin_plan_duration'); ?></th>
            <th><?= lang('Main.xin_price'); ?> (UGX)</th>
            <th><?= lang('Employees.xin_total_employees'); ?></th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Add Plan Modal -->
<div class="modal fade" id="addPlanModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <?php $attributes = array('name' => 'add_membership', 'id' => 'xin-form', 'autocomplete' => 'off'); ?>
      <?php $hidden = array('user_id' => 0); ?>
      <?= form_open('erp/membership/add_membership', $attributes, $hidden); ?>
      <div class="modal-header">
        <h5 class="modal-title"><?= lang('Main.xin_add_new'); ?> <?= lang('Membership.xin_plan'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Membership.xin_membership_type'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="membership_type" type="text" placeholder="e.g. Enterprise, Growth, Starter">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Main.xin_price'); ?> (UGX) <span class="text-danger">*</span></label>
              <input class="form-control" name="price" type="number" placeholder="e.g. 59000">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Membership.xin_plan_duration'); ?> <span class="text-danger">*</span></label>
              <select class="form-control" name="plan_duration">
                <option value=""><?= lang('Main.xin_select_one'); ?></option>
                <option value="1"><?= lang('Membership.xin_per_month'); ?></option>
                <option value="2"><?= lang('Membership.xin_per_year'); ?></option>
                <option value="3"><?= lang('Membership.xin_subscription_unlimit'); ?></option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= lang('Employees.xin_total_employees'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" name="total_employees" type="number" placeholder="e.g. 50">
            </div>
          </div>
          <div class="col-md-12">
            <div class="form-group">
              <label><?= lang('Main.xin_description'); ?></label>
              <textarea class="form-control" name="description" rows="2" placeholder="Brief description of this plan"></textarea>
            </div>
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
