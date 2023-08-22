<?php

namespace App;

use App\Shareholder_Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shareholder extends Model
{
  
  protected $table = 'shareholders';

    protected $fillable =[
        "name", "image", "email", "phone_number",
      "address", "city", "country", "is_active" ,"share", "share_value",
    ];

    // public function payments()
    // {
    //     return $this->hasMany(Shareholder_Payment::class, 'share_holder_id');
    // }
    

}
