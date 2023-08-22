<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartofAccount extends Model
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
     protected $table = 'chartof_accounts';
    protected $fillable = ['name', 'chartof_accounts_id','chartof_account_categories_id','code','fixed_asset_category_id'];

    public function accountType()
    {
        return $this->belongsTo('App\ChartofAccountCategory', 'chartof_account_categories_id');
    }

    public function accountTransaction()
    {
        return $this->hasMany('App\AccountTransaction', 'chartof_accounts_id');
    }
}
