/**
 * Rooibok HR System — Archive Vault
 */
$(document).ready(function() {
    if ($('#vault-table').length) {
        $('#vault-table').DataTable({
            "pageLength": 25,
            "order": [[3, "desc"]]
        });
    }
});
