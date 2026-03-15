$(document).ready(function() {
  var xin_table = $('#xin_table').dataTable({
    "bDestroy": true,
    "ajax": {
      url: main_url + "users/roles_list",
      type: 'GET'
    },
    "language": {
      "lengthMenu": dt_lengthMenu,
      "zeroRecords": dt_zeroRecords,
      "info": dt_info,
      "infoEmpty": dt_infoEmpty,
      "infoFiltered": dt_infoFiltered,
      "search": dt_search,
      "paginate": { "first": dt_first, "previous": dt_previous, "next": dt_next, "last": dt_last }
    },
    "fnDrawCallback": function(){ $('[data-toggle="tooltip"]').tooltip(); }
  });

  /* Delete role */
  $("#delete_record").submit(function(e){
    e.preventDefault();
    var obj = $(this);
    $.ajax({
      type: "POST",
      url: e.target.action,
      data: obj.serialize() + "&is_ajax=2&type=delete_record&form=" + obj.attr('name'),
      dataType: "json",
      cache: false,
      success: function(data) {
        if (data.error && data.error !== '') {
          toastr.error(data.error);
        } else {
          $('.delete-modal').modal('hide');
          xin_table.api().ajax.reload(function(){ toastr.success(data.result); }, true);
        }
        if(data.csrf_hash) $('input[name="csrf_token"]').val(data.csrf_hash);
        try{Ladda.stopAll();}catch(ex){}
      },
      error: function(xhr) {
        toastr.error('Delete failed');
        try{Ladda.stopAll();}catch(ex){}
      }
    });
  });

  /* Edit role modal — load content */
  $('.edit-modal-data').on('show.bs.modal', function(event) {
    var field_id = $(event.relatedTarget).data('field_id');
    $.ajax({
      url: main_url + "users/read_role",
      type: "GET",
      data: 'jd=1&data=role&field_id=' + field_id,
      success: function(response) { if(response) $("#ajax_modal").html(response); }
    });
  });

  /* Add role */
  $("#xin-form").submit(function(e){
    e.preventDefault();
    var fd = new FormData(this);
    fd.append("is_ajax", 1);
    fd.append("type", "add_record");
    fd.append("form", $(this).attr('name'));
    $.ajax({
      url: $(this).attr('action'),
      type: "POST",
      data: fd,
      dataType: "json",
      contentType: false,
      cache: false,
      processData: false,
      success: function(data) {
        if (data.error && data.error !== '') {
          toastr.error(data.error);
        } else {
          toastr.success(data.result || 'Role created');
          xin_table.api().ajax.reload(null, true);
          $('#xin-form')[0].reset();
          $('#addRoleModal').modal('hide');
        }
        if(data.csrf_hash) $('input[name="csrf_token"]').val(data.csrf_hash);
        try{Ladda.stopAll();}catch(ex){}
      },
      error: function(xhr) {
        var msg = 'Create failed'; try{var r=JSON.parse(xhr.responseText);if(r.error)msg=r.error;}catch(ex){} toastr.error(msg);
        try{Ladda.stopAll();}catch(ex){}
      }
    });
  });

  /* Full Access toggle */
  $("#role_access").change(function(){
    if($(this).val()=='1') { $('.switcher-input').prop('checked', true); }
    else { $('.switcher-input').prop("checked", false); }
  });
});

/* Delete button handler */
$(document).on("click", ".delete", function() {
  $('input[name=_token]').val($(this).data('record-id'));
  $('#delete_record').attr('action', main_url + 'users/delete_role/' + $(this).data('record-id'));
});
