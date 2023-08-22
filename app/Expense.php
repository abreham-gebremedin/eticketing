<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;


    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
   // ...
 
  public function isDraft()
  {
      return $this->status === self::STATUS_DRAFT;
  }
  
 
  public function isApproved()
  {
      return $this->status === self::STATUS_DRAFT;
  }
    protected $fillable =[
        "reference_no", "expense_category_id", "warehouse_id", "account_id", "user_id", "cash_register_id", "amount", "note", "created_at"  
    ];

    public function warehouse()
    {
    	return $this->belongsTo('App\Warehouse');
    }

    public function expenseCategory() {
    	return $this->belongsTo('App\ChartofAccount');
    }
}
