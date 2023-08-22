<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Payment extends Model
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
        "purchase_id", "user_id", "sale_id", "cash_register_id", "account_id", "payment_reference", "amount", "used_points", "change", "paying_method", "payment_note",
    ];
}
