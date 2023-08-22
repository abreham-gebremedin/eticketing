<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class product_sale_purchase_cost extends Model
{
    use HasFactory;
    
    protected $table = 'product_sale_purchase_cost';
    protected $fillable =[
        "product_purchase_id", "product_sale_id", "qty",
    ];
}
