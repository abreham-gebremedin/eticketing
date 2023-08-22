<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Purchase extends Model
{

    use SoftDeletes;


    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
   // ...
 
  public function isDraft()
  {
      return $this->pstatus === self::STATUS_DRAFT;
  }
  
 
  public function isApproved()
  {
      return $this->pstatus === self::STATUS_APPROVED;
  }

  public function isRejected()
  {
      return $this->pstatus === self::STATUS_REJECTED;
  }
  
 
  public function isPending()
  {
      return $this->pstatus === self::STATUS_PENDING;
  }

  
    protected $fillable =[

        "reference_no", "user_id","returned_qty", "warehouse_id", "supplier_id", "item", "total_qty", "total_discount", "total_tax", "total_cost", "order_tax_rate", "order_tax", "order_discount", "shipping_cost", "grand_total","paid_amount", "status", "payment_status", "document", "note", "created_at"
    ];

    public function supplier()
    {
    	return $this->belongsTo('App\Supplier');
    }

    public function warehouse()
    {
    	return $this->belongsTo('App\Warehouse');
    }
}
