<?php
namespace App\Models;

use CodeIgniter\Model;
	
class CompanysettingsModel extends Model {
 
    protected $table = 'ci_erp_company_settings';

    protected $primaryKey = 'setting_id';
    
	// get all fields of company settings table
    protected $allowedFields = [
		'setting_id','company_id','default_currency','default_currency_symbol',
		'notification_position','notification_close_btn','notification_bar','date_format_xi',
		'default_language','system_timezone',
		'stripe_secret_key','stripe_publishable_key','stripe_active',
		'invoice_terms_condition','setup_modules','header_background',
		'calendar_locale','datepicker_locale','login_page','login_page_text','updated_at',
		// Phase 2.4: Geofencing
		'office_latitude','office_longitude','geofence_radius_m',
	];
	
	protected $validationRules = [];
	protected $validationMessages = [];
	protected $skipValidation = false;
	
}
?>