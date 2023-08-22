<?php
foreach ($currentSale as $product_sale) {
    $product_id = $product_sale['product_id'];
    $product_batch_id = $product_sale['product_batch_id'] ?? null;
    $variant_id = $product_sale['variant_id'] ?? null;
    $sold_qty = $product_sale['qty'];

    
$product_purchase_data = ProductPurchase::where('product_id', $product_id)
->where('sale_status', '<>', 3)
->where('product_sales.warehouse_id', $product_sale['warehouse_id'])
    ->when($product_batch_id, function ($query) use ($product_batch_id) {
        return $query->where('product_batch_id', $product_batch_id);
    })
    ->when($variant_id, function ($query) use ($variant_id) {
        return $query->where('variant_id', $variant_id);
    })
    ->orderBy('created_at', 'asc')
    ->get();

$purchased_amount = 0;
$flagcheck = 0;

foreach ($product_purchase_data as $product_purchase) {
    $availabele_purchased_qty = $product_purchase->qty - $product_purchase->sold_qty;
    $unit_cost = $product_purchase->total / $product_purchase->qty;

    if ($sold_qty>0) {
        # code...
        if ($availabele_purchased_qty == $sold_qty) {
            $purchased_amount += $unit_cost * $sold_qty;
            $product_purchase->sale_status=1;
            break;
        }elseif ($availabele_purchased_qty > $sold_qty) {
            $purchased_amount += $unit_cost * $sold_qty;
            $product_purchase->sale_status=2;
            break;
        } else {
            $purchased_amount += $unit_cost * $product_purchase->qty;
            $sold_qty -= $product_purchase->qty;
            $product_purchase->sale_status=1;
        }
    }
    $product_purchase->save();

  
}

$product_cost += $purchased_amount;

$current_product_sale = Product_Sale::find($product_sale['id']);
$current_product_sale->net_unit_cost = $purchased_amount/$sold_qty;
$current_product_sale->save();

}

?>