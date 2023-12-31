<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable =[
        "BusID","RouteID","TicketOfficerID","SeatNumber","TicketPrice","CommissionFee","Total","warehouse_id","QueueID"
    ];
    
}
