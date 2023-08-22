<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
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
        "DepartureCity",	"ArrivalCity",	"DistanceKM",	"TicketPrice",	"TicketOfficerID"
    ];

    public function departureCity()
    {
        return $this->belongsTo(City::class, 'DepartureCity');
    }

    public function arrivalCity()
    {
        return $this->belongsTo(City::class, 'ArrivalCity');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'TicketOfficerID');
    }
}
