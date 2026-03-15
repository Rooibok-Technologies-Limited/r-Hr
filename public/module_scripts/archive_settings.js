/**
 * Rooibok HR System — Archive Settings
 */
$(document).ready(function() {
    // Settings form handler
    $("#xin-form, #archive-settings-form").submit(function(e) {
        e.preventDefault();
        var obj = $(this), action = obj.attr('name');
        $.ajax({
            type: "POST",
            url: e.target.action,
            data: obj.serialize() + "&is_ajax=1&type=update_record&form=" + action,
            cache: false,
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
