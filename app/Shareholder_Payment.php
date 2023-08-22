<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shareholder_Payment extends Model
{
    use HasFactory;
    protected $table = 'shareholder_payment';
    public function shareholder()
{
    return $this->belongsTo(Shareholder::class, 'share_holder_id');
}

 


    
}
