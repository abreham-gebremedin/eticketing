<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    protected $table = 'fixed_asset';


    protected $fillable =[
        "payment_status","paid_amount","name","reference_no", "fixed_asset_category_id", "warehouse_id", "account_id", "user_id","qty", "unit_cost","total_cost", "note", "created_at"  
    ];

    
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
    public function warehouse()
    {
    	return $this->belongsTo('App\Warehouse');
    }

    public function fixedAssetCategory() {
    	return $this->belongsTo('App\FixedAssetCategory');
    }
}
