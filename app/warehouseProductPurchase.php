<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class warehouseProductPurchase extends Model
{
    protected $table = 'warehouse_product_purchases';
    protected $fillable =[

        "warehouse_purchase_id", "is_new","product_id","returned_qty", "product_batch_id", "variant_id", "imei_number", "qty", "recieved", "purchase_unit_id", "net_unit_cost", "discount", "tax_rate", "tax", "total", "warehouse_id"
    ];
}
