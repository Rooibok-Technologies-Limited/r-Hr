<?php namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php'))
{
	require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/**
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.

// Root landing page
$routes->get('/', 'Home::index');

// Frontend pages
$routes->get('features', 'Home::features');
$routes->get('pricing', 'Home::pricing');
$routes->get('contact', 'Home::contact');
$routes->get('register', 'Home::register');
$routes->get('privacy', 'Home::privacy');
$routes->get('cookies', 'Home::cookies');
$routes->get('terms', 'Home::terms');
$routes->get('demo', 'Home::demo');

// Kiosk modes (no auth — tablet/kiosk devices)
$routes->get('kiosk', 'Home::kiosk');
$routes->get('visitor-kiosk', 'Home::visitor_kiosk');

// Landing Page CMS (super_user)
$routes->get('erp/landing-page', 'Erp\Landingpage::index', ['filter' => 'checklogin']);
$routes->post('erp/landing-page/save', 'Erp\Landingpage::save_section', ['filter' => 'checklogin']);
$routes->post('erp/landing-page/upload', 'Erp\Landingpage::upload_image', ['filter' => 'checklogin']);

// ERP|TimeHRM
///$routes->get('erp/{locale}/dashboard', 'Dashboard::language', ['namespace' => 'App\Controllers\Erp']);
$routes->get('erp/', 'Home::login', ['namespace' => 'App\Controllers']);
$routes->get('erp/login', 'Home::login', ['namespace' => 'App\Controllers']);
$routes->post('erp/auth/login', 'Auth::login', ['namespace' => 'App\Controllers\Erp']);
$routes->get('erp/desk', 'Dashboard::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/dashboard/revenue-chart', 'Dashboard::revenue_chart', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/dashboard/kpi-refresh', 'Dashboard::kpi_refresh', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/global-search', 'Dashboard::global_search', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/notifications', 'Dashboard::notifications', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/notifications-page', 'Dashboard::notifications_page', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->post('erp/notifications/mark-read', 'Dashboard::mark_notification_read', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->post('erp/notifications/mark-all-read', 'Dashboard::mark_all_read', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->post('erp/notifications/delete', 'Dashboard::delete_notification', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->post('erp/notifications/delete-all', 'Dashboard::delete_all_notifications', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/system-logout', 'Logout::index', ['namespace' => 'App\Controllers\Erp']);
$routes->get('erp/set-language/(:segment)', 'Dashboard::language/$1', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/forgot-password', 'Auth::forgot_password', ['namespace' => 'App\Controllers\Erp']);
$routes->get('erp/verified-password', 'Auth::verified_password', ['namespace' => 'App\Controllers\Erp']);
$routes->match(['get', 'post'],'erp/auth/unlock', 'Auth::unlock', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// Two-Factor Authentication (2FA)
$routes->get('erp/verify-2fa', 'Auth::show_2fa', ['namespace' => 'App\Controllers\Erp', 'filter' => 'noauth']);
$routes->post('erp/auth/verify-2fa', 'Auth::verify_2fa', ['namespace' => 'App\Controllers\Erp', 'filter' => 'noauth']);
$routes->post('erp/profile/setup-2fa', 'Profile::setup_2fa', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->post('erp/profile/verify-2fa-setup', 'Profile::verify_2fa_setup', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->post('erp/profile/disable-2fa', 'Profile::disable_2fa', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/my-profile', 'Profile::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/system-logout', 'Logout::index', ['namespace' => 'App\Controllers\Erp']);
/////Super User Modules
//4: Languages
$routes->get('erp/all-languages', 'Languages::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'],'erp/languages/add_language', 'Languages::add_language', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'],'erp/languages/delete_language', 'Languages::delete_language', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'],'erp/languages/language_status', 'Languages::language_status', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
//7: System||Settings|| STD
$routes->get('erp/currency-converter', 'Settings::currency_converter', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
//STD
$routes->get('erp/system-settings', 'Settings::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// Super Admin Settings
$routes->get('erp/system-payment-settings', 'Settings::super_settings/payments', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/system-sms-settings', 'Settings::super_settings/sms', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/system-api-settings', 'Settings::super_settings/api', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/system-tax-settings', 'Settings::super_settings/tax', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->post('erp/settings/save_super_settings', 'Settings::save_super_settings', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->match(['get', 'post'],'erp/settings/system_info', 'Settings::system_info', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'],'erp/settings/add_logo', 'Settings::add_logo', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'],'erp/settings/add_favicon', 'Settings::add_favicon', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'],'erp/settings/add_singin_logo', 'Settings::add_singin_logo', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'],'erp/settings/update_payment_gateway', 'Settings::update_payment_gateway', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'],'erp/settings/email_info', 'Settings::email_info', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'],'erp/settings/notification_position_info', 'Settings::notification_position_info', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
//8: System||Constants
$routes->get('erp/system-constants', 'Settings::constants', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->match(['get', 'post'], 'erp/settings/company_type_info', 'Settings::company_type_info', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->match(['get', 'post'], 'erp/settings/update_company_type', 'Settings::update_company_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'], 'erp/settings/delete_company_type', 'Settings::delete_company_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'], 'erp/settings/currency_type_info', 'Settings::currency_type_info', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'], 'erp/settings/update_currency_type', 'Settings::update_currency_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'], 'erp/settings/delete_currency_type', 'Settings::delete_currency_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
//9: System||Database Backup
$routes->get('erp/theme-settings', 'Settings::theme_settings', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->post('erp/settings/save_theme', 'Settings::save_theme', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/system-backup', 'Settings::database_backup', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'], 'erp/settings/create_database_backup', 'Settings::create_database_backup', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'], 'erp/settings/delete_db_backup', 'Settings::delete_db_backup', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'], 'erp/settings/delete_dbsingle_backup', 'Settings::delete_dbsingle_backup', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
//10: System||Email Templates
$routes->get('erp/email-templates', 'Settings::email_templates', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'], 'erp/settings/update_template', 'Settings::update_template', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->get('erp/sms-templates', 'Settings::sms_templates', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
$routes->match(['get', 'post'], 'erp/settings/update_sms_template', 'Settings::update_template', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin',]);
// AJAX list endpoints for settings DataTables
$routes->get('erp/settings/company_type_list', 'Settings::company_type_list', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/settings/religion_list', 'Settings::religion_list', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/settings/currency_list', 'Settings::currency_list', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/settings/payment_method_list', 'Settings::payment_method_list', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/settings/email_template_list', 'Settings::email_template_list', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/settings/sms_template_list', 'Settings::sms_template_list', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/settings/database_backup_list', 'Settings::database_backup_list', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/settings/read', 'Settings::read', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/settings/add_religion_info', 'Settings::add_religion_info', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/settings/update_religion_info', 'Settings::update_religion_info', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/settings/delete_religion_type', 'Settings::delete_religion_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/settings/update_currency', 'Settings::update_currency', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);

/***************************************************************************************************************/
/***************************************************************************************************************/
/***************************************************************************************************************/

/////Company|Staff Modules
//1: Staff Roles
$routes->get('erp/set-roles', 'Roles::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->match(['get', 'post'], 'erp/roles/add_role', 'Roles::add_role', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->match(['get', 'post'], 'erp/roles/update_role', 'Roles::update_role', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->match(['get', 'post'], 'erp/roles/delete_role', 'Roles::delete_role', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
//2: Assets
$routes->get('erp/assets-list', 'Assets::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->get('erp/asset-view/(:segment)', 'Assets::asset_view', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->match(['get', 'post'], 'erp/assets/assets_list', 'Assets::assets_list', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->match(['get', 'post'], 'erp/assets/read_asset', 'Assets::read_asset', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->match(['get', 'post'], 'erp/assets/add_asset', 'Assets::add_asset', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->match(['get', 'post'], 'erp/assets/update_asset', 'Assets::update_asset', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->match(['get', 'post'], 'erp/assets/delete_asset', 'Assets::delete_asset', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);


// module types
$routes->get('erp/assets-category', 'Types::asset_category', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->get('erp/assets-brand', 'Types::asset_brand', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin,companyauth']);
$routes->get('erp/leave-type', 'Types::leave_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/award-type', 'Types::award_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/arrangement-type', 'Types::arrangement_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/exit-type', 'Types::exit_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/income-type', 'Types::income_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/expense-type', 'Types::expense_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/competencies', 'Types::competencies', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/goal-type', 'Types::goal_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/training-skills', 'Types::training_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/case-type', 'Types::case_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/tax-type', 'Types::tax_type', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/jobs-categories', 'Types::jobs_categories', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/customers-group', 'Types::customers_group', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/products-category', 'Types::product_category', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);

// core hr dashboard
$routes->get('erp/corehr-dashboard', 'Department::corehr_dashboard', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// department
$routes->get('erp/departments-list', 'Department::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// designation
$routes->get('erp/designation-list', 'Designation::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// announcements
$routes->get('erp/news-list', 'Announcements::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/announcement-view/(:segment)', 'Announcements::announcement_view', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// policies
$routes->get('erp/policies-list', 'Policies::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/all-policies', 'Policies::staff_policies_all', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// staff
$routes->get('erp/staff-list', 'Employees::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/staff-grid', 'Employees::staff_grid', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/staff-dashboard', 'Employees::staff_dashboard', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/organization-chart', 'Employees::staff_chart', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/employee-details/(:segment)', 'Employees::staff_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/employee-qr/(:num)', 'Erp\Employees::employee_qr/$1', ['filter' => 'checklogin']);
$routes->get('erp/employee-id-card/(:num)', 'Erp\Employees::employee_id_card/$1', ['filter' => 'checklogin']);
// awards
$routes->get('erp/awards-list', 'Awards::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/award-view/(:segment)', 'Awards::award_view', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// travel
$routes->get('erp/business-travel', 'Travel::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/travel-calendar', 'Travel::travel_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/view-travel-info/(:segment)', 'Travel::travel_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// complaints
$routes->get('erp/complaints-list', 'Complaints::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// resignation
$routes->get('erp/resignation-list', 'Resignation::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// transfer
$routes->get('erp/transfers-list', 'Transfers::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// employee exit
$routes->get('erp/employee-exit', 'Leaving::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// documents || upload files, official and expired documents
$routes->get('erp/upload-files', 'Documents::upload_files', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/official-documents', 'Documents::official_documents', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/expired-documents', 'Documents::expired_documents', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// warning
$routes->get('erp/disciplinary-cases', 'Warning::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// tickets
$routes->get('erp/support-tickets', 'Tickets::tickets_page', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/create-ticket', 'Tickets::create_ticket', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/helpdesk-dashboard', 'Tickets::helpdesk_dashboard', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/ticket-view/(:segment)', 'Tickets::ticket_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// training
$routes->get('erp/training-sessions', 'Training::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/training-details/(:segment)', 'Training::training_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/training-calendar', 'Training::training_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// trainers
$routes->get('erp/trainers-list', 'Trainers::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// events
$routes->get('erp/events-list', 'Events::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/events-calendar', 'Events::events_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// meetings
$routes->get('erp/meeting-list', 'Conference::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/meetings-calendar', 'Conference::meetings_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// leave
$routes->get('erp/leave-list', 'Leave::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/leave-status', 'Leave::leave_status', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/view-leave-info/(:segment)', 'Leave::view_leave', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/leave-calendar', 'Leave::leave_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// holidays
$routes->get('erp/holidays-list', 'Holidays::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/holidays-calendar', 'Holidays::holidays_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// officeshifts
$routes->get('erp/office-shifts', 'Officeshifts::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// tasks||Staff
$routes->get('erp/tasks-list', 'Tasks::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/tasks-grid', 'Tasks::tasks_grid', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/task-detail/(:segment)', 'Tasks::task_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/tasks-summary', 'Tasks::tasks_summary', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/tasks-calendar', 'Tasks::tasks_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/tasks-scrum-board', 'Tasks::tasks_scrum_board', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// tasks||Clients
$routes->get('erp/my-tasks-list', 'Tasks::task_client', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/task-details/(:segment)', 'Tasks::client_task_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// clients
$routes->get('erp/clients-list', 'Clients::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/clients-grid', 'Clients::clients_grid', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/view-client-info/(:segment)', 'Clients::client_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// leads
$routes->get('erp/leads-list', 'Clients::leads_index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/view-lead-info/(:segment)', 'Clients::lead_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// performance
$routes->get('erp/performance-indicator-list', 'Talent::performance_indicator', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/kpi-details/(:segment)', 'Talent::indicator_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/performance-appraisal-list', 'Talent::performance_appraisal', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/kpa-details/(:segment)', 'Talent::appraisal_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/track-goals', 'Trackgoals::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/goal-details/(:segment)', 'Trackgoals::goal_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/goals-calendar', 'Trackgoals::goals_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// visitors
$routes->get('erp/visitors-list', 'Visitors::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// todo
$routes->get('erp/todo-list', 'Todo::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// organization chart
$routes->get('erp/chart', 'Application::org_chart', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// org chart module (5.4)
$routes->get('erp/org-chart', 'Orgchart::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/org-chart/data', 'Orgchart::get_tree_data', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// subscription
$routes->get('erp/my-subscription', 'Subscription::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/subscription-expired', 'Subscription::subscription_expired', ['namespace' => 'App\Controllers\Erp']);
$routes->get('erp/upgrade-subscription/(:segment)', 'Subscription::upgrade_subscription', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/subscription-list', 'Subscription::more_subscriptions', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// payment history
$routes->get('erp/my-payment-history', 'Paymenthistory::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/payment-details/(:segment)', 'Paymenthistory::billing_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// subscription invoices (Phase 4.5)
$routes->get('erp/subscription-invoices', 'Membership::invoice_history', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/subscription-invoice-download/(:num)', 'Membership::download_invoice/$1', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/all-subscription-invoices', 'Membership::all_invoices', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// contact support
$routes->get('erp/contact-support', 'Contact::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// payroll
$routes->get('erp/payroll-list', 'Payroll::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/payroll-view/(:segment)', 'Payroll::payroll_view', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/payslip-history', 'Payroll::payroll_history', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/advance-salary', 'Payroll::advance_salary', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/loan-request', 'Payroll::request_loan', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// invoices || Staff
$routes->get('erp/invoices-list', 'Invoices::project_invoices', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/invoice-dashboard', 'Invoices::invoice_dashboard', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/invoice-payments-list', 'Invoices::project_invoice_payment', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/create-new-invoice', 'Invoices::create_invoice', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/invoice-calendar', 'Invoices::invoice_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/invoice-detail/(:segment)', 'Invoices::invoice_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/edit-invoice/(:segment)', 'Invoices::edit_invoice', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/print-invoice/(:segment)', 'Invoices::view_project_invoice', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// estimates || Staff
$routes->get('erp/estimates-list', 'Estimates::project_estimates', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/create-new-estimate', 'Estimates::create_estimate', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/estimates-calendar', 'Estimates::estimates_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/estimate-detail/(:segment)', 'Estimates::estimate_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/edit-estimate/(:segment)', 'Estimates::edit_estimate', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/print-estimate/(:segment)', 'Estimates::view_project_estimate', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// invoices || Clients
$routes->get('erp/my-invoices-list', 'Invoices::invoices_client', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/my-invoice-payments-list', 'Invoices::client_invoice_payment', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/my-invoices-calendar', 'Invoices::client_invoice_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// staff attendance
$routes->get('erp/timesheet-dashboard', 'Timesheet::timesheet_dashboard', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/attendance-list', 'Timesheet::attendance', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/attendance-info/(:segment)/(:segment)', 'Timesheet::attendance_view', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/manual-attendance', 'Timesheet::update_attendance', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/monthly-attendance-view', 'Timesheet::monthly_timesheet', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/monthly-attendance', 'Timesheet::monthly_timesheet_filter', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/timesheet-calendar', 'Timesheet::timesheet_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// live attendance dashboard (Phase 6.4)
$routes->get('erp/attendance-live', 'AttendanceLive::index', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/attendance-live/stream', 'AttendanceLive::stream', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// overtime request
$routes->get('erp/overtime-request', 'Timesheet::overtime_request', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// custom fields
$routes->get('erp/custom-fields', 'Customfields::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// Finance
$routes->get('erp/finance-dashboard', 'Finance::finance_dashboard', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/payees-list', 'Finance::payees', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/payers-list', 'Finance::payers', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/accounts-list', 'Finance::bank_cash', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/deposit-list', 'Finance::deposit', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/expense-list', 'Finance::expense', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/transfer-list', 'Finance::transfer', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/account-ledger/(:segment)', 'Finance::account_ledger', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/transactions-list', 'Finance::transactions', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/transaction-details/(:segment)', 'Finance::transaction_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// HR System
$routes->get('erp/system-reports', 'Application::reports', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/attendance-report', 'Reports::attendance_report', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/payroll-report', 'Reports::payroll_report', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/project-report', 'Reports::project_report', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/task-report', 'Reports::task_report', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/invoice-report', 'Reports::invoice_report', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/leave-report', 'Reports::leave_report', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/training-report', 'Reports::training_report', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/account-statement', 'Reports::account_statement', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/purchases-report', 'Reports::purchases_report', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/sales-order-report', 'Reports::salesorder_report', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/system-import', 'Application::import', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/system-calendar', 'Application::erp_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/company-settings', 'Application::company_settings', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/company-constants', 'Application::company_constants', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// recruitment
$routes->get('erp/jobs-dashboard', 'Recruitment::recruitment_dashboard', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/jobs-list', 'Recruitment::jobs', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/view-job/(:segment)', 'Recruitment::job_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/candidates-list', 'Recruitment::candidates', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/jobs-interviews', 'Recruitment::interviews', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/promotion-list', 'Recruitment::promotions', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/create-new-job', 'Recruitment::create_job', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/edit-a-job/(:segment)', 'Recruitment::edit_job', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// projects||Staff
$routes->get('erp/projects-dashboard', 'Projects::projects_dashboard', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/projects-list', 'Projects::projects', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/projects-calendar', 'Projects::projects_calendar', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/projects-scrum-board', 'Projects::projects_scrum_board', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/project-detail/(:segment)', 'Projects::project_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/projects-grid', 'Projects::projects_grid', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// projects||Clients
$routes->get('erp/my-projects-list', 'Projects::projects_client', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/project-details/(:segment)', 'Projects::client_project_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
/***************************************************************************************************************/
/***************************************************************************************************************/
// INVENTORY
$routes->get('erp/suppliers-list', 'Suppliers::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/warehouse-list', 'Warehouse::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/product-list', 'Products::index', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/product-view/(:segment)', 'Products::product_view', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/out-of-stock-products', 'Products::out_of_stock', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/expired-products', 'Products::expired_stock', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// purchases
$routes->get('erp/create-purchase', 'Purchases::create_purchase', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/stock-purchases', 'Purchases::stock_purchases', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/purchase-detail/(:segment)', 'Purchases::purchase_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/edit-purchase/(:segment)', 'Purchases::edit_purchase', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/print-purchase/(:segment)', 'Purchases::view_purchase_invoice', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// orders
$routes->get('erp/create-order', 'Orders::create_order', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/stock-orders', 'Orders::stock_orders', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/paid-orders', 'Orders::paid_orders', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/unpaid-orders', 'Orders::unpaid_orders', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/packed-orders', 'Orders::packed_orders', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/delivered-orders', 'Orders::delivered_orders', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/cancelled-orders', 'Orders::cancelled_orders', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/order-detail/(:segment)', 'Orders::order_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/edit-order/(:segment)', 'Orders::edit_order', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/print-order/(:segment)', 'Orders::view_order_invoice', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
// Order quotes
$routes->get('erp/create-quote', 'Orderquotes::create_quote', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/order-quotes', 'Orderquotes::stock_quoteorders', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/quote-detail/(:segment)', 'Orderquotes::quote_details', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/edit-quote/(:segment)', 'Orderquotes::edit_quote', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);
$routes->get('erp/print-quote/(:segment)', 'Orderquotes::view_quote_order', ['namespace' => 'App\Controllers\Erp','filter' => 'checklogin']);

// Broadcasts (Phase 5.5)
$routes->get('erp/broadcasts', 'Broadcasts::index', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/broadcasts/create', 'Broadcasts::create', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->post('erp/broadcasts/save-draft', 'Broadcasts::save_draft', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->post('erp/broadcasts/preview', 'Broadcasts::preview', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->post('erp/broadcasts/send', 'Broadcasts::send', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/broadcasts/details/(:num)', 'Broadcasts::details/$1', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/broadcasts/templates', 'Broadcasts::templates', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->post('erp/broadcasts/save-template', 'Broadcasts::save_template', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/broadcasts/recipient-count', 'Broadcasts::recipient_count', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

// Phase 10.7–10.8: Archive Portal (super_user only)
$routes->get('erp/archive', 'Erp\Archive::index', ['filter' => 'checklogin']);
$routes->get('erp/archive/companies', 'Erp\Archive::companies', ['filter' => 'checklogin']);
$routes->get('erp/archive/company/(:num)', 'Erp\Archive::company_detail/$1', ['filter' => 'checklogin']);
$routes->get('erp/archive/search', 'Erp\Archive::search', ['filter' => 'checklogin']);
$routes->get('erp/archive/contacts', 'Erp\Archive::contacts', ['filter' => 'checklogin']);
$routes->get('erp/archive/contacts-list', 'Erp\Archive::contacts_list', ['filter' => 'checklogin']);
$routes->get('erp/archive/vault', 'Erp\Archive::vault', ['filter' => 'checklogin']);
$routes->get('erp/archive/download/(:num)', 'Erp\Archive::download_bundle/$1', ['filter' => 'checklogin']);
$routes->get('erp/archive/settings', 'Erp\Archive::settings', ['filter' => 'checklogin']);
$routes->post('erp/archive/trigger', 'Erp\Archive::trigger_archive', ['filter' => 'checklogin']);
$routes->post('erp/archive/restore/(:num)', 'Erp\Archive::restore_company/$1', ['filter' => 'checklogin']);

// Server-side DataTables endpoints
$routes->post('erp/employees-list-server', 'Erp\Employees::employees_list_server', ['filter' => 'checklogin']);
$routes->post('erp/attendance-list-server', 'Erp\Timesheet::attendance_list_server', ['filter' => 'checklogin']);
$routes->post('erp/payroll-list-server', 'Erp\Payroll::payroll_list_server', ['filter' => 'checklogin']);
$routes->post('erp/leave-list-server', 'Erp\Leave::leave_list_server', ['filter' => 'checklogin']);
$routes->post('erp/invoices-list-server', 'Erp\Invoices::invoices_list_server', ['filter' => 'checklogin']);
$routes->post('erp/visitors-list-server', 'Erp\Visitors::visitors_list_server', ['filter' => 'checklogin']);

// Phase 7.3: Expense Claims
$routes->get('erp/expenses', 'Erp\Expenses::index', ['filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/expenses/list', 'Erp\Expenses::expenses_list', ['filter' => 'checklogin']);
$routes->post('erp/expenses/add', 'Erp\Expenses::add_expense', ['filter' => 'checklogin']);
$routes->post('erp/expenses/approve', 'Erp\Expenses::approve_expense', ['filter' => 'checklogin']);
$routes->post('erp/expenses/reject', 'Erp\Expenses::reject_expense', ['filter' => 'checklogin']);
$routes->post('erp/expenses/delete', 'Erp\Expenses::delete_expense', ['filter' => 'checklogin']);
$routes->get('erp/expense-categories', 'Erp\Expenses::categories', ['filter' => 'checklogin']);
$routes->post('erp/expenses/add-category', 'Erp\Expenses::add_category', ['filter' => 'checklogin']);
$routes->post('erp/expenses/update-category', 'Erp\Expenses::update_category', ['filter' => 'checklogin']);
$routes->post('erp/expenses/delete-category', 'Erp\Expenses::delete_category', ['filter' => 'checklogin']);
$routes->get('erp/expense-report', 'Erp\Expenses::expense_report', ['filter' => 'checklogin']);

// Archive Export (Phase 10.10)
$routes->get('erp/archive/export', 'Erp\ArchiveExport::export', ['filter' => 'checklogin']);

/***************************************************************************************************************/
/***************************************************************************************************************/
// MISSING SIDEBAR & PAGE ROUTES
/***************************************************************************************************************/

// Super Admin: Companies
$routes->get('erp/companies-list', 'Companies::index', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/company-detail/(:segment)', 'Companies::company_details', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/companies/companies_list', 'Companies::companies_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/companies/add_company', 'Companies::add_company', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/companies/update_company', 'Companies::update_company', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/companies/update_basic_info', 'Companies::update_basic_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/companies/update_plan', 'Companies::update_plan', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/companies/update_company_photo', 'Companies::update_company_photo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/companies/update_company_info', 'Companies::update_company_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/companies/read', 'Companies::read', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/companies/delete_company', 'Companies::delete_company', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

// Super Admin: Membership Plans
$routes->get('erp/membership-list', 'Membership::index', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/membership-detail/(:segment)', 'Membership::membership_details', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membership/membership_list', 'Membership::membership_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membership/add_membership', 'Membership::add_membership', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membership/update_membership', 'Membership::update_membership', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membership/read', 'Membership::read', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membership/membership_type_chart', 'Membership::membership_type_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membership/membership_by_country_chart', 'Membership::membership_by_country_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membership/delete_membership', 'Membership::delete_membership', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membership/invoice_history_list', 'Membership::invoice_history_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membership/all_invoices_list', 'Membership::all_invoices_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

// Super Admin: Super Users
$routes->get('erp/super-users', 'Users::index', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/user-detail/(:segment)', 'Users::user_details', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/users_list', 'Users::users_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/add_user', 'Users::add_user', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/update_user', 'Users::update_user', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/add_role', 'Users::add_role', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/update_profile_photo', 'Users::update_profile_photo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/update_role', 'Users::update_role', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/roles_list', 'Users::roles_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/read', 'Users::read', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/read_role', 'Users::read_role', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/delete_user', 'Users::delete_user', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/users/delete_role', 'Users::delete_role', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

// Super Admin: User Roles
$routes->get('erp/users-role', 'Users::role', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

// Super Admin: Billing Invoices
$routes->get('erp/billing-invoices', 'Membershipinvoices::index', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/billing-detail/(:segment)', 'Membershipinvoices::billing_details', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membershipinvoices/billing_list', 'Membershipinvoices::billing_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/membershipinvoices/membership_invoice_amount_chart', 'Membershipinvoices::membership_invoice_amount_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Employees
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/employees/employees_list', 'Employees::employees_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/add_employee', 'Employees::add_employee', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_basic_info', 'Employees::update_basic_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_bio', 'Employees::update_bio', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_social', 'Employees::update_social', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_bankinfo', 'Employees::update_bankinfo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_contact_info', 'Employees::update_contact_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_company_info', 'Employees::update_company_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_contract_info', 'Employees::update_contract_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_account_info', 'Employees::update_account_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_profile_photo', 'Employees::update_profile_photo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_password', 'Employees::update_password', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/is_designation', 'Employees::is_designation', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/dialog_user_data', 'Employees::dialog_user_data', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/delete_staff', 'Employees::delete_staff', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/allowances_list', 'Employees::allowances_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/commissions_list', 'Employees::commissions_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/statutory_list', 'Employees::statutory_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/other_payments_list', 'Employees::other_payments_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/user_documents_list', 'Employees::user_documents_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/add_allowance', 'Employees::add_allowance', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_allowance', 'Employees::update_allowance', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/add_commissions', 'Employees::add_commissions', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_commission', 'Employees::update_commission', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/add_statutory', 'Employees::add_statutory', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_statutory', 'Employees::update_statutory', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/add_otherpayment', 'Employees::add_otherpayment', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_other_payments', 'Employees::update_other_payments', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/add_document', 'Employees::add_document', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/update_document', 'Employees::update_document', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/delete_all_allowances', 'Employees::delete_all_allowances', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/delete_all_commissions', 'Employees::delete_all_commissions', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/delete_all_statutory_deductions', 'Employees::delete_all_statutory_deductions', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/delete_all_other_payments', 'Employees::delete_all_other_payments', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/employees/delete_document', 'Employees::delete_document', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/employees/staff_chart', 'Employees::staff_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Timesheet / Attendance
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/timesheet/attendance_list', 'Timesheet::attendance_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/update_attendance_list', 'Timesheet::update_attendance_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/overtime_request_list', 'Timesheet::overtime_request_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/add_attendance', 'Timesheet::add_attendance', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/add_overtime', 'Timesheet::add_overtime', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/update_attendance_record', 'Timesheet::update_attendance_record', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/update_overtime_record', 'Timesheet::update_overtime_record', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/update_attendance_add', 'Timesheet::update_attendance_add', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/read_overtime_request', 'Timesheet::read_overtime_request', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/delete_overtime', 'Timesheet::delete_overtime', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/delete_attendance', 'Timesheet::delete_attendance', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/set_clocking', 'Timesheet::set_clocking', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/timesheet/staff_working_status_chart', 'Timesheet::staff_working_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Leave
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/leave/leave_list', 'Leave::leave_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leave/add_leave', 'Leave::add_leave', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leave/update_leave', 'Leave::update_leave', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leave/update_leave_status', 'Leave::update_leave_status', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leave/read_leave', 'Leave::read_leave', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leave/get_employee_assigned_leave_types', 'Leave::get_employee_assigned_leave_types', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leave/leave_type_chart', 'Leave::leave_type_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leave/leave_status_chart', 'Leave::leave_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leave/delete_leave', 'Leave::delete_leave', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Payroll
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/payroll/payslip_list', 'Payroll::payslip_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/advance_salary_list', 'Payroll::advance_salary_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/loan_list', 'Payroll::loan_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/payslip_history_list', 'Payroll::payslip_history_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/add_advance_salary', 'Payroll::add_advance_salary', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/edit_advance_salary', 'Payroll::edit_advance_salary', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/add_loan', 'Payroll::add_loan', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/edit_loan', 'Payroll::edit_loan', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/add_pay_monthly', 'Payroll::add_pay_monthly', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/payroll_chart', 'Payroll::payroll_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/staff_payroll_chart', 'Payroll::staff_payroll_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/read_advance_salary', 'Payroll::read_advance_salary', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/read_loan', 'Payroll::read_loan', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/read_payroll', 'Payroll::read_payroll', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/delete_advance_salary', 'Payroll::delete_advance_salary', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/delete_loan', 'Payroll::delete_loan', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/payroll/delete_payslip', 'Payroll::delete_payslip', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Department
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/department/departments_list', 'Department::departments_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/department/add_department', 'Department::add_department', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/department/update_department', 'Department::update_department', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/department/read_department', 'Department::read_department', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/department/department_wise_chart', 'Department::department_wise_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/department/delete_department', 'Department::delete_department', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Designation
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/designation/designation_list', 'Designation::designation_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/designation/add_designation', 'Designation::add_designation', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/designation/update_designation', 'Designation::update_designation', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/designation/read_designation', 'Designation::read_designation', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/designation/designation_wise_chart', 'Designation::designation_wise_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/designation/delete_designation', 'Designation::delete_designation', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Announcements
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/announcements/announcement_list', 'Announcements::announcement_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/announcements/add_announcement', 'Announcements::add_announcement', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/announcements/update_announcement', 'Announcements::update_announcement', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/announcements/read_announcement', 'Announcements::read_announcement', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/announcements/delete_announcement', 'Announcements::delete_announcement', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Policies
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/policies/policies_list', 'Policies::policies_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/policies/add_policy', 'Policies::add_policy', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/policies/update_policy', 'Policies::update_policy', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/policies/read_policy', 'Policies::read_policy', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/policies/delete_policy', 'Policies::delete_policy', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Warning (Disciplinary)
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/warning/warning_list', 'Warning::warning_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/warning/add_warning', 'Warning::add_warning', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/warning/update_warning', 'Warning::update_warning', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/warning/read_warning', 'Warning::read_warning', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/warning/delete_warning', 'Warning::delete_warning', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Tickets (Helpdesk)
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/tickets/tickets_list', 'Tickets::tickets_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/add_ticket', 'Tickets::add_ticket', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/add_ticket_reply', 'Tickets::add_ticket_reply', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/add_note', 'Tickets::add_note', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/add_attachment', 'Tickets::add_attachment', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/update_ticket_status', 'Tickets::update_ticket_status', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/update_ticket', 'Tickets::update_ticket', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/read_ticket', 'Tickets::read_ticket', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/tickets_status_chart', 'Tickets::tickets_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/tickets_priority_chart', 'Tickets::tickets_priority_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/staff_tickets_status_chart', 'Tickets::staff_tickets_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/staff_tickets_priority_chart', 'Tickets::staff_tickets_priority_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/is_department', 'Tickets::is_department', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/delete_ticket', 'Tickets::delete_ticket', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/delete_ticket_note', 'Tickets::delete_ticket_note', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/delete_ticket_reply', 'Tickets::delete_ticket_reply', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tickets/delete_ticket_file', 'Tickets::delete_ticket_file', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Training
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/training/training_list', 'Training::training_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/training/add_training', 'Training::add_training', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/training/update_training', 'Training::update_training', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/training/update_training_status', 'Training::update_training_status', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/training/add_note', 'Training::add_note', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/training/read_training', 'Training::read_training', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/training/delete_training', 'Training::delete_training', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/training/delete_training_note', 'Training::delete_training_note', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Performance (Talent / Indicators / Appraisals)
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/talent/indicator_list', 'Talent::indicator_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/talent/appraisal_list', 'Talent::appraisal_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/talent/add_indicator', 'Talent::add_indicator', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/talent/update_indicator', 'Talent::update_indicator', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/talent/add_appraisal', 'Talent::add_appraisal', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/talent/update_appraisal', 'Talent::update_appraisal', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/talent/delete_indicator', 'Talent::delete_indicator', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/talent/delete_appraisal', 'Talent::delete_appraisal', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Goal Tracking
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/trackgoals/goals_list', 'Trackgoals::goals_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/trackgoals/add_tracking', 'Trackgoals::add_tracking', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/trackgoals/update_goal_tracking', 'Trackgoals::update_goal_tracking', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/trackgoals/update_rating', 'Trackgoals::update_rating', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/trackgoals/add_work', 'Trackgoals::add_work', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/trackgoals/delete_goal', 'Trackgoals::delete_goal', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Tasks
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/tasks/tasks_list', 'Tasks::tasks_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/client_tasks_list', 'Tasks::client_tasks_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/client_profile_tasks_list', 'Tasks::client_profile_tasks_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/add_task', 'Tasks::add_task', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/update_task', 'Tasks::update_task', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/update_task_progress', 'Tasks::update_task_progress', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/add_note', 'Tasks::add_note', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/update_task_status', 'Tasks::update_task_status', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/add_discussion', 'Tasks::add_discussion', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/add_attachment', 'Tasks::add_attachment', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/task_status_chart', 'Tasks::task_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/staff_task_status_chart', 'Tasks::staff_task_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/client_task_status_chart', 'Tasks::client_task_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/tasks_by_projects_chart', 'Tasks::tasks_by_projects_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/delete_task', 'Tasks::delete_task', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/delete_task_note', 'Tasks::delete_task_note', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/delete_task_discussion', 'Tasks::delete_task_discussion', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/tasks/delete_task_file', 'Tasks::delete_task_file', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Projects
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/projects/projects_list', 'Projects::projects_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/timelogs_list', 'Projects::timelogs_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/project_tasks_list', 'Projects::project_tasks_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/client_projects_list', 'Projects::client_projects_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/client_profile_projects_list', 'Projects::client_profile_projects_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/add_project', 'Projects::add_project', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/update_project', 'Projects::update_project', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/update_project_progress', 'Projects::update_project_progress', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/add_note', 'Projects::add_note', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/add_bug', 'Projects::add_bug', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/add_timelogs', 'Projects::add_timelogs', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/update_timelog', 'Projects::update_timelog', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/add_discussion', 'Projects::add_discussion', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/add_attachment', 'Projects::add_attachment', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/update_project_status', 'Projects::update_project_status', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/project_status_chart', 'Projects::project_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/staff_project_status_chart', 'Projects::staff_project_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/client_project_status_chart', 'Projects::client_project_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/projects_priority_chart', 'Projects::projects_priority_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/read_timelog', 'Projects::read_timelog', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/delete_project', 'Projects::delete_project', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/delete_project_note', 'Projects::delete_project_note', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/delete_project_bug', 'Projects::delete_project_bug', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/delete_project_discussion', 'Projects::delete_project_discussion', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/delete_timelog', 'Projects::delete_timelog', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/projects/delete_project_file', 'Projects::delete_project_file', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/project-timelogs/(:segment)', 'Projects::project_timelogs', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/project-invoices/(:segment)', 'Projects::invoices', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/project-payments/(:segment)', 'Projects::payments_history', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/project-taxes/(:segment)', 'Projects::invoice_taxes', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/project-quotes/(:segment)', 'Projects::quotes', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Clients & Leads
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/clients/clients_list', 'Clients::clients_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/leads_list', 'Clients::leads_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/leads_followup_list', 'Clients::leads_followup_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/add_followup', 'Clients::add_followup', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/update_followup', 'Clients::update_followup', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/add_client', 'Clients::add_client', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/add_lead', 'Clients::add_lead', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/update_lead', 'Clients::update_lead', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/update_client', 'Clients::update_client', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/update_client_status', 'Clients::update_client_status', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/update_profile_photo', 'Clients::update_profile_photo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/update_lead_profile_photo', 'Clients::update_lead_profile_photo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/update_password_opt', 'Clients::update_password_opt', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/update_password', 'Clients::update_password', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/convert_lead', 'Clients::convert_lead', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/read_followup', 'Clients::read_followup', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/read_lead', 'Clients::read_lead', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/delete_client', 'Clients::delete_client', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/delete_lead', 'Clients::delete_lead', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/clients/delete_lead_followup', 'Clients::delete_lead_followup', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Invoices
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/invoices/invoices_list', 'Invoices::invoices_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/client_invoices_list', 'Invoices::client_invoices_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/client_profile_invoices_list', 'Invoices::client_profile_invoices_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/project_billing_list', 'Invoices::project_billing_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/client_project_billing_list', 'Invoices::client_project_billing_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/create_new_invoice', 'Invoices::create_new_invoice', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/update_invoice', 'Invoices::update_invoice', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/delete_invoice_items', 'Invoices::delete_invoice_items', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/invoice_status_chart', 'Invoices::invoice_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/invoice_amount_chart', 'Invoices::invoice_amount_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/client_invoice_amount_chart', 'Invoices::client_invoice_amount_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/read_invoice_data', 'Invoices::read_invoice_data', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/pay_invoice_record', 'Invoices::pay_invoice_record', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/invoices/delete_invoice', 'Invoices::delete_invoice', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Estimates
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/estimates/create_new_estimate', 'Estimates::create_new_estimate', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/estimates/update_estimate', 'Estimates::update_estimate', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/estimates/delete_estimate_items', 'Estimates::delete_estimate_items', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/estimates/read_estimate_data', 'Estimates::read_estimate_data', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/estimates/cancel_estimate_record', 'Estimates::cancel_estimate_record', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/estimates/convert_estimate_record', 'Estimates::convert_estimate_record', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/estimates/delete_estimate', 'Estimates::delete_estimate', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Finance
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/finance/accounts_list', 'Finance::accounts_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/deposit_list', 'Finance::deposit_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/expense_list', 'Finance::expense_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/transaction_list', 'Finance::transaction_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/add_account', 'Finance::add_account', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/add_deposit', 'Finance::add_deposit', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/add_expense', 'Finance::add_expense', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/update_deposit', 'Finance::update_deposit', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/update_expense', 'Finance::update_expense', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/update_account', 'Finance::update_account', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/read_accounts', 'Finance::read_accounts', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/read_transactions', 'Finance::read_transactions', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/delete_account', 'Finance::delete_account', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/finance/delete_transaction', 'Finance::delete_transaction', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/finance-dashboard', 'Finance::finance_dashboard', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/payees-list', 'Finance::payees', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->get('erp/payers-list', 'Finance::payers', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Recruitment
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/recruitment/jobs_list', 'Recruitment::jobs_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/candidates_list', 'Recruitment::candidates_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/interview_list', 'Recruitment::interview_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/promotion_list', 'Recruitment::promotion_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/add_job', 'Recruitment::add_job', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/update_job', 'Recruitment::update_job', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/update_candidate_status', 'Recruitment::update_candidate_status', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/update_interview_status', 'Recruitment::update_interview_status', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/apply_job', 'Recruitment::apply_job', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/read_candidate', 'Recruitment::read_candidate', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/jobs_status_chart', 'Recruitment::jobs_status_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/jobs_type_chart', 'Recruitment::jobs_type_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/job_by_designation_chart', 'Recruitment::job_by_designation_chart', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/recruitment/delete_job', 'Recruitment::delete_job', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Types (Constants)
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/types/assets_category_list', 'Types::assets_category_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/products_category_list', 'Types::products_category_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/arrangement_type_list', 'Types::arrangement_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/exit_type_list', 'Types::exit_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/income_type_list', 'Types::income_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/expense_type_list', 'Types::expense_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/competencies_list', 'Types::competencies_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/org_competencies_list', 'Types::org_competencies_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/goal_type_list', 'Types::goal_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/training_type_list', 'Types::training_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/case_type_list', 'Types::case_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/tax_type_list', 'Types::tax_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/jobs_categories_list', 'Types::jobs_categories_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/customers_group_list', 'Types::customers_group_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/leave_type_list', 'Types::leave_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/award_type_list', 'Types::award_type_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/assets_brand_list', 'Types::assets_brand_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_asset_category', 'Types::read_asset_category', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_product_category', 'Types::read_product_category', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_leave_type', 'Types::read_leave_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_award_type', 'Types::read_award_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_asset_brand', 'Types::read_asset_brand', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_arrangement_type', 'Types::read_arrangement_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_exit_type', 'Types::read_exit_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_income_type', 'Types::read_income_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_expense_type', 'Types::read_expense_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_competencies', 'Types::read_competencies', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_goal_type', 'Types::read_goal_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_training_type', 'Types::read_training_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_case_type', 'Types::read_case_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_tax_type', 'Types::read_tax_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_jobs_categories', 'Types::read_jobs_categories', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/read_customers_group', 'Types::read_customers_group', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_category', 'Types::add_category', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_product_category', 'Types::add_product_category', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_award_type', 'Types::add_award_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_brand', 'Types::add_brand', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_leave_type', 'Types::add_leave_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_arrangement_type', 'Types::add_arrangement_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_income_type', 'Types::add_income_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_expense_type', 'Types::add_expense_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_competencies', 'Types::add_competencies', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_competencies2', 'Types::add_competencies2', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_goal_type', 'Types::add_goal_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_training_type', 'Types::add_training_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_case_type', 'Types::add_case_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_jobs_categories', 'Types::add_jobs_categories', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_tax_type', 'Types::add_tax_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_customers_group', 'Types::add_customers_group', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/add_exit_type', 'Types::add_exit_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/update_constants_type', 'Types::update_constants_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/update_tax_type', 'Types::update_tax_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/update_leave_type', 'Types::update_leave_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/types/delete_type', 'Types::delete_type', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Profile (self-service)
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/profile/update_profile', 'Profile::update_profile', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_profile_photo', 'Profile::update_profile_photo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_password', 'Profile::update_password', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_company_info', 'Profile::update_company_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_basic_info', 'Profile::update_basic_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_bio', 'Profile::update_bio', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_social', 'Profile::update_social', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_bankinfo', 'Profile::update_bankinfo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_contact_info', 'Profile::update_contact_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_account_info', 'Profile::update_account_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/user_documents_list', 'Profile::user_documents_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/allowances_list', 'Profile::allowances_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/commissions_list', 'Profile::commissions_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/statutory_list', 'Profile::statutory_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/other_payments_list', 'Profile::other_payments_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/system_info', 'Profile::system_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/update_payment_gateway', 'Profile::update_payment_gateway', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/notification_position_info', 'Profile::notification_position_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/setup_modules_info', 'Profile::setup_modules_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/profile/layout_info', 'Profile::layout_info', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Application (company settings, imports, etc.)
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/application/company_settings', 'Application::company_settings', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/application/company_constants', 'Application::company_constants', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Auth (forgot/reset password)
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/auth/forgot_password', 'Auth::forgot_password', ['namespace' => 'App\Controllers\Erp']);
$routes->match(['get','post'], 'erp/auth/verified_password', 'Auth::verified_password', ['namespace' => 'App\Controllers\Erp']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Stripe Payments
/***************************************************************************************************************/
$routes->match(['get','post'], 'erp/stripe/payment', 'Stripe::payment', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/stripe/create_subscription', 'Stripe::create_subscription', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/stripe/cancel_subscription', 'Stripe::cancel_subscription', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

/***************************************************************************************************************/
// AJAX/POST ENDPOINTS — Remaining modules (Awards, Travel, Complaints, etc.)
/***************************************************************************************************************/
// Awards
$routes->match(['get','post'], 'erp/awards/add_award', 'Awards::add_award', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/awards/update_award', 'Awards::update_award', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/awards/awards_list', 'Awards::awards_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/awards/read_award', 'Awards::read_award', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/awards/delete_award', 'Awards::delete_award', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Travel
$routes->match(['get','post'], 'erp/travel/add_travel', 'Travel::add_travel', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/travel/update_travel', 'Travel::update_travel', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/travel/travel_list', 'Travel::travel_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/travel/read_travel', 'Travel::read_travel', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/travel/delete_travel', 'Travel::delete_travel', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Complaints
$routes->match(['get','post'], 'erp/complaints/add_complaint', 'Complaints::add_complaint', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/complaints/update_complaint', 'Complaints::update_complaint', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/complaints/complaints_list', 'Complaints::complaints_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/complaints/read_complaint', 'Complaints::read_complaint', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/complaints/delete_complaint', 'Complaints::delete_complaint', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Resignation
$routes->match(['get','post'], 'erp/resignation/add_resignation', 'Resignation::add_resignation', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/resignation/update_resignation', 'Resignation::update_resignation', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/resignation/resignation_list', 'Resignation::resignation_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/resignation/read_resignation', 'Resignation::read_resignation', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/resignation/delete_resignation', 'Resignation::delete_resignation', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Transfers
$routes->match(['get','post'], 'erp/transfers/add_transfer', 'Transfers::add_transfer', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/transfers/update_transfer', 'Transfers::update_transfer', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/transfers/transfers_list', 'Transfers::transfers_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/transfers/read_transfer', 'Transfers::read_transfer', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/transfers/delete_transfer', 'Transfers::delete_transfer', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Employee Exit (Leaving)
$routes->match(['get','post'], 'erp/leaving/add_leaving', 'Leaving::add_leaving', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leaving/update_leaving', 'Leaving::update_leaving', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leaving/leaving_list', 'Leaving::leaving_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leaving/read_leaving', 'Leaving::read_leaving', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/leaving/delete_leaving', 'Leaving::delete_leaving', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Documents
$routes->match(['get','post'], 'erp/documents/add_document', 'Documents::add_document', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/documents/update_document', 'Documents::update_document', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/documents/upload_files_list', 'Documents::upload_files_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/documents/official_documents_list', 'Documents::official_documents_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/documents/expired_documents_list', 'Documents::expired_documents_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/documents/read_document', 'Documents::read_document', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/documents/delete_document', 'Documents::delete_document', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Trainers
$routes->match(['get','post'], 'erp/trainers/add_trainer', 'Trainers::add_trainer', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/trainers/update_trainer', 'Trainers::update_trainer', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/trainers/trainers_list', 'Trainers::trainers_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/trainers/read_trainer', 'Trainers::read_trainer', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/trainers/delete_trainer', 'Trainers::delete_trainer', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Events
$routes->match(['get','post'], 'erp/events/add_event', 'Events::add_event', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/events/update_event', 'Events::update_event', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/events/events_list', 'Events::events_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/events/read_event', 'Events::read_event', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/events/delete_event', 'Events::delete_event', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Meetings (Conference)
$routes->match(['get','post'], 'erp/conference/add_meeting', 'Conference::add_meeting', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/conference/update_meeting', 'Conference::update_meeting', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/conference/meetings_list', 'Conference::meetings_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/conference/read_meeting', 'Conference::read_meeting', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/conference/delete_meeting', 'Conference::delete_meeting', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Holidays
$routes->match(['get','post'], 'erp/holidays/add_holiday', 'Holidays::add_holiday', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/holidays/update_holiday', 'Holidays::update_holiday', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/holidays/holidays_list', 'Holidays::holidays_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/holidays/read_holiday', 'Holidays::read_holiday', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/holidays/delete_holiday', 'Holidays::delete_holiday', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Office Shifts
$routes->match(['get','post'], 'erp/officeshifts/add_shift', 'Officeshifts::add_shift', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/officeshifts/update_shift', 'Officeshifts::update_shift', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/officeshifts/shifts_list', 'Officeshifts::shifts_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/officeshifts/read_shift', 'Officeshifts::read_shift', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/officeshifts/delete_shift', 'Officeshifts::delete_shift', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Visitors
$routes->match(['get','post'], 'erp/visitors/add_visitor', 'Visitors::add_visitor', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/visitors/update_visitor', 'Visitors::update_visitor', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/visitors/visitors_list', 'Visitors::visitors_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/visitors/read_visitor', 'Visitors::read_visitor', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/visitors/delete_visitor', 'Visitors::delete_visitor', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Todo
$routes->match(['get','post'], 'erp/todo/add_todo', 'Todo::add_todo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/todo/update_todo', 'Todo::update_todo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/todo/todo_list', 'Todo::todo_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/todo/delete_todo', 'Todo::delete_todo', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Custom Fields
$routes->match(['get','post'], 'erp/customfields/add_field', 'Customfields::add_field', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/customfields/update_field', 'Customfields::update_field', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/customfields/fields_list', 'Customfields::fields_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/customfields/read_field', 'Customfields::read_field', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/customfields/delete_field', 'Customfields::delete_field', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Suppliers
$routes->match(['get','post'], 'erp/suppliers/add_supplier', 'Suppliers::add_supplier', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/suppliers/update_supplier', 'Suppliers::update_supplier', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/suppliers/suppliers_list', 'Suppliers::suppliers_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/suppliers/read_supplier', 'Suppliers::read_supplier', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/suppliers/delete_supplier', 'Suppliers::delete_supplier', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Warehouse
$routes->match(['get','post'], 'erp/warehouse/add_warehouse', 'Warehouse::add_warehouse', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/warehouse/update_warehouse', 'Warehouse::update_warehouse', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/warehouse/warehouse_list', 'Warehouse::warehouse_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/warehouse/read_warehouse', 'Warehouse::read_warehouse', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/warehouse/delete_warehouse', 'Warehouse::delete_warehouse', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Products
$routes->match(['get','post'], 'erp/products/add_product', 'Products::add_product', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/products/update_product', 'Products::update_product', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/products/products_list', 'Products::products_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/products/read_product', 'Products::read_product', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/products/delete_product', 'Products::delete_product', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Purchases
$routes->match(['get','post'], 'erp/purchases/create_new_purchase', 'Purchases::create_new_purchase', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/purchases/update_purchase', 'Purchases::update_purchase', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/purchases/purchases_list', 'Purchases::purchases_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/purchases/delete_purchase', 'Purchases::delete_purchase', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/purchases/delete_purchase_items', 'Purchases::delete_purchase_items', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Orders
$routes->match(['get','post'], 'erp/orders/create_new_order', 'Orders::create_new_order', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/orders/update_order', 'Orders::update_order', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/orders/orders_list', 'Orders::orders_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/orders/delete_order', 'Orders::delete_order', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/orders/delete_order_items', 'Orders::delete_order_items', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Order Quotes
$routes->match(['get','post'], 'erp/orderquotes/create_new_quote', 'Orderquotes::create_new_quote', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/orderquotes/update_quote', 'Orderquotes::update_quote', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/orderquotes/quotes_list', 'Orderquotes::quotes_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/orderquotes/delete_quote', 'Orderquotes::delete_quote', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/orderquotes/delete_quote_items', 'Orderquotes::delete_quote_items', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Subscription
$routes->match(['get','post'], 'erp/subscription/subscription_list', 'Subscription::subscription_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Payment History
$routes->match(['get','post'], 'erp/paymenthistory/payment_list', 'Paymenthistory::payment_list', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Contact Support
$routes->match(['get','post'], 'erp/contact/send_message', 'Contact::send_message', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
// Org Chart (data endpoint)
$routes->match(['get','post'], 'erp/orgchart/save_node', 'Orgchart::save_node', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);
$routes->match(['get','post'], 'erp/orgchart/delete_node', 'Orgchart::delete_node', ['namespace' => 'App\Controllers\Erp', 'filter' => 'checklogin']);

// Unsubscribe (Phase 10.11)
$routes->get('unsubscribe', 'Unsubscribe::index');

// API Documentation
$routes->get('api/docs', 'Home::api_docs', ['namespace' => 'App\Controllers']);

// REST API v1
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => 'throttle:60,1'], function ($routes) {
	$routes->post('auth/token', 'Auth::token');
	$routes->get('health', 'Health::index');

	// Webhook endpoints — no JWT auth, verified by provider-specific signatures
	$routes->post('webhooks/stripe', 'Webhooks::stripe');
	$routes->post('webhooks/mtn', 'Webhooks::mtn');
	$routes->post('webhooks/airtel', 'Webhooks::airtel');
	$routes->post('webhooks/zkteco', 'Webhooks::zkteco');

	$routes->group('', ['filter' => 'jwt'], function ($routes) {
		$routes->post('attendance/clock-in', 'Attendance::clockIn');
		$routes->post('attendance/clock-out', 'Attendance::clockOut');
		$routes->get('attendance/status', 'Attendance::status');
		$routes->get('employee/(:num)', 'Employees::show/$1');
		$routes->post('visitors/check-in', 'Visitors::checkIn');
		$routes->get('subscription/status', 'Subscription::status');
	});
});

/**
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php'))
{
	require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
