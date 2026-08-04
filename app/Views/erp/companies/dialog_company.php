<?php
/** @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved. */
use App\Models\UsersModel;

$UsersModel = new UsersModel();
$company_id = !empty($field_id) ? udecode($field_id) : false;
$result = $company_id ? $UsersModel->where('user_id', $company_id)->first() : null;
if(empty($result)) {
	echo '<div class="modal-header"><h5 class="modal-title">Company</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p class="text-muted mb-0">Company not found.</p></div>';
	return;
}
?>
<div class="modal-header">
  <h5 class="modal-title"><?= esc($result['company_name'] ?? ($result['first_name'].' '.$result['last_name'])); ?></h5>
  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
  <ul class="list-group">
    <li class="list-group-item d-flex justify-content-between"><span class="f-w-500"><?= lang('Company.xin_company_name'); ?></span><span><?= esc($result['company_name']); ?></span></li>
    <li class="list-group-item d-flex justify-content-between"><span class="f-w-500"><?= lang('Main.contact_first_name_error'); ?></span><span><?= esc($result['first_name'].' '.$result['last_name']); ?></span></li>
    <li class="list-group-item d-flex justify-content-between"><span class="f-w-500"><?= lang('Main.xin_email'); ?></span><span><?= esc($result['email']); ?></span></li>
    <li class="list-group-item d-flex justify-content-between"><span class="f-w-500"><?= lang('Main.xin_contact_number'); ?></span><span><?= esc($result['contact_number']); ?></span></li>
  </ul>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>
