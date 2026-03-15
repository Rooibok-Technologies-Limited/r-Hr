/**
 * Rooibok HR System — Landing CMS
 */
$(document).ready(function() {
    // CMS section save handlers
    $("#xin-form, #cms-form").submit(function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        var obj = $(this), action = obj.attr('name');
        fd.append("is_ajax", 1);
        fd.append("type", "update_record");
        fd.append("form", action);
        $.ajax({
            url: e.target.action,
            type: "POST",
            data: fd,
            contentType: false,
            cache: false,
            processData: false,
            success: function(JSON) {
                if (JSON.error != '') {
                    toastr.error(JSON.error);
                } else {
                    toastr.success(JSON.result);
                }
                if (JSON.csrf_hash) {
                    $('input[name="csrf_token"]').val(JSON.csrf_hash);
                }
                if (typeof Ladda !== 'undefined') Ladda.stopAll();
            }
        });
    });
});
