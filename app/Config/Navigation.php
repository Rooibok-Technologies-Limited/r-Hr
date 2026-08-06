<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace Config;

/**
 * SINGLE SOURCE OF TRUTH for every sidebar, breadcrumb and command-palette entry
 * across the admin (company), team-member (staff) and super-admin (super_user)
 * portals. Nothing else may hardcode navigation — the render partial
 * (app/Views/default/navigation.php) and NavBuilder derive everything from here.
 *
 * Structure is exactly three tiers: GROUP -> ITEM -> child (leaf). Never a fourth.
 *
 * Visibility keys (all optional; absent = always visible for the portal):
 *   'roles'     => which user_types this belongs to: 'company','staff','super_user'.
 *                  A group/item is filtered out for any role not listed.
 *   'plan'      => plan feature slug; hidden unless plan_allows($slug).
 *   'module'    => setup_modules key; hidden unless the tenant enabled it.
 *   'resources' => staff role-resource keys; for user_type 'staff' at least one must
 *                  be present in staff_role_resource(). company/super_user bypass this.
 *
 * Group ordering follows workflow sequence (Overview first, Configuration/System last);
 * item ordering within a group follows frequency of use. Both encoded by array order.
 */
class Navigation
{
    /**
     * The declarative tree. Icons are Feather names (the theme's icon family),
     * one per item, never repeated within a portal. Parent items are navigable
     * (their 'href' is a real page). Labels use lang() keys resolved at render.
     */
    public static function tree(): array
    {
        return [
            // ============================= TENANT PORTAL (company + staff) =====
            [
                'id' => 'overview', 'label' => 'nav.group_overview', 'order' => 10,
                'roles' => ['company', 'staff'],
                'items' => [
                    ['id' => 'dashboard', 'label' => 'nav.dashboard', 'icon' => 'home', 'href' => 'erp/desk'],
                    ['id' => 'live_attendance', 'label' => 'nav.live_attendance', 'icon' => 'activity',
                     'href' => 'erp/attendance-live', 'roles' => ['company']],
                    ['id' => 'id_cards', 'label' => 'nav.id_cards', 'icon' => 'credit-card', 'href' => 'erp/id-cards',
                     'children' => [
                        ['id' => 'id_cards_all', 'label' => 'nav.id_cards', 'href' => 'erp/id-cards'],
                        ['id' => 'id_card_settings', 'label' => 'nav.id_card_settings', 'href' => 'erp/id-card-settings',
                         'roles' => ['company']],
                     ]],
                ],
            ],
            [
                'id' => 'people', 'label' => 'nav.group_people', 'order' => 20,
                'roles' => ['company', 'staff'],
                'items' => [
                    ['id' => 'employees', 'label' => 'nav.employees', 'icon' => 'users', 'href' => 'erp/staff-list',
                     'resources' => ['staff2', 'shift1', 'staffexit1']],
                    ['id' => 'roles', 'label' => 'nav.roles', 'icon' => 'shield', 'href' => 'erp/set-roles',
                     'roles' => ['company']],
                    ['id' => 'recruitment', 'label' => 'nav.recruitment', 'icon' => 'gitlab', 'href' => 'erp/jobs-list',
                     'plan' => 'recruitment', 'module' => 'recruitment',
                     'resources' => ['ats2', 'candidate', 'interview', 'promotion']],
                    ['id' => 'clients', 'label' => 'nav.clients', 'icon' => 'user-check', 'href' => 'erp/clients-list',
                     'resources' => ['client1']],
                    ['id' => 'leads', 'label' => 'nav.leads', 'icon' => 'user-plus', 'href' => 'erp/leads-list',
                     'resources' => ['leads1']],
                    ['id' => 'org_chart', 'label' => 'nav.org_chart', 'icon' => 'git-merge', 'href' => 'erp/org-chart',
                     'resources' => ['org_chart']],
                    ['id' => 'core_hr', 'label' => 'nav.core_hr', 'icon' => 'crosshair', 'href' => 'erp/departments-list',
                     'resources' => ['department1', 'designation1', 'policy1', 'news1'],
                     'children' => [
                        ['id' => 'departments', 'label' => 'nav.departments', 'href' => 'erp/departments-list', 'resources' => ['department1']],
                        ['id' => 'designations', 'label' => 'nav.designations', 'href' => 'erp/designation-list', 'resources' => ['designation1']],
                        ['id' => 'policies', 'label' => 'nav.policies', 'href' => 'erp/policies-list', 'resources' => ['policy1']],
                        ['id' => 'announcements', 'label' => 'nav.announcements', 'href' => 'erp/news-list', 'resources' => ['news1']],
                     ]],
                ],
            ],
            [
                'id' => 'time_leave', 'label' => 'nav.group_time_leave', 'order' => 30,
                'roles' => ['company', 'staff'],
                'items' => [
                    ['id' => 'attendance', 'label' => 'nav.attendance', 'icon' => 'clock', 'href' => 'erp/attendance-list',
                     'resources' => ['attendance'],
                     'children' => [
                        ['id' => 'attendance_log', 'label' => 'nav.attendance_log', 'href' => 'erp/attendance-list', 'resources' => ['attendance']],
                        ['id' => 'manual_attendance', 'label' => 'nav.manual_attendance', 'href' => 'erp/manual-attendance', 'roles' => ['company']],
                        ['id' => 'monthly_attendance', 'label' => 'nav.monthly_attendance', 'href' => 'erp/monthly-attendance', 'roles' => ['company']],
                        ['id' => 'overtime', 'label' => 'nav.overtime', 'href' => 'erp/overtime-request', 'resources' => ['overtime_req1']],
                     ]],
                    ['id' => 'leave', 'label' => 'nav.leave', 'icon' => 'plus-square', 'href' => 'erp/leave-list',
                     'resources' => ['leave1', 'leave2']],
                    ['id' => 'requests', 'label' => 'nav.requests', 'icon' => 'list', 'href' => 'erp/expense-list',
                     'roles' => ['staff'], 'resources' => ['expense1', 'loan1', 'travel1', 'advance_salary1'],
                     'children' => [
                        ['id' => 'expense_claim', 'label' => 'nav.expense_claim', 'href' => 'erp/expense-list', 'resources' => ['expense1']],
                        ['id' => 'loan_request', 'label' => 'nav.loan_request', 'href' => 'erp/loan-request', 'resources' => ['loan1']],
                        ['id' => 'travel_request', 'label' => 'nav.travel_request', 'href' => 'erp/business-travel', 'module' => 'travel', 'resources' => ['travel1']],
                        ['id' => 'advance_salary', 'label' => 'nav.advance_salary', 'href' => 'erp/advance-salary', 'resources' => ['advance_salary1']],
                     ]],
                ],
            ],
            [
                'id' => 'talent', 'label' => 'nav.group_talent', 'order' => 40,
                'roles' => ['company', 'staff'],
                'items' => [
                    ['id' => 'performance', 'label' => 'nav.performance', 'icon' => 'aperture', 'href' => 'erp/performance-indicator-list',
                     'plan' => 'performance', 'module' => 'performance',
                     'resources' => ['indicator1', 'appraisal1', 'competency1', 'tracking1', 'track_type1', 'track_calendar'],
                     'children' => [
                        ['id' => 'indicators', 'label' => 'nav.indicators', 'href' => 'erp/performance-indicator-list', 'resources' => ['indicator1']],
                        ['id' => 'appraisals', 'label' => 'nav.appraisals', 'href' => 'erp/performance-appraisal-list', 'resources' => ['appraisal1']],
                        ['id' => 'competencies', 'label' => 'nav.competencies', 'href' => 'erp/competencies', 'resources' => ['competency1']],
                        ['id' => 'goals', 'label' => 'nav.goals', 'href' => 'erp/track-goals', 'resources' => ['tracking1']],
                        ['id' => 'goal_types', 'label' => 'nav.goal_types', 'href' => 'erp/goal-type', 'resources' => ['track_type1']],
                        ['id' => 'goals_calendar', 'label' => 'nav.goals_calendar', 'href' => 'erp/goals-calendar', 'resources' => ['track_calendar']],
                     ]],
                    ['id' => 'training', 'label' => 'nav.training', 'icon' => 'target', 'href' => 'erp/training-sessions',
                     'plan' => 'training', 'module' => 'training',
                     'resources' => ['training1', 'trainer1', 'training_skill1', 'training_calendar']],
                    ['id' => 'disciplinary', 'label' => 'nav.disciplinary', 'icon' => 'alert-circle', 'href' => 'erp/disciplinary-cases',
                     'resources' => ['disciplinary1', 'case_type1']],
                ],
            ],
            [
                'id' => 'finance', 'label' => 'nav.group_finance', 'order' => 50,
                'roles' => ['company', 'staff'],
                'items' => [
                    ['id' => 'accounts', 'label' => 'nav.finance', 'icon' => 'book', 'href' => 'erp/accounts-list',
                     'resources' => ['accounts1', 'deposit1', 'expense1'],
                     'children' => [
                        ['id' => 'accounts_list', 'label' => 'nav.accounts', 'href' => 'erp/accounts-list', 'resources' => ['accounts1']],
                        ['id' => 'payees', 'label' => 'nav.payees', 'href' => 'erp/payees-list', 'roles' => ['company']],
                        ['id' => 'payers', 'label' => 'nav.payers', 'href' => 'erp/payers-list', 'roles' => ['company']],
                     ]],
                    ['id' => 'payroll', 'label' => 'nav.payroll', 'icon' => 'dollar-sign', 'href' => 'erp/payroll-list',
                     'plan' => 'payroll',
                     'children' => [
                        ['id' => 'payroll_list', 'label' => 'nav.payroll_runs', 'href' => 'erp/payroll-list', 'roles' => ['company']],
                        ['id' => 'payslips', 'label' => 'nav.payslips', 'href' => 'erp/payslip-history', 'resources' => ['pay_history']],
                        ['id' => 'payroll_run', 'label' => 'nav.payroll_run', 'href' => 'erp/payroll-run', 'resources' => ['pay1']],
                        ['id' => 'payout_methods', 'label' => 'nav.payout_methods', 'href' => 'erp/payout-methods'],
                     ]],
                    ['id' => 'disbursements', 'label' => 'nav.disbursements', 'icon' => 'send', 'href' => 'erp/disbursements',
                     'plan' => 'payroll', 'roles' => ['company'],
                     'children' => [
                        ['id' => 'wallet', 'label' => 'nav.wallet', 'href' => 'erp/wallet'],
                        ['id' => 'disbursements_list', 'label' => 'nav.disbursements', 'href' => 'erp/disbursements'],
                     ]],
                    ['id' => 'expenses', 'label' => 'nav.expenses', 'icon' => 'trending-down', 'href' => 'erp/expenses',
                     'resources' => ['expense1']],
                    ['id' => 'invoices', 'label' => 'nav.invoices', 'icon' => 'file-text', 'href' => 'erp/invoices-list',
                     'resources' => ['invoice2', 'invoice_payments', 'invoice_calendar']],
                    ['id' => 'estimates', 'label' => 'nav.estimates', 'icon' => 'file', 'href' => 'erp/estimates-list',
                     'resources' => ['estimate2']],
                    ['id' => 'subscription', 'label' => 'nav.subscription', 'icon' => 'refresh-cw', 'href' => 'erp/subscription-invoices',
                     'roles' => ['company']],
                ],
            ],
            [
                'id' => 'workspace', 'label' => 'nav.group_workspace', 'order' => 60,
                'roles' => ['company', 'staff'],
                'items' => [
                    ['id' => 'projects', 'label' => 'nav.projects', 'icon' => 'layers', 'href' => 'erp/projects-grid',
                     'plan' => 'projects', 'resources' => ['project1']],
                    ['id' => 'tasks', 'label' => 'nav.tasks', 'icon' => 'edit', 'href' => 'erp/tasks-grid',
                     'plan' => 'projects', 'resources' => ['task1']],
                    ['id' => 'inventory', 'label' => 'nav.inventory', 'icon' => 'package', 'href' => 'erp/product-list',
                     'plan' => 'inventory', 'module' => 'inventory',
                     'resources' => ['product1', 'warehouse1', 'supplier1', 'purchases1', 'purchases2', 'sales_order1', 'sales_order2'],
                     'children' => [
                        ['id' => 'products', 'label' => 'nav.products', 'href' => 'erp/product-list', 'resources' => ['product1']],
                        ['id' => 'out_of_stock', 'label' => 'nav.out_of_stock', 'href' => 'erp/out-of-stock-products', 'resources' => ['out_of_stock']],
                        ['id' => 'expired_products', 'label' => 'nav.expired_products', 'href' => 'erp/expired-products', 'resources' => ['expired_product']],
                        ['id' => 'product_categories', 'label' => 'nav.product_categories', 'href' => 'erp/products-category', 'resources' => ['product_category1']],
                        ['id' => 'suppliers', 'label' => 'nav.suppliers', 'href' => 'erp/suppliers-list', 'resources' => ['supplier1']],
                        ['id' => 'purchases', 'label' => 'nav.purchases', 'href' => 'erp/stock-purchases', 'resources' => ['purchases1', 'purchases2']],
                        ['id' => 'sales_orders', 'label' => 'nav.sales_orders', 'href' => 'erp/stock-orders', 'resources' => ['sales_order1', 'sales_order2']],
                        ['id' => 'warehouses', 'label' => 'nav.warehouses', 'href' => 'erp/warehouse-list', 'resources' => ['warehouse1']],
                     ]],
                    ['id' => 'tickets', 'label' => 'nav.tickets', 'icon' => 'help-circle', 'href' => 'erp/support-tickets',
                     'resources' => ['helpdesk1']],
                    ['id' => 'broadcasts', 'label' => 'nav.broadcasts', 'icon' => 'radio', 'href' => 'erp/broadcasts',
                     'roles' => ['company']],
                ],
            ],
            [
                'id' => 'configuration', 'label' => 'nav.group_configuration', 'order' => 90,
                'roles' => ['company'],
                'items' => [
                    ['id' => 'company_settings', 'label' => 'nav.company_settings', 'icon' => 'settings', 'href' => 'erp/company-settings'],
                    ['id' => 'my_profile', 'label' => 'nav.my_profile', 'icon' => 'user', 'href' => 'erp/my-profile'],
                ],
            ],

            // ============================= SUPER-ADMIN PORTAL ==================
            [
                'id' => 'sa_overview', 'label' => 'nav.group_overview', 'order' => 10,
                'roles' => ['super_user'],
                'items' => [
                    ['id' => 'sa_dashboard', 'label' => 'nav.dashboard', 'icon' => 'home', 'href' => 'erp/desk'],
                    ['id' => 'sa_audit', 'label' => 'nav.audit_log', 'icon' => 'activity', 'href' => 'erp/audit-log'],
                ],
            ],
            [
                'id' => 'sa_people', 'label' => 'nav.group_people', 'order' => 20,
                'roles' => ['super_user'],
                'items' => [
                    ['id' => 'sa_companies', 'label' => 'nav.companies', 'icon' => 'briefcase', 'href' => 'erp/companies-list'],
                    ['id' => 'sa_users', 'label' => 'nav.staff_users', 'icon' => 'user-plus', 'href' => 'erp/super-users',
                     'children' => [
                        ['id' => 'sa_users_all', 'label' => 'nav.staff_users', 'href' => 'erp/super-users'],
                        ['id' => 'sa_user_roles', 'label' => 'nav.user_roles', 'href' => 'erp/users-role'],
                     ]],
                ],
            ],
            [
                'id' => 'sa_operations', 'label' => 'nav.group_billing', 'order' => 30,
                'roles' => ['super_user'],
                'items' => [
                    ['id' => 'sa_plans', 'label' => 'nav.plans', 'icon' => 'layers', 'href' => 'erp/membership-list'],
                    ['id' => 'sa_invoices', 'label' => 'nav.all_invoices', 'icon' => 'file-text', 'href' => 'erp/all-subscription-invoices'],
                    ['id' => 'sa_payments', 'label' => 'nav.payment_history', 'icon' => 'credit-card', 'href' => 'erp/billing-invoices'],
                    ['id' => 'sa_wallets', 'label' => 'nav.company_wallets', 'icon' => 'dollar-sign', 'href' => 'erp/wallets'],
                    ['id' => 'sa_disbursements', 'label' => 'nav.disbursements', 'icon' => 'send', 'href' => 'erp/disbursements'],
                ],
            ],
            [
                'id' => 'sa_content', 'label' => 'nav.group_content', 'order' => 40,
                'roles' => ['super_user'],
                'items' => [
                    ['id' => 'sa_landing', 'label' => 'nav.landing_cms', 'icon' => 'layout', 'href' => 'erp/landing-page'],
                    ['id' => 'sa_broadcasts', 'label' => 'nav.broadcasts', 'icon' => 'radio', 'href' => 'erp/broadcasts',
                     'children' => [
                        ['id' => 'sa_broadcasts_all', 'label' => 'nav.broadcasts', 'href' => 'erp/broadcasts'],
                        ['id' => 'sa_broadcast_templates', 'label' => 'nav.broadcast_templates', 'href' => 'erp/broadcasts/templates'],
                     ]],
                ],
            ],
            [
                'id' => 'sa_configuration', 'label' => 'nav.group_configuration', 'order' => 90,
                'roles' => ['super_user'],
                'items' => [
                    ['id' => 'sa_settings', 'label' => 'nav.general_settings', 'icon' => 'settings', 'href' => 'erp/system-settings',
                     'children' => [
                        ['id' => 'sa_settings_general', 'label' => 'nav.general_settings', 'href' => 'erp/system-settings'],
                        ['id' => 'sa_constants', 'label' => 'nav.constants', 'href' => 'erp/system-constants'],
                        ['id' => 'sa_theme', 'label' => 'nav.theme_settings', 'href' => 'erp/theme-settings'],
                     ]],
                    ['id' => 'sa_templates', 'label' => 'nav.templates', 'icon' => 'mail', 'href' => 'erp/email-templates',
                     'children' => [
                        ['id' => 'sa_email_templates', 'label' => 'nav.email_templates', 'href' => 'erp/email-templates'],
                        ['id' => 'sa_sms_templates', 'label' => 'nav.sms_templates', 'href' => 'erp/sms-templates'],
                     ]],
                    ['id' => 'sa_gateways', 'label' => 'nav.payment_gateways', 'icon' => 'sliders', 'href' => 'erp/system-payment-settings'],
                    ['id' => 'sa_tax', 'label' => 'nav.tax_settings', 'icon' => 'percent', 'href' => 'erp/system-tax-settings'],
                ],
            ],
            [
                'id' => 'sa_system', 'label' => 'nav.group_system', 'order' => 100,
                'roles' => ['super_user'],
                'items' => [
                    ['id' => 'sa_backup', 'label' => 'nav.database_backup', 'icon' => 'database', 'href' => 'erp/system-backup'],
                    ['id' => 'sa_archive', 'label' => 'nav.archive', 'icon' => 'archive', 'href' => 'erp/archive'],
                    ['id' => 'sa_api_docs', 'label' => 'nav.api_docs', 'icon' => 'book-open', 'href' => 'api/docs', 'external' => true],
                ],
            ],
        ];
    }
}
