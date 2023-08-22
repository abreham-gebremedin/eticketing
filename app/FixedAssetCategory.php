<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssetCategory extends Model
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
    protected $table = 'fixed_asset_categories';

    protected $fillable =[
         "name","life_time", "is_active","updated_by",
    ];

    public function fixed_asset() {
    	return $this->hasMany('App\FixedAsset');
    }

}
