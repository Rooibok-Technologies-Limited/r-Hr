/**
 * Rooibok HR System — Archive Search
 */
$(document).ready(function() {
    if ($('#search-results-table').length) {
        $('#search-results-table').DataTable({
            "pageLength": 50
        });
    }
});
