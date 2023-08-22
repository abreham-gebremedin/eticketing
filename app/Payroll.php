<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Payroll extends Model
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
       "daily_amount","day","starting_date","ending_date","place", "reference_no", "employee_id", "account_id", "user_id",
        "amount", "paying_method", "note"
    ];

    public function employee()
    {
    	return $this->belongsTo('App\Employee');
    }
}
