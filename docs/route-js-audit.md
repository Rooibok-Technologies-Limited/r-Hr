<!-- @author Bodo Desderio <rooiboktechltd@gmail.com> -->
# Route ↔ JS contract audit (2026-08-03)

Auto-routing is **off** (`setAutoRoute(false)`), so every `main_url + "module/action"`
call in `public/module_scripts/*.js` needs a matching explicit `erp/...` route.
Tool: `route_audit.py` (extracts JS endpoint calls, diffs against registered routes).

## Result
- **Reachable pages (in the company + superadmin sidebars) were verified live and load clean** — their DataTables/actions resolve.
- **Expenses** was the one reachable breakage (`expenses_list`/`delete_expense` → real routes `list`/`delete`) — **fixed**.
- The candidates below are overwhelmingly for **HRSALE modules not exposed in the Rooibok sidebar** (products, crm, customfields, conference, trainers, orderquotes, purchases, warehouse, …). Their JS is effectively dead until those modules are reactivated. A couple are dynamic-URL false positives (`employees/delete_`, `settings/delete_`).

## Action
When (re)activating any module below, add the missing route (or align the JS URL) before shipping the page. Verify each with a headless load that asserts no DataTables Ajax error.

## Candidates (JS call → no matching erp/ route)
```
erp/agenda/advance_salary_list
erp/agenda/awards_list
erp/agenda/expense_list
erp/agenda/leave_list
erp/agenda/loan_list
erp/agenda/overtime_request_list
erp/agenda/payslip_history_list
erp/agenda/projects_list
erp/agenda/tasks_list
erp/agenda/travel_list
erp/announcements/read_annoucement
erp/complaints/read_complaints
erp/conference/read_meeting_record
erp/crm/customer_read
erp/crm/customers_list
erp/crm/delete_customer
erp/crm/delete_lead
erp/crm/leads_list
erp/customfields/customfields_list
erp/customfields/delete_customfield
erp/customfields/read_customfield
erp/documents/delete_official_document
erp/documents/read_official_document
erp/documents/system_documents_list
erp/employees/delete_
erp/events/read_event_record
erp/expenses/read_expense
erp/finance/delete_payeers
erp/finance/payees_list
erp/finance/payers_list
erp/finance/read_payee_payers
erp/languages/languages_list
erp/leaving/delete_employee_exit
erp/leaving/employee_off_list
erp/leaving/read_employee_exit
erp/mailbox/update_deletemail_record
erp/mailbox/update_important_mail_record
erp/mailbox/update_starmail_record
erp/my-mailbox
erp/officeshifts/delete_office_shift
erp/officeshifts/office_shifts_list
erp/orderquotes/delete_quoteorder
erp/orderquotes/read_quote_data
erp/orders/read_invoice_data
erp/paymenthistory/payment_history_list
erp/products/expired_product_list
erp/products/out_of_stock_list
erp/products/product_list
erp/projects/read_project
erp/purchases/delete_invoice_items
erp/purchases/get_purchase_items
erp/purchases/read_purchase_data
erp/roles/read_role
erp/roles/staff_roles_list
erp/settings/constants_read
erp/settings/delete_
erp/settings/read_sms_tempalte
erp/settings/read_tempalte
erp/talent/read_appraisal
erp/talent/read_indicator
erp/tasks/read_task
erp/todo/read_todo
erp/todo/update_item
erp/trackgoals/read_goal
erp/trainers/trainer_list
erp/transfers/is_department
```
