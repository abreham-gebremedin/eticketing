<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWithPOSATM extends Model
{
    protected $table = 'payment_with_pos';

    protected $fillable =[

        "payment_id","pos_bank",
    ];}
