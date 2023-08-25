<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
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
        "BusNumber", "Capacity","RouteID","warehouse_id"
    ];


    public function route()
    {
        return $this->belongsTo(Route::class, 'RouteID');
    }
}


