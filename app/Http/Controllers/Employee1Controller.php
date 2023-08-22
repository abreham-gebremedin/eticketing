<?php

namespace App\Http\Controllers;
use App\Employee1;

use Illuminate\Http\Request;
use App\Warehouse;
use App\Supplier;
use App\Unit;
use App\Tax;
use App\Account;
use App\Purchase;
use App\ProductPurchase;
use App\Product_Warehouse;
use App\Payment;
use App\PaymentWithCheque;
use App\PaymentWithCreditCard;
use App\PosSetting;
use DB;
use App\GeneralSetting;
use Stripe\Stripe;
use Auth;
use App\User;
use App\ProductVariant;
use App\ProductBatch;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class Employee1Controller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('purchases-add')){ 
  
            return view('employee.newindex');
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }
    public function getData()
    {
    $data = Employee1::all(); 
    return response()->json($data);
    }

 
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
         
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
