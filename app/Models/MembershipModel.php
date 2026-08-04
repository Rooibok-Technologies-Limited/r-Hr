<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace App\Models;

use CodeIgniter\Model;
	
class MembershipModel extends Model {
 
    protected $table = 'ci_membership';

    protected $primaryKey = 'membership_id';
    
	// get all fields of user membership table
    protected $allowedFields = ['membership_id','subscription_id','membership_type','price','plan_duration','total_employees','description','features','created_at'];
	
	protected $validationRules = [];
	protected $validationMessages = [];
	protected $skipValidation = false;
	
}
?>