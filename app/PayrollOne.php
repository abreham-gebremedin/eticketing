<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PayrollOne extends Model
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

    protected $table = 'payrollone';

    protected $fillable =[
         "reference_no", "account_id", "user_id", "total_net", "add_total_pension", "total_income_tax", "total_deduction", "grand_total",
    ];

   


}
