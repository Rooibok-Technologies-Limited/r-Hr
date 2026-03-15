<?php
use App\Models\SuperroleModel;

$SuperroleModel = new SuperroleModel();
$request = \Config\Services::request();

if($request->getGet('data') === 'role' && !empty($field_id)){
$role_id = udecode($field_id);
$result = $SuperroleModel->where('role_id', $role_id)->first();
if(!$result) { echo '<div class="modal-body"><p>Role not found.</p></div>'; return; }
$role_resources_ids = !empty($result['role_resources']) ? explode(',', $result['role_resources']) : [];

$permissions = [
  ['id'=>1,'label'=>'Companies'],
  ['id'=>2,'label'=>'Subscriptions'],
  ['id'=>3,'label'=>'Billing Invoices'],
  ['id'=>5,'label'=>'Staff Users'],
  ['id'=>6,'label'=>'Settings'],
  ['id'=>7,'label'=>'User Roles'],
  ['id'=>8,'label'=>'Database Backup'],
  ['id'=>9,'label'=>'Email Templates'],
  ['id'=>10,'label'=>'Archive Portal'],
  ['id'=>11,'label'=>'Broadcasts'],
];
?>

<div class="modal-header">
  <h5 class="modal-title">Edit Role: <?= esc($result['role_name']); ?></h5>
  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>
<?php $attributes = array('name' => 'edit_role', 'id' => 'edit_role', 'autocomplete' => 'off'); ?>
<?php $hidden = array('token' => $field_id); ?>
<?= form_open('erp/users/update_role', $attributes, $hidden); ?>
<div class="modal-body">
  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label>Role Name <span class="text-danger">*</span></label>
        <input class="form-control" name="role_name" type="text" value="<?= esc($result['role_name']); ?>">
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label>Access Level <span class="text-danger">*</span></label>
        <select class="form-control" id="role_access_modal" name="role_access">
          <option value="1" <?= $result['role_access']=='1' ? 'selected' : ''; ?>>Full Access (All Modules)</option>
          <option value="2" <?= $result['role_access']=='2' ? 'selected' : ''; ?>>Custom Access (Select Below)</option>
        </select>
      </div>
    </div>
  </div>
  <h6 class="text-muted mb-3 mt-2">Module Permissions</h6>
  <div class="row">
    <div class="col-md-6">
      <input type="hidden" name="role_resources[0]" value="0">
      <?php foreach(array_slice($permissions, 0, 5) as $perm): ?>
      <div class="form-group mb-2">
        <div class="custom-control custom-switch">
          <input type="checkbox" class="custom-control-input switcher-input" name="role_resources[<?= $perm['id']; ?>]" id="edit_perm_<?= $perm['id']; ?>" value="<?= $perm['id']; ?>" <?= in_array($perm['id'], $role_resources_ids) ? 'checked' : ''; ?>>
          <label class="custom-control-label" for="edit_perm_<?= $perm['id']; ?>"><?= $perm['label']; ?></label>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="col-md-6">
      <?php foreach(array_slice($permissions, 5) as $perm): ?>
      <div class="form-group mb-2">
        <div class="custom-control custom-switch">
          <input type="checkbox" class="custom-control-input switcher-input" name="role_resources[<?= $perm['id']; ?>]" id="edit_perm_<?= $perm['id']; ?>" value="<?= $perm['id']; ?>" <?= in_array($perm['id'], $role_resources_ids) ? 'checked' : ''; ?>>
          <label class="custom-control-label" for="edit_perm_<?= $perm['id']; ?>"><?= $perm['label']; ?></label>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
  <button type="submit" class="btn btn-primary">Update</button>
</div>
<?= form_close(); ?>
<script>
$(document).ready(function(){
  $("#role_access_modal").change(function(){
    if($(this).val()=='1') { $('.switcher-input').prop('checked', true); }
    else { $('.switcher-input').prop("checked", false); }
  });
  $("#edit_role").submit(function(e){
    e.preventDefault();
    $.ajax({
      type: "POST",
      url: $(this).attr('action'),
      data: $(this).serialize()+"&is_ajax=1&type=edit_record&form="+$(this).attr('name'),
      dataType: "json",
      cache: false,
      success: function(data) {
        if (data.error && data.error !== '') {
          toastr.error(data.error);
        } else {
          toastr.success(data.result || 'Role updated');
          $('.edit-modal-data').modal('hide');
          $('#xin_table').DataTable().ajax.reload();
        }
        if(data.csrf_hash) $('input[name="csrf_token"]').val(data.csrf_hash);
        try{Ladda.stopAll();}catch(ex){}
      },
      error: function(xhr) {
        var msg = 'Update failed'; try{var r=JSON.parse(xhr.responseText);if(r.error)msg=r.error;}catch(ex){msg=xhr.status+' error';} toastr.error(msg);
        try{Ladda.stopAll();}catch(ex){}
      }
    });
  });
});
</script>
<?php } ?>
