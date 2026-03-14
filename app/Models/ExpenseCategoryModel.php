<?php
namespace App\Models;

use CodeIgniter\Model;

class ExpenseCategoryModel extends Model {

    protected $table = 'ci_expense_categories';

    protected $primaryKey = 'category_id';

    // get all fields of table
    protected $allowedFields = ['category_id','company_id','category_name','is_active'];

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

}
?>