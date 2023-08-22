<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\AccountTransactionAdjustment;
use App\ChartofAccount;
use App\Journal_Entry;
use App\ProductPurchase;
use App\ProductTransfer;
use App\warehouseProductPurchase;
use Illuminate\Http\Request;
use App\PurchaseTransfer;
use App\Warehouse;
use App\Supplier;
use App\Tax;
use App\Product;
use App\Product_Warehouse;
use App\Unit;
use App\Account;
use App\ProductVariant;
use App\ProductBatch;
use App\Variant;
use App\Purchase;
use App\warehousePurchase;
use Auth;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\UserNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\PurchaseProductTransfer;

class PurchaseTransferController extends Controller
{
    //
    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('returns-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';

            if ($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if ($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            } else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d'))))));
                $ending_date = date("Y-m-d");
            }

            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            return view('purchase_transfer.index', compact('starting_date', 'ending_date', 'warehouse_id', 'all_permission', 'lims_warehouse_list'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function transferData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $warehouse_id = $request->input('warehouse_id');

        if (Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $totalData = PurchaseTransfer::where('user_id', Auth::id())
                ->whereDate('created_at', '>=', $request->input('starting_date'))
                ->whereDate('created_at', '<=', $request->input('ending_date'))
                ->count();
        elseif ($warehouse_id != 0)
            $totalData = PurchaseTransfer::where('warehouse_id', $warehouse_id)
                ->whereDate('created_at', '>=', $request->input('starting_date'))
                ->whereDate('created_at', '<=', $request->input('ending_date'))
                ->count();
        else
            $totalData = PurchaseTransfer::whereDate('created_at', '>=', $request->input('starting_date'))
                ->whereDate('created_at', '<=', $request->input('ending_date'))
                ->count();

        $totalFiltered = $totalData;
        if ($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'transfer_purchases.' . $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if (empty($request->input('search.value'))) {
            $q = PurchaseTransfer::with('warehouse', 'user')
                ->whereDate('created_at', '>=', $request->input('starting_date'))
                ->whereDate('created_at', '<=', $request->input('ending_date'))
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own')
                $q = $q->where('user_id', Auth::id());
            elseif ($warehouse_id != 0)
                $q = $q->where('warehouse_id', $warehouse_id);
            $transfers = $q->get();
        } else {
            $search = $request->input('search.value');
            $q = PurchaseTransfer:: 
                whereDate('transfer_purchases.created_at', '=', date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $transfers = $q->select('transfer_purchases.*')
                    ->with( 'warehouse', 'user')
                    ->where('transfer_purchases.user_id', Auth::id())
                    ->orwhere([
                        ['transfer_purchases.reference_no', 'LIKE', "%{$search}%"],
                        ['transfer_purchases.user_id', Auth::id()]
                    ])
                    ->orwhere([
                        ['transfer_purchases.user_id', Auth::id()]
                    ])
                    ->get();

                $totalFiltered = $q->where('transfer_purchases.user_id', Auth::id())
                    ->orwhere([
                        ['transfer_purchases.reference_no', 'LIKE', "%{$search}%"],
                        ['transfer_purchases.user_id', Auth::id()]
                    ])
                    ->orwhere([
                         ['transfer_purchases.user_id', Auth::id()]
                    ])
                    ->count();
            } else {
                $transfers = $q->select('transfer_purchases.*')
                    ->with('warehouse', 'user')
                    ->orwhere('transfer_purchases.reference_no', 'LIKE', "%{$search}%")
                     ->get();

                $totalFiltered = $q->orwhere('transfer_purchases.reference_no', 'LIKE', "%{$search}%")
                     ->count();
            }
        }
        $data = array();
        if (!empty($transfers)) {
           
            foreach ($transfers as $key => $ptransfers) {
                $nestedData['id'] = $ptransfers->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($ptransfers->created_at->toDateString()));
                $nestedData['reference_no'] = $ptransfers->reference_no;
                $nestedData['warehouse'] = $ptransfers->warehouse->name;

                $to_warehouse = Warehouse::where('id', $ptransfers->to_warehouse_id)->first();
                $nestedData['to_warehouse'] = $to_warehouse->name;
                $nestedData['grand_total'] = number_format($ptransfers->grand_total, 2);
                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . trans("file.action") . '
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" class="btn btn-link view"><i class="fa fa-eye"></i> ' . trans('file.View') . '</button>
                                </li>';
                // if (in_array("transfers-edit", $request['all_permission'])) {
                //     $nestedData['options'] .= '<li>
                //         <a href="' . route('return-purchase.edit', $ptransfers->id) . '" class="btn btn-link"><i class="dripicons-document-edit"></i> ' . trans('file.edit') . '</a>
                //         </li>';
                // }
                // if (in_array("transfers-delete", $request['all_permission']))
                //     $nestedData['options'] .= \Form::open(["route" => ["return-purchase.destroy", $ptransfers->id], "method" => "DELETE"]) . '
                //             <li>
                //               <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> ' . trans("file.delete") . '</button> 
                //             </li>' . \Form::close() . 
                '
                        </ul>
                    </div>';
                // data for purchase details by one click
 
                $nestedData['return'] = array(
                    '[ "'.date(config('date_format'), strtotime($ptransfers->created_at->toDateString())).'"', ' "'.$ptransfers->reference_no.'"', ' "'.$ptransfers->warehouse->name.'"', ' "'.$ptransfers->warehouse->phone.'"', ' "'.$ptransfers->warehouse->address.'"', ' "'.$to_warehouse->name.'"', ' "'." ".'"', ' "'.$to_warehouse->email.'"', ' "'.$to_warehouse->phone.'"', ' "'.$to_warehouse->address.'"', ' "'." ".'"', ' "'.$ptransfers->id.'"', ' "'.$ptransfers->total_tax.'"', ' "'.$ptransfers->total_discount.'"', ' "'.$ptransfers->total_cost.'"', ' "'.$ptransfers->order_tax.'"', ' "'.$ptransfers->order_tax_rate.'"', ' "'.$ptransfers->grand_total.'"', ' "'.preg_replace('/[\n\r]/', "<br>", $ptransfers->return_note).'"', ' "'.preg_replace('/[\n\r]/', "<br>", $ptransfers->staff_note).'"', ' "'.$ptransfers->user->name.'"', ' "'.$ptransfers->user->email.'"]'
                );
                $data[] = $nestedData;
            }
        }

         $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        );

        echo json_encode($json_data);
    }

    public function create(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('purchasetransfer-index')) {
            $lims_purchase_data = warehousePurchase::select('id')->where('reference_no', $request->input('reference_no'))->first();
            if ($lims_purchase_data) {
                # code...
                $lims_product_purchase_data = warehouseProductPurchase::where('warehouse_purchase_id', $lims_purchase_data->id)->get();
            }else {
                # code...
                return redirect('transfer-purchase')->with('not_permitted', 'Sorry! we can not find your data');

            }
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            return view('purchase_transfer.create', compact('lims_warehouse_list', 'lims_tax_list', 'lims_account_list', 'lims_purchase_data', 'lims_product_purchase_data'));
        } else
        return redirect('transfer-purchase')->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function getProduct($id)
    {
        //retrieve data of product without variant
        $lims_product_warehouse_data = DB::table('products')
            ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->select('products.code', 'products.name', 'products.type', 'product_warehouse.qty')
            ->where([
                ['product_warehouse.warehouse_id', $id],
                ['products.is_active', true]
            ])
            ->whereNull('product_warehouse.variant_id')
            ->whereNull('product_warehouse.product_batch_id')
            ->get();

        config()->set('database.connections.mysql.strict', false);
        \DB::reconnect(); //important as the existing connection if any would be in strict mode

        //retrieve data of product with batch
        $lims_product_with_batch_warehouse_data = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
            ])
            ->whereNull('product_warehouse.variant_id')
            ->whereNotNull('product_warehouse.product_batch_id')
            ->select('product_warehouse.*')
            ->groupBy('product_warehouse.product_id')
            ->get();

        //now changing back the strict ON
        config()->set('database.connections.mysql.strict', true);
        \DB::reconnect();

        //retrieve data of product with variant
        $lims_product_with_variant_warehouse_data = DB::table('products')
            ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->select('products.id', 'products.code', 'products.name', 'products.type', 'product_warehouse.qty', 'product_warehouse.variant_id')
            ->where([
                ['product_warehouse.warehouse_id', $id],
                ['products.is_active', true]
            ])
            ->whereNotNull('product_warehouse.variant_id')
            ->get();

        $product_code = [];
        $product_name = [];
        $product_qty = [];
        $is_batch = [];
        $product_data = [];
        foreach ($lims_product_warehouse_data as $product_warehouse) {
            $product_qty[] = $product_warehouse->qty;
            $product_code[] = $product_warehouse->code;
            $product_name[] = $product_warehouse->name;
            $product_type[] = $product_warehouse->type;
            $is_batch[] = null;
        }
        //product with batches
        foreach ($lims_product_with_batch_warehouse_data as $product_warehouse) {
            $product_qty[] = $product_warehouse->qty;
            $lims_product_data = Product::select('code', 'name', 'type', 'is_batch')->find($product_warehouse->product_id);
            $product_code[] = $lims_product_data->code;
            $product_name[] = htmlspecialchars($lims_product_data->name);
            $product_type[] = $lims_product_data->type;
            $product_batch_data = ProductBatch::select('id', 'batch_no')->find($product_warehouse->product_batch_id);
            $is_batch[] = $lims_product_data->is_batch;
        }

        foreach ($lims_product_with_variant_warehouse_data as $product_warehouse) {
            $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_warehouse->id, $product_warehouse->variant_id)->first();
            $product_qty[] = $product_warehouse->qty;
            $product_code[] = $lims_product_variant_data->item_code;
            $product_name[] = $product_warehouse->name;
            $product_type[] = $product_warehouse->type;
            $is_batch[] = null;
        }

        $product_data = [$product_code, $product_name, $product_qty, $product_type, $is_batch];
        return $product_data;
    }

    public function limsProductSearch(Request $request)
    {
        $product_code = explode("(", $request['data']);
        $product_code[0] = rtrim($product_code[0], " ");
        $lims_product_data = Product::where('code', $product_code[0])->first();
        $product_variant_id = null;
        if (!$lims_product_data) {
            $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.*', 'product_variants.id as product_variant_id', 'product_variants.item_code', 'product_variants.additional_cost')
                ->where('product_variants.item_code', $product_code[0])
                ->first();
            $lims_product_data->code = $lims_product_data->item_code;
            $lims_product_data->cost += $lims_product_data->additional_cost;
            $product_variant_id = $lims_product_data->product_variant_id;
        }

        $product[] = $lims_product_data->name;
        $product[] = $lims_product_data->code;
        $product[] = $lims_product_data->cost;

        if ($lims_product_data->tax_id) {
            $lims_tax_data = Tax::find($lims_product_data->tax_id);
            $product[] = $lims_tax_data->rate;
            $product[] = $lims_tax_data->name;
        } else {
            $product[] = 0;
            $product[] = 'No Tax';
        }
        $product[] = $lims_product_data->tax_method;

        $units = Unit::where("base_unit", $lims_product_data->unit_id)
            ->orWhere('id', $lims_product_data->unit_id)
            ->get();

        $unit_name = array();
        $unit_operator = array();
        $unit_operation_value = array();
        foreach ($units as $unit) {
            if ($lims_product_data->purchase_unit_id == $unit->id) {
                array_unshift($unit_name, $unit->unit_name);
                array_unshift($unit_operator, $unit->operator);
                array_unshift($unit_operation_value, $unit->operation_value);
            } else {
                $unit_name[] = $unit->unit_name;
                $unit_operator[] = $unit->operator;
                $unit_operation_value[] = $unit->operation_value;
            }
        }

        $product[] = implode(",", $unit_name) . ',';
        $product[] = implode(",", $unit_operator) . ',';
        $product[] = implode(",", $unit_operation_value) . ',';
        $product[] = $lims_product_data->id;
        $product[] = $product_variant_id;
        $product[] = $lims_product_data->is_imei;
        return $product;
    }

    public function store(Request $request)
    {
        $data = $request->except('document');

        $data['user_id'] = Auth::id();
        $lims_purchase_data = warehousePurchase::where("id", $data['purchase_id'])->first();
        $data['reference_no'] = "transfered-" . $lims_purchase_data->reference_no.'-on-'.date("Y-m-d H:i:s");
        $lims_purchase_data->transferred_qty=$lims_purchase_data->transferred_qty + $data['total_qty'];
        $lims_purchase_data->save();
        $data['user_id'] = Auth::id();
        $data['to_warehouse_id'] = $data['warehouse_id'];
        Arr::forget($data, 'warehouse_id');
        $data['warehouse_id'] = $lims_purchase_data->warehouse_id;;
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
            return redirect('transfer-purchase')->withErrors($v->errors());

            $documentName = $document->getClientOriginalName();
            $document->move('public/return/documents', $documentName);
            $data['document'] = $documentName;
        }
         $data2['reference_no'] = "transfered-" . $lims_purchase_data->reference_no.'-on-'.date("Y-m-d H:i:s");
        $data2['supplier_id'] = $lims_purchase_data->supplier_id;
        $data2['warehouse_id'] = $data['to_warehouse_id'];
        $data2['item'] = $data['item'];

        $data2['total_qty'] = $data['total_qty'];
        $data2['total_discount'] = $data['total_discount'];
        $data2['total_tax'] = $data['total_tax'];
        $data2['total_cost'] = $data['total_cost'];
        $data2['order_tax'] = $data['order_tax'];
        $data2['grand_total'] = $data['grand_total'];
        $data2['order_tax_rate'] = $data['order_tax_rate'];
        $data2['note'] = $data['return_note'];
        $data2['user_id'] = $data['user_id'];
        $data2['paid_amount'] = $data2['grand_total'];
        $data2['status'] =1;
        $data2['payment_status'] = 2;
        $lims_transfer_data = PurchaseTransfer::create($data);
        $lims_transfer_data2 = Purchase::create($data2);
        
        $dataad['user_id'] = Auth::id();
        $dataad['created_at'] =$lims_transfer_data2->created_at;
        $dataad['warehouse_id'] = $data2['warehouse_id'] ;
        $dataad['reference_no'] = $data2['reference_no'] ;
        $dataad['reason'] = $data2['reference_no'] .$data2['note'] ;
        $dataad['is_adjustment'] = false ;
        $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);
        
        $transaction = new AccountTransaction;
        $transaction->reference_no = $lims_transfer_data->reference_no;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id	 = Auth::id();
        $transaction->warehouse_id =  $data['warehouse_id']; 
        $transaction->credit =$lims_transfer_data->grand_total ;
        $transaction->debit = 0;
        $accountType = ChartofAccount::where('name', 'Purchase')->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->save();
        
        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);

        $transaction = new AccountTransaction;
        $transaction->reference_no = $lims_transfer_data->reference_no;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id	 = Auth::id();
        $transaction->warehouse_id =  $data['to_warehouse_id']; 
        $transaction->credit =0 ;
        $transaction->debit = $lims_transfer_data->grand_total;
        $accountType = ChartofAccount::where('name', 'Purchase')->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->save();

        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);
        $product_code_selected = $data['is_transfer'];
        $imei_number = $data['imei_number'];
        $product_batch_id = $data['product_batch_id'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];

        foreach ($product_code_selected as $i => $pcs) {

             //return $key;
             if (Str::contains($pcs,'-')) {
                # code...
                $lims_product_selected_data = ProductVariant::where("item_code",$pcs)->first();
                $id=$lims_product_selected_data->product_id;

            } else {
                # code...
                $lims_product_selected_data = Product::where('code',$pcs)->first();
                $id=$lims_product_selected_data->id;
            }
            $lims_product_data = Product::find($id);
            $lims_purchase_unit_data = Unit::where('unit_name', $purchase_unit[$i])->first();

            if ($lims_purchase_unit_data->operator == '*') {
                $quantity = $qty[$i] * $lims_purchase_unit_data->operation_value;
            } else {
                $quantity = $qty[$i] / $lims_purchase_unit_data->operation_value;
            }
 
            //dealing with product barch
            if ($product_batch_id[$i]) {
                
                $product_batch_data = ProductBatch::where([
                    ['product_id', $lims_product_data->id],
                    ['id', $product_batch_id[$i]]
                ])->first();
                if ($product_batch_data) {
                    $product_batch_data->qty += $quantity;
                    $product_batch_data->save();
                }
                $product_purchase['product_batch_id'] = $product_batch_data->id;
            } else
                $product_purchase['product_batch_id'] = null;

            if ($lims_product_data->is_variant) {
                $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($lims_product_data->id, $product_code[$i])->first();
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $id],
                    ['variant_id', $lims_product_variant_data->variant_id],
                    ['warehouse_id', $data['to_warehouse_id']]
                ])->first();

                $lims_from_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $id],
                    ['variant_id', $lims_product_variant_data->variant_id],
                    ['warehouse_id', $data['warehouse_id']]
                ])->first();
                $product_purchase['variant_id'] = $lims_product_variant_data->variant_id;


                $update_warehousepurchase_data = warehouseProductPurchase::where([
                    ['product_id', $id],
                    ['variant_id', $lims_product_variant_data->variant_id],
                    ['warehouse_purchase_id', $data['purchase_id']]
                ])->first();
                $update_warehousepurchase_data->transferred_qty=$update_warehousepurchase_data->transferred_qty +  $quantity;
                $update_warehousepurchase_data->save();
                //add quantity to product variant table
                $lims_product_variant_data->qty += $quantity;
                $lims_product_variant_data->save();
            } else {

                $update_warehousepurchase_data = warehouseProductPurchase::where([
                    ['product_id', $id],
                    ['warehouse_purchase_id', $data['purchase_id']]
                ])->first();
                $update_warehousepurchase_data->transferred_qty=$update_warehousepurchase_data->transferred_qty + $quantity;
                $update_warehousepurchase_data->save();
                $product_purchase['variant_id'] = null;
                if ($product_purchase['product_batch_id']) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['product_batch_id', $product_purchase['product_batch_id']],
                        ['warehouse_id', $data['to_warehouse_id']],
                    ])->first();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['warehouse_id', $data['to_warehouse_id']],
                    ])->first();
                }


                if ($product_purchase['product_batch_id']) {
                    $lims_from_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['product_batch_id', $product_purchase['product_batch_id']],
                        ['warehouse_id', $data['warehouse_id']],
                    ])->first();
                } else {
                    $lims_from_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['warehouse_id', $data['warehouse_id']],
                    ])->first();
                }
            }

            //add quantity to warehouse
            if ($lims_product_warehouse_data) {
                $lims_product_warehouse_data->qty = $lims_product_warehouse_data->qty + $quantity;
                $lims_product_warehouse_data->product_batch_id = $product_purchase['product_batch_id'];
            } else {
                $lims_product_warehouse_data = new Product_Warehouse();
                $lims_product_warehouse_data->product_id = $id;
                $lims_product_warehouse_data->product_batch_id = $product_purchase['product_batch_id'];
                $lims_product_warehouse_data->warehouse_id = $data['to_warehouse_id'];
                $lims_product_warehouse_data->qty = $quantity;
                if ($lims_product_data->is_variant)
                    $lims_product_warehouse_data->variant_id = $lims_product_variant_data->variant_id;
            }

            //substract quantity from warehouse
            if ($lims_from_product_warehouse_data) {
                $lims_from_product_warehouse_data->qty = $lims_from_product_warehouse_data->qty - $quantity;
             } 
            //added imei numbers to product_warehouse table
            if ($imei_number[$i]) {
                if ($lims_product_warehouse_data->imei_number)
                    $lims_product_warehouse_data->imei_number .= ',' . $imei_number[$i];
                else
                    $lims_product_warehouse_data->imei_number = $imei_number[$i];
            }
            
            $lims_from_product_warehouse_data->save();
            $lims_product_warehouse_data->save();

            $product_purchase['product_id'] = $id;
            $product_purchase['imei_number'] = $imei_number[$i];
            $product_purchase['qty'] = $qty[$i];
            $product_purchase['purchase_unit_id'] = $lims_purchase_unit_data->id;
            $product_purchase['net_unit_cost'] = $net_unit_cost[$i];
            $product_purchase['discount'] = $discount[$i];
            $product_purchase['tax_rate'] = $tax_rate[$i];
            $product_purchase['tax'] = $tax[$i];
            $product_purchase['total'] = $total[$i];
            $product_purchase['warehouse_id'] = $data['to_warehouse_id'];
            $product_transfer=$product_purchase;
            $product_purchase['purchase_id'] = $lims_transfer_data2->id;
            $product_purchase['is_transfered'] = 1;
            $product_purchase['recieved'] = $qty[$i];
            $product_transfer['transfer_id'] = $lims_transfer_data->id;
            // dd($product_transfer);

             PurchaseProductTransfer::create($product_transfer);
             ProductPurchase::create($product_purchase);

 
        }
        $message = 'Transfer created successfully';
        
        return redirect('transfer-purchase')->with('message', $message);
    }
 






    public function storeTwo(Request $request)
    {
        $data = $request->except('document');

        $data['user_id'] = Auth::id();
        $lims_purchase_data = Purchase::where("id", $data['purchase_id'])->first();
        $data['reference_no'] = "transfered-" . $lims_purchase_data->reference_no.'-on-'.date("Y-m-d H:i:s");
        $lims_purchase_data->transferred_qty=$lims_purchase_data->transferred_qty + $data['total_qty'];
        $lims_purchase_data->save();
        $data['user_id'] = Auth::id();
        $data['to_warehouse_id'] = $data['warehouse_id'];
        Arr::forget($data, 'warehouse_id');
        $data['warehouse_id'] = $lims_purchase_data->warehouse_id;;
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
            return redirect('transfer-purchase')->withErrors($v->errors());

            $documentName = $document->getClientOriginalName();
            $document->move('public/return/documents', $documentName);
            $data['document'] = $documentName;
        }
        $data2['reference_no'] = "transfered-" . $lims_purchase_data->reference_no.'-on-'.date("Y-m-d H:i:s");
        $data2['supplier_id'] = $lims_purchase_data->supplier_id;
        $data2['warehouse_id'] = $data['to_warehouse_id'];
        $data2['item'] = $data['item'];
        $data2['total_qty'] = $data['total_qty'];
        $data2['total_discount'] = $data['total_discount'];
        $data2['total_tax'] = $data['total_tax'];
        $data2['total_cost'] = $data['total_cost'];
        $data2['order_tax'] = $data['order_tax'];
        $data2['grand_total'] = $data['grand_total'];
        $data2['order_tax_rate'] = $data['order_tax_rate'];
        $data2['note'] = $data['return_note'];
        $data2['user_id'] = $data['user_id'];
        $data2['paid_amount'] = $data2['grand_total'];
        $data2['status'] =1;
        $data2['payment_status'] = 2;
        $lims_transfer_data = PurchaseTransfer::create($data);
        $lims_transfer_data2 = warehousePurchase::create($data2);

        $dataad['user_id'] = Auth::id();
        $dataad['created_at'] = $lims_transfer_data2->created_at ;
        $dataad['warehouse_id'] = $data2['warehouse_id'] ;
        $dataad['reference_no'] = $data2['reference_no'] ;
        $dataad['reason'] = $data2['reference_no'] .$data2['note'] ;
        $dataad['is_adjustment'] = false ;
        $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);


        $transaction = new AccountTransaction;
        $transaction->reference_no = $lims_transfer_data->reference_no;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id= Auth::id();
        $transaction->warehouse_id =$data['warehouse_id']; 
        $transaction->credit =$lims_transfer_data->grand_total ;
        $transaction->debit =0;
        $accountType = ChartofAccount::where('name', 'Purchase')->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->save();

        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);

        $transaction = new AccountTransaction;
        $transaction->reference_no = $lims_transfer_data->reference_no;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id	 = Auth::id();
        $transaction->warehouse_id =  $data['to_warehouse_id']; 
        $transaction->credit =0 ;
        $transaction->debit = $lims_transfer_data->grand_total;
        $accountType = ChartofAccount::where('name', 'Purchase')->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->save();

        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);

        $product_code_selected = $data['is_transfer'];
        $imei_number = $data['imei_number'];
        $product_batch_id = $data['product_batch_id'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];

   

        foreach ($product_code_selected as $i => $pcs) {

             //return $key;
             if (Str::contains($pcs,'-')) {
                # code...
                $lims_product_selected_data = ProductVariant::where("item_code",$pcs)->first();
                $id=$lims_product_selected_data->product_id;

            } else {
                # code...
                $lims_product_selected_data = Product::where('code',$pcs)->first();
                $id=$lims_product_selected_data->id;
            }
            $lims_product_data = Product::find($id);
            $lims_purchase_unit_data = Unit::where('unit_name', $purchase_unit[$i])->first();

            if ($lims_purchase_unit_data->operator == '*') {
                $quantity = $qty[$i] * $lims_purchase_unit_data->operation_value;
            } else {
                $quantity = $qty[$i] / $lims_purchase_unit_data->operation_value;
            }
 
            //dealing with product barch
            if ($product_batch_id[$i]) {
                
                $product_batch_data = ProductBatch::where([
                    ['product_id', $lims_product_data->id],
                    ['id', $product_batch_id[$i]]
                ])->first();
                if ($product_batch_data) {
                    $product_batch_data->qty += $quantity;
                    $product_batch_data->save();
                }
                $product_purchase['product_batch_id'] = $product_batch_data->id;
            } else
                $product_purchase['product_batch_id'] = null;

            if ($lims_product_data->is_variant) {
                $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($lims_product_data->id, $product_code[$i])->first();
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $id],
                    ['variant_id', $lims_product_variant_data->variant_id],
                    ['warehouse_id', $data['to_warehouse_id']]
                ])->first();
                $product_purchase['variant_id'] = $lims_product_variant_data->variant_id;


                $update_warehousepurchase_data = warehouseProductPurchase::where([
                    ['product_id', $id],
                    ['variant_id', $lims_product_variant_data->variant_id],
                    ['warehouse_purchase_id', $data['purchase_id']]
                ])->first();
                $update_warehousepurchase_data->transferred_qty=$update_warehousepurchase_data->transferred_qty +  $quantity;
                $update_warehousepurchase_data->save();
                //add quantity to product variant table
                $lims_product_variant_data->qty += $quantity;
                $lims_product_variant_data->save();
            } else {

                $update_warehousepurchase_data = warehouseProductPurchase::where([
                    ['product_id', $id],
                    ['warehouse_purchase_id', $data['purchase_id']]
                ])->first();
                $update_warehousepurchase_data->transferred_qty=$update_warehousepurchase_data->transferred_qty + $quantity;
                $update_warehousepurchase_data->save();
                $product_purchase['variant_id'] = null;
                if ($product_purchase['product_batch_id']) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['product_batch_id', $product_purchase['product_batch_id']],
                        ['warehouse_id', $data['to_warehouse_id']],
                    ])->first();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['warehouse_id', $data['to_warehouse_id']],
                    ])->first();
                }
            }
            //add quantity to product table
            $lims_product_data->qty = $lims_product_data->qty + $quantity;
            $lims_product_data->save();
            //add quantity to warehouse
            if ($lims_product_warehouse_data) {
                $lims_product_warehouse_data->qty = $lims_product_warehouse_data->qty + $quantity;
                $lims_product_warehouse_data->product_batch_id = $product_purchase['product_batch_id'];
            } else {
                $lims_product_warehouse_data = new Product_Warehouse();
                $lims_product_warehouse_data->product_id = $id;
                $lims_product_warehouse_data->product_batch_id = $product_purchase['product_batch_id'];
                $lims_product_warehouse_data->warehouse_id = $data['to_warehouse_id'];
                $lims_product_warehouse_data->qty = $quantity;
                if ($lims_product_data->is_variant)
                    $lims_product_warehouse_data->variant_id = $lims_product_variant_data->variant_id;
            }
            //added imei numbers to product_warehouse table
            if ($imei_number[$i]) {
                if ($lims_product_warehouse_data->imei_number)
                    $lims_product_warehouse_data->imei_number .= ',' . $imei_number[$i];
                else
                    $lims_product_warehouse_data->imei_number = $imei_number[$i];
            }
            $lims_product_warehouse_data->save();

            $product_purchase['product_id'] = $id;
            $product_purchase['imei_number'] = $imei_number[$i];
            $product_purchase['qty'] = $qty[$i];
            $product_purchase['purchase_unit_id'] = $lims_purchase_unit_data->id;
            $product_purchase['net_unit_cost'] = $net_unit_cost[$i];
            $product_purchase['discount'] = $discount[$i];
            $product_purchase['tax_rate'] = $tax_rate[$i];
            $product_purchase['tax'] = $tax[$i];
            $product_purchase['total'] = $total[$i];
            $product_purchase['warehouse_id'] = $data['to_warehouse_id'];
            $product_transfer=$product_purchase;
            $product_purchase['warehouse_purchase_id'] = $lims_transfer_data2->id;
            $product_purchase['recieved'] = $qty[$i];
            $product_transfer['transfer_id'] = $lims_transfer_data->id;
            // dd($product_transfer);
             PurchaseProductTransfer::create($product_transfer);
             warehouseProductPurchase::create($product_purchase); 
        }
        $message = 'Transfer created successfully';
        
        return redirect('transfer-purchase')->with('message', $message);
    }




    
    public function purchaseTransfer($id)
    {
        $lims_product_transfer_data = PurchaseProductTransfer::where('transfer_id', $id)->get();
        foreach ($lims_product_transfer_data as $key => $product_transfer_data) {
            $product = Product::find($product_transfer_data->product_id);
            if ($product_transfer_data->purchase_unit_id != 0) {
                $unit_data = Unit::find($product_transfer_data->purchase_unit_id);
                $unit = $unit_data->unit_code;
            } else
                $unit = '';

            if ($product_transfer_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_transfer_data->product_id, $product_transfer_data->variant_id)->first();
                $product->code = $lims_product_variant_data->item_code;
            }
            if ($product_transfer_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no')->find($product_transfer_data->product_batch_id);
                $product_transfer[7][$key] = $product_batch_data->batch_no;
            } else
                $product_transfer[7][$key] = 'N/A';
            $product_transfer[0][$key] = $product->name . ' [' . $product->code . ']';
            if ($product_transfer_data->imei_number)
                $product_transfer[0][$key] .= '<br>IMEI or Serial Number: ' . $product_transfer_data->imei_number;
            $product_transfer[1][$key] = $product_transfer_data->qty;
            $product_transfer[2][$key] = $unit;
            $product_transfer[3][$key] = $product_transfer_data->tax;
            $product_transfer[4][$key] = $product_transfer_data->tax_rate;
            $product_transfer[5][$key] = $product_transfer_data->discount;
            $product_transfer[6][$key] = $product_transfer_data->total;
        }
        return $product_transfer;
    }

    public function sendMail(Request $request)
    {
        $data = $request->all();
        $lims_transfer_data = PurchaseTransfer::find($data['transfer_id']);
        //transfer $lims_transfer_data;
        $lims_product_transfer_data = PurchaseProductTransfer::where('transfer_id', $data['transfer_id'])->get();
        if ($lims_transfer_data->to_warehouse_id) {
            $lims_supplier_data = Supplier::find($lims_transfer_data->supplier_id);
            //collecting male data
            $mail_data['email'] = $lims_supplier_data->email;
            $mail_data['reference_no'] = $lims_transfer_data->reference_no;
            $mail_data['total_qty'] = $lims_transfer_data->total_qty;
            $mail_data['total_price'] = $lims_transfer_data->total_cost;
            $mail_data['order_tax'] = $lims_transfer_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_transfer_data->order_tax_rate;
            $mail_data['grand_total'] = $lims_transfer_data->grand_total;

            foreach ($lims_product_transfer_data as $key => $product_transfer_data) {
                $lims_product_data = Product::find($product_transfer_data->product_id);
                if ($product_transfer_data->variant_id) {
                    $variant_data = Variant::find($product_transfer_data->variant_id);
                    $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
                } else
                    $mail_data['products'][$key] = $lims_product_data->name;

                if ($product_transfer_data->purchase_unit_id) {
                    $lims_unit_data = Unit::find($product_transfer_data->purchase_unit_id);
                    $mail_data['unit'][$key] = $lims_unit_data->unit_code;
                } else
                    $mail_data['unit'][$key] = '';

                $mail_data['qty'][$key] = $product_transfer_data->qty;
                $mail_data['total'][$key] = $product_transfer_data->qty;
            }
            try {
                Mail::send('mail.transfer_details', $mail_data, function ($message) use ($mail_data) {
                    $message->to($mail_data['email'])->subject('Return Details');
                });
                $message = 'Mail sent successfully';
            } catch (\Exception $e) {
                $message = 'Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        } else
            $message = "This transfer doesn't belong to any supplier";

        return redirect()->back()->with('message', $message);
    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('purchasetransfer-edit')) {
            $lims_supplier_list = Supplier::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_transfer_data = PurchaseTransfer::find($id);
            $lims_product_transfer_data = PurchaseProductTransfer::where('transfer_id', $id)->get();
            return view('purchase_transfer.edit', compact('lims_supplier_list', 'lims_warehouse_list', 'lims_tax_list', 'lims_account_list', 'lims_transfer_data', 'lims_product_transfer_data'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
        ;
    }

    public function update(Request $request, $id)
    {
        $data = $request->except('document');
        //return dd($data);
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $documentName = $document->getClientOriginalName();
            $document->move('public/transfer/documents', $documentName);
            $data['document'] = $documentName;
        }

        $lims_transfer_data = PurchaseTransfer::find($id);
        $lims_product_transfer_data = PurchaseProductTransfer::where('transfer_id', $id)->get();

        $product_id = $data['product_id'];
        $imei_number = $data['imei_number'];
        $product_batch_id = $data['product_batch_id'];
        $product_code = $data['product_code'];
        $product_variant_id = $data['product_variant_id'];
        $qty = $data['qty'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];

        foreach ($lims_product_transfer_data as $key => $product_transfer_data) {
            $old_product_id[] = $product_transfer_data->product_id;
            $old_product_variant_id[] = null;
            $lims_product_data = Product::find($product_transfer_data->product_id);
            if ($product_transfer_data->purchase_unit_id != 0) {
                $lims_purchase_unit_data = Unit::find($product_transfer_data->purchase_unit_id);
                if ($lims_purchase_unit_data->operator == '*')
                    $quantity = $product_transfer_data->qty * $lims_purchase_unit_data->operation_value;
                elseif ($lims_purchase_unit_data->operator == '/')
                    $quantity = $product_transfer_data->qty / $lims_purchase_unit_data->operation_value;

                if ($product_transfer_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_transfer_data->product_id, $product_transfer_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_transfer_data->product_id, $product_transfer_data->variant_id, $lims_transfer_data->warehouse_id)
                        ->first();
                    $old_product_variant_id[$key] = $lims_product_variant_data->id;
                    $lims_product_variant_data->qty += $quantity;
                    $lims_product_variant_data->save();
                } elseif ($product_transfer_data->product_batch_id) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_transfer_data->product_id],
                        ['product_batch_id', $product_transfer_data->product_batch_id],
                        ['warehouse_id', $lims_transfer_data->warehouse_id]
                    ])->first();

                    $product_batch_data = ProductBatch::find($product_transfer_data->product_batch_id);
                    $product_batch_data->qty += $quantity;
                    $product_batch_data->save();
                } else
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_transfer_data->product_id, $lims_transfer_data->warehouse_id)
                        ->first();

                if ($product_transfer_data->imei_number) {
                    if ($lims_product_warehouse_data->imei_number)
                        $lims_product_warehouse_data->imei_number .= ',' . $product_transfer_data->imei_number;
                    else
                        $lims_product_warehouse_data->imei_number = $product_transfer_data->imei_number;
                }

                $lims_product_data->qty += $quantity;
                $lims_product_warehouse_data->qty += $quantity;
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
            }
            if ($product_transfer_data->variant_id && !(in_array($old_product_variant_id[$key], $product_variant_id))) {
                $product_transfer_data->delete();
            } elseif (!(in_array($old_product_id[$key], $product_id)))
                $product_transfer_data->delete();
        }
        foreach ($product_id as $key => $pro_id) {
            $lims_product_data = Product::find($pro_id);
            $product_transfer['variant_id'] = null;
            if ($purchase_unit[$key] != 'n/a') {
                $lims_purchase_unit_data = Unit::where('unit_name', $purchase_unit[$key])->first();
                $purchase_unit_id = $lims_purchase_unit_data->id;
                if ($lims_purchase_unit_data->operator == '*')
                    $quantity = $qty[$key] * $lims_purchase_unit_data->operation_value;
                elseif ($lims_purchase_unit_data->operator == '/')
                    $quantity = $qty[$key] / $lims_purchase_unit_data->operation_value;

                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($pro_id, $lims_product_variant_data->variant_id, $data['warehouse_id'])
                        ->first();
                    $variant_data = Variant::find($lims_product_variant_data->variant_id);

                    $product_transfer['variant_id'] = $lims_product_variant_data->variant_id;
                    $lims_product_variant_data->qty -= $quantity;
                    $lims_product_variant_data->save();
                } elseif ($product_batch_id[$key]) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['product_batch_id', $product_batch_id[$key]],
                        ['warehouse_id', $data['warehouse_id']]
                    ])->first();

                    $product_batch_data = ProductBatch::find($product_batch_id[$key]);
                    $product_batch_data->qty -= $quantity;
                    $product_batch_data->save();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])
                        ->first();
                }
                //deduct imei number if available
                if ($imei_number[$key]) {
                    $imei_numbers = explode(",", $imei_number[$key]);
                    $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number);
                    foreach ($imei_numbers as $number) {
                        if (($j = array_search($number, $all_imei_numbers)) !== false) {
                            unset($all_imei_numbers[$j]);
                        }
                    }
                    $lims_product_warehouse_data->imei_number = implode(",", $all_imei_numbers);
                }

                $lims_product_data->qty -= $quantity;
                $lims_product_warehouse_data->qty -= $quantity;

                $lims_product_data->save();
                $lims_product_warehouse_data->save();
            }

            if ($lims_product_data->is_variant)
                $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
            else
                $mail_data['products'][$key] = $lims_product_data->name;

            if ($purchase_unit_id)
                $mail_data['unit'][$key] = $lims_purchase_unit_data->unit_code;
            else
                $mail_data['unit'][$key] = '';

            $mail_data['qty'][$key] = $qty[$key];
            $mail_data['total'][$key] = $total[$key];

            $product_transfer['transfer_id'] = $id;
            $product_transfer['product_id'] = $pro_id;
            $product_transfer['imei_number'] = $imei_number[$key];
            $product_transfer['product_batch_id'] = $product_batch_id[$key];
            $product_transfer['qty'] = $qty[$key];
            $product_transfer['purchase_unit_id'] = $purchase_unit_id;
            $product_transfer['net_unit_cost'] = $net_unit_cost[$key];
            $product_transfer['discount'] = $discount[$key];
            $product_transfer['tax_rate'] = $tax_rate[$key];
            $product_transfer['tax'] = $tax[$key];
            $product_transfer['total'] = $total[$key];

            if ($product_transfer['variant_id'] && in_array($product_variant_id[$key], $old_product_variant_id)) {
                PurchaseProductTransfer::where([
                    ['product_id', $pro_id],
                    ['variant_id', $product_transfer['variant_id']],
                    ['transfer_id', $id]
                ])->update($product_transfer);
            } elseif ($product_transfer['variant_id'] === null && (in_array($pro_id, $old_product_id))) {
                PurchaseProductTransfer::where([
                    ['transfer_id', $id],
                    ['product_id', $pro_id]
                ])->update($product_transfer);
            } else
                PurchaseProductTransfer::create($product_transfer);
        }
        $lims_transfer_data->update($data);
        $message = 'Return updated successfully';
        if ($data['supplier_id']) {
            $lims_supplier_data = Supplier::find($data['supplier_id']);
            //collecting male data
            $mail_data['email'] = $lims_supplier_data->email;
            $mail_data['reference_no'] = $lims_transfer_data->reference_no;
            $mail_data['total_qty'] = $lims_transfer_data->total_qty;
            $mail_data['total_price'] = $lims_transfer_data->total_cost;
            $mail_data['order_tax'] = $lims_transfer_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_transfer_data->order_tax_rate;
            $mail_data['grand_total'] = $lims_transfer_data->grand_total;

            try {
                Mail::send('mail.transfer_details', $mail_data, function ($message) use ($mail_data) {
                    $message->to($mail_data['email'])->subject('Return Details');
                });
            } catch (\Exception $e) {
                $message = 'Return updated successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }
        return redirect('transfer-purchase')->with('message', $message);
    }

    public function deleteBySelection(Request $request)
    {
        $transfer_id = $request['transferIdArray'];
        foreach ($transfer_id as $id) {
            $lims_transfer_data = PurchaseTransfer::find($id);
            $lims_product_transfer_data = PurchaseProductTransfer::where('transfer_id', $id)->get();

            foreach ($lims_product_transfer_data as $key => $product_transfer_data) {
                $lims_product_data = Product::find($product_transfer_data->product_id);

                if ($product_transfer_data->purchase_unit_id != 0) {
                    $lims_purchase_unit_data = Unit::find($product_transfer_data->purchase_unit_id);

                    if ($lims_purchase_unit_data->operator == '*')
                        $quantity = $product_transfer_data->qty * $lims_purchase_unit_data->operation_value;
                    elseif ($lims_purchase_unit_data->operator == '/')
                        $quantity = $product_transfer_data->qty / $lims_purchase_unit_data->operation_value;

                    if ($product_transfer_data->variant_id) {
                        $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_transfer_data->product_id, $product_transfer_data->variant_id)->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_transfer_data->product_id, $product_transfer_data->variant_id, $lims_transfer_data->warehouse_id)->first();
                        $lims_product_variant_data->qty += $quantity;
                        $lims_product_variant_data->save();
                    } elseif ($product_transfer_data->product_batch_id) {
                        $lims_product_batch_data = ProductBatch::find($product_transfer_data->product_batch_id);
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_batch_id', $product_transfer_data->product_batch_id],
                            ['warehouse_id', $lims_transfer_data->warehouse_id]
                        ])->first();

                        $lims_product_batch_data->qty += $product_transfer_data->qty;
                        $lims_product_batch_data->save();
                    } else
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_transfer_data->product_id, $lims_transfer_data->warehouse_id)->first();

                    $lims_product_data->qty += $quantity;
                    $lims_product_warehouse_data->qty += $quantity;
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                    $product_transfer_data->delete();
                }
            }
            $lims_transfer_data->delete();
        }
        return 'Return deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_transfer_data = PurchaseTransfer::find($id);
        $lims_product_transfer_data = PurchaseProductTransfer::where('transfer_id', $id)->get();

        foreach ($lims_product_transfer_data as $key => $product_transfer_data) {
            $lims_product_data = Product::find($product_transfer_data->product_id);

            if ($product_transfer_data->purchase_unit_id != 0) {
                $lims_purchase_unit_data = Unit::find($product_transfer_data->purchase_unit_id);

                if ($lims_purchase_unit_data->operator == '*')
                    $quantity = $product_transfer_data->qty * $lims_purchase_unit_data->operation_value;
                elseif ($lims_purchase_unit_data->operator == '/')
                    $quantity = $product_transfer_data->qty / $lims_purchase_unit_data->operation_value;

                if ($product_transfer_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_transfer_data->product_id, $product_transfer_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_transfer_data->product_id, $product_transfer_data->variant_id, $lims_transfer_data->warehouse_id)->first();
                    $lims_product_variant_data->qty += $quantity;
                    $lims_product_variant_data->save();
                } elseif ($product_transfer_data->product_batch_id) {
                    $lims_product_batch_data = ProductBatch::find($product_transfer_data->product_batch_id);
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_batch_id', $product_transfer_data->product_batch_id],
                        ['warehouse_id', $lims_transfer_data->warehouse_id]
                    ])->first();

                    $lims_product_batch_data->qty += $product_transfer_data->qty;
                    $lims_product_batch_data->save();
                } else
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_transfer_data->product_id, $lims_transfer_data->warehouse_id)->first();

                if ($product_transfer_data->imei_number) {
                    if ($lims_product_warehouse_data->imei_number)
                        $lims_product_warehouse_data->imei_number .= ',' . $product_transfer_data->imei_number;
                    else
                        $lims_product_warehouse_data->imei_number = $product_transfer_data->imei_number;
                }

                $lims_product_data->qty += $quantity;
                $lims_product_warehouse_data->qty += $quantity;
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
                $product_transfer_data->delete();
            }
        }
        $lims_transfer_data->delete();
        return redirect('transfer-purchase')->with('not_permitted', 'Data deleted successfully');
        ;
    }
}