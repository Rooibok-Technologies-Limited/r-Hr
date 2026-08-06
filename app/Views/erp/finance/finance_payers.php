<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
use App\Models\SystemModel;
use App\Models\RolesModel;
use App\Models\UsersModel;
use App\Models\PayeesModel;

$session = \Config\Services::session();
$usession = $session->get('sup_username');

$UsersModel = new UsersModel();
$RolesModel = new RolesModel();
$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
/*
* Finance||Payer - View Page
*/
?>
<div id="smartwizard-2" class="border-bottom smartwizard-example sw-main sw-theme-default mt-2">
    <ul class="nav nav-tabs step-anchor">
        <?php if(in_array('deposit1',staff_role_resource()) || $user_info['user_type'] == 'company') {?>
        <li class="nav-item clickable"> <a href="<?= site_url('erp/deposit-list');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon fas fa-dollar-sign"></span>
            <?= lang('Dashboard.xin_acc_deposit');?>
            <div class="text-muted small">
                <?= lang('Main.xin_add');?>
                <?= lang('Dashboard.xin_acc_deposit');?>
            </div>
            </a> </li>
        <li class="nav-item clickable"> <a href="<?= site_url('erp/income-type');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon feather icon-copy"></span>
            <?= lang('Dashboard.xin_category');?>
            <div class="text-muted small">
                <?= lang('Main.xin_add');?>
                <?= lang('Dashboard.xin_category');?>
            </div>
            </a> </li>
        <?php } ?>
        <li class="nav-item active"> <a href="<?= site_url('erp/payers-list');?>" class="mb-3 nav-link"> <span class="sw-done-icon feather icon-check-circle"></span> <span class="sw-icon fas fa-user-tag"></span>
            <?= lang('Dashboard.xin_acc_payers');?>
            <div class="text-muted small">
                <?= lang('Main.xin_add');?>
                <?= lang('Dashboard.xin_acc_payers');?>
            </div>
            </a> </li>
    </ul>
</div>
<hr class="border-light m-0 mb-3">

<div class="row m-b-1">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header with-elements">
        <h5>
          <?= lang('Main.xin_add_new');?>
          <?= lang('Dashboard.xin_acc_payer');?>
        </h5>
      </div>
      <?php $attributes = array('name' => 'add_payer', 'id' => 'xin-form', 'autocomplete' => 'off');?>
      <?php echo form_open('erp/finance/add_payer', $attributes);?>
      <div class="card-body">
        <div class="form-group">
          <label for="payer_name">
            <?= lang('Dashboard.xin_acc_payer');?>
          </label>
          <input type="text" class="form-control" name="name" placeholder="<?= lang('Dashboard.xin_acc_payer_name');?>">
        </div>
        <div class="form-group">
          <label for="contact_number">
            <?= lang('Main.xin_contact_number');?>
          </label>
          <input type="text" class="form-control" name="contact_number" placeholder="<?= lang('Main.xin_contact_number');?>">
        </div>
      </div>
      <div class="card-footer text-right">
        <button type="submit" class="btn btn-primary"><?= lang('Main.xin_save');?></button>
      </div>
      <?= form_close(); ?>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card user-profile-list">
      <div class="card-header with-elements">
        <h5>
          <?= lang('Main.xin_list_all');?>
          <?= lang('Dashboard.xin_acc_payers');?>
        </h5>
      </div>
      <div class="card-body">
        <div class="box-datatable table-responsive">
          <table class="datatables-demo table table-striped table-bordered" id="xin_table">
            <thead>
              <tr>
                <th><?= lang('Dashboard.xin_acc_payer');?></th>
                <th><?= lang('Main.xin_contact_number');?></th>
                <th><?= lang('Dashboard.xin_acc_created_at');?></th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
