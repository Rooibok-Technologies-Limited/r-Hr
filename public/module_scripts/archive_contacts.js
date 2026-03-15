/**
 * Rooibok HR System — Archive Contacts
 */
$(document).ready(function() {
    if ($('#contacts-table').length) {
        $('#contacts-table').DataTable({
            "pageLength": 25,
            "order": [[0, "asc"]],
            "fnDrawCallback": function(settings) {
                $('[data-toggle="tooltip"]').tooltip();
            }
        });
    }
});
