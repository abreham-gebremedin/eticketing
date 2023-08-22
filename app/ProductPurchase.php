<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    protected $table = 'product_purchases';
    protected $fillable =[

        "is_transfered","purchase_id", "is_new",  "product_id", "product_batch_id", "variant_id", "imei_number", "qty", "recieved", "purchase_unit_id", "net_unit_cost", "discount", "tax_rate", "tax", "total", "warehouse_id"
    ];
}
