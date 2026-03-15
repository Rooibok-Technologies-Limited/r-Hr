/**
 * Rooibok HR System — Expense Categories
 */
$(document).ready(function() {
    // Category CRUD — form handling
    $("#xin-form-category, #xin-form").submit(function(e) {
        e.preventDefault();
        var obj = $(this), action = obj.attr('name');
        $.ajax({
            type: "POST",
            url: e.target.action,
            data: obj.serialize() + "&is_ajax=1&type=add_record&form=" + action,
            cache: false,
            success: function(JSON) {
                if (JSON.error != '') {
                    toastr.error(JSON.error);
                } else {
                    toastr.success(JSON.result);
                    location.reload();
                }
                if (JSON.csrf_hash) {
                    $('input[name="csrf_token"]').val(JSON.csrf_hash);
                }
                if (typeof Ladda !== 'undefined') Ladda.stopAll();
            }
        });
    });

    /* Delete data */
    $("#delete_record").submit(function(e) {
        e.preventDefault();
        var obj = $(this), action = obj.attr('name');
        $.ajax({
            type: "POST",
            url: e.target.action,
            data: obj.serialize() + "&is_ajax=2&type=delete_record&form=" + action,
            cache: false,
            success: function(JSON) {
                if (JSON.error != '') {
                    toastr.error(JSON.error);
                } else {
                    $('.delete-modal').modal('toggle');
                    toastr.success(JSON.result);
                    location.reload();
                }
                if (JSON.csrf_hash) {
                    $('input[name="csrf_token"]').val(JSON.csrf_hash);
                }
                if (typeof Ladda !== 'undefined') Ladda.stopAll();
            }
        });
    });
});
