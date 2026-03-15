/**
 * Rooibok HR System — Broadcasts
 */
$(document).ready(function() {
    if ($('#xin_table').length) {
        $('#xin_table').DataTable({
            "pageLength": 25,
            "order": [[7, "desc"]],
            "language": {
                "lengthMenu": typeof dt_lengthMenu !== 'undefined' ? dt_lengthMenu : '_MENU_',
                "zeroRecords": typeof dt_zeroRecords !== 'undefined' ? dt_zeroRecords : 'No records found',
                "info": typeof dt_info !== 'undefined' ? dt_info : '_START_ to _END_ of _TOTAL_',
                "infoEmpty": typeof dt_infoEmpty !== 'undefined' ? dt_infoEmpty : 'No records',
                "infoFiltered": typeof dt_infoFiltered !== 'undefined' ? dt_infoFiltered : '(filtered from _MAX_)',
                "search": typeof dt_search !== 'undefined' ? dt_search : 'Search:',
            },
            "fnDrawCallback": function(settings) {
                $('[data-toggle="tooltip"]').tooltip();
            }
        });
    }
});
