/**
 * Rooibok HR System — Expense Report
 */
$(document).ready(function() {
    // Report filtering — DataTable initialization is handled inline in the view
    $('[data-plugin="select_hrm"]').select2($(this).attr('data-options'));
    $('[data-plugin="select_hrm"]').select2({ width: '100%' });
});
