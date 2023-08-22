<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseProductTransfer extends Model
{
    protected $table = 'purchase_product_transfer';
    protected $fillable =[
        "transfer_id", "product_id", "product_batch_id", "variant_id", "imei_number", "qty", "purchase_unit_id", "net_unit_cost", "discount", "tax_rate", "tax", "total"
    ];
}
