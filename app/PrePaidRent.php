<?php

namespace App;
use \Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrePaidRent extends Model
{
    protected $table = 'prepaid_rent';


    protected $fillable =[
        "name","reference_no", "warehouse_id", "account_id", "user_id","total_cost", "note", "created_at","life_time"  
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
