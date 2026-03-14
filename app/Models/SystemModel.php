<?php
namespace App\Models;

use CodeIgniter\Model;
	
class SystemModel extends Model {
 
    protected $table = 'ci_erp_settings';

    protected $primaryKey = 'setting_id';
    
	// get all fields of system table
    protected $allowedFields = [
		'setting_id','application_name','company_name','trading_name','registration_no',
		'government_tax','company_type_id','default_currency','currency_converter',
		'notification_position','notification_close_btn','notification_bar','date_format_xi',
		'enable_email_notification','email_type','logo','favicon','frontend_logo','other_logo',
		'animation_effect','footer_text','default_language','system_timezone','is_ssl_available',
		'contact_number','country','online_payment_account','invoice_terms_condition',
		'auth_background','address_1','address_2','city','zipcode','state','email',
		'hr_version','hr_release_date','updated_at',
		// Stripe
		'stripe_secret_key','stripe_publishable_key','stripe_webhook_secret','stripe_mode','stripe_active',
		// MTN Mobile Money
		'mtn_subscription_key','mtn_api_user','mtn_api_key','mtn_environment','mtn_active',
		// Airtel Money
		'airtel_client_id','airtel_client_secret','airtel_environment','airtel_active',
		// SMS
		'sms_provider','sms_username','sms_api_key','sms_sender_id','sms_active',
		// JWT / API
		'jwt_secret','jwt_ttl_hours','api_active','api_rate_limit',
		// Geofencing & Billing
		'default_geofence_radius','billing_reminder_active','billing_reminder_days',
		// NSSF
		'nssf_employee_rate','nssf_employer_rate','nssf_enabled',
	];
	
	protected $validationRules = [];
	protected $validationMessages = [];
	protected $skipValidation = false;
	
}
?>