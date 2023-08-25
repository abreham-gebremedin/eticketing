<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Queue extends Model
{
    use HasFactory;
    protected $table = 'queue';

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

  // Define the relationship with Bus
  public function bus()
  {
      return $this->belongsTo(Bus::class, 'BusID');
  }

  // Define the relationship with Route
  public function route()
  {
      return $this->belongsTo(Route::class, 'RouteID');
  }
  
    protected $fillable =[
        "BusID","RouteID", "Position","warehouse_id"
    ];

}
