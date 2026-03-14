<?php
namespace App\Models;

use CodeIgniter\Model;

class ExpenseModel extends Model {

    protected $table = 'ci_expenses';

    protected $primaryKey = 'expense_id';

    // get all fields of table
    protected $allowedFields = ['expense_id','company_id','employee_id','category_id','amount','currency','description','expense_date','receipt_path','status','approved_by','approved_at','payroll_month','created_at'];

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

}
?>