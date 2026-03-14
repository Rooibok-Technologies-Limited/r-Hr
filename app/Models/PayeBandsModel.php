<?php
namespace App\Models;

use CodeIgniter\Model;

class PayeBandsModel extends Model {

    protected $table = 'ci_paye_bands';

    protected $primaryKey = 'band_id';

	// get all fields of table
    protected $allowedFields = ['company_id','min_income','max_income','rate_percent','effective_from','is_active'];

	protected $validationRules = [];
	protected $validationMessages = [];
	protected $skipValidation = false;

}
?>
