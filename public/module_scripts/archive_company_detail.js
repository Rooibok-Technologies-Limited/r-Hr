/**
 * Rooibok HR System — Archive Company Detail
 */
$(document).ready(function() {
    // Initialize DataTables on all basic tables
    if ($('.datatable-basic').length) {
        $('.datatable-basic').DataTable({
            "pageLength": 25,
            "order": []
        });
    }

    // Tab switching
    $('a[data-toggle="tab"], a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
});
