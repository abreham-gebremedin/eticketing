<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product_Warehouse extends Model
{
	protected $table = 'product_warehouse';
    protected $fillable =[

        "product_id", "product_batch_id", "varinat_id", "imei_number", "warehouse_id", "qty", "price"
    ];

    public function scopeFindProductWithVariant($query, $product_id, $variant_id, $warehouse_id,$is_new)
    {
    	return $query->where([
            ['product_id', $product_id],
            ['variant_id', $variant_id],
            ['warehouse_id', $warehouse_id],
            ['is_new', $is_new],

        ]);
    }

    public function scopeFindProductWithoutVariant($query, $product_id, $warehouse_id,$is_new)
    {
    	return $query->where([
            ['product_id', $product_id],
            ['warehouse_id', $warehouse_id],
            ['is_new', $is_new],

        ]);
    }
}
