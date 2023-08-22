<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWithMobile extends Model
{
    protected $table = 'payment_with_mobile';

    protected $fillable =[

        "payment_id","mobile_bank","mbtn_no",
    ];
}
