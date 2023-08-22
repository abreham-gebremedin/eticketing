<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\AccountTransactionAdjustment;
use App\CashRegister;
use App\ChartofAccount;
use App\Expense;
use App\GeneralSetting;
use App\Journal_Entry;
use App\Warehouse;
use DateTime;
use DB;
use Exception;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Account;

class AccountTransactionAdjustmentController extends Controller
{
    //
    public function index(Request $request)
    {
        $expenses  = AccountTransactionAdjustment::select('account_transactions_adjusment.*')
        ->get();
        // dd($expenses);
        $general_setting = DB::table('general_settings')->latest()->first();
        
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('transaction_adjustments-index')){
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';

            if($request->starting_date) {
                $starting_date = $request->starting_date;
                $ending_date = $request->ending_date;
            }
            else {
                $starting_date = $general_setting->fiscal_year;
                $ending_date = date("Y-m-d");
            }

            if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            $lims_warehouse_list = Warehouse::select('name', 'id')->where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            return view('transaction_adjustment.index', compact('lims_account_list', 'lims_warehouse_list', 'all_permission', 'starting_date', 'ending_date', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function index1(Request $request)
    {
        $expenses  = AccountTransactionAdjustment::select('account_transactions_adjusment.*')
         ->get();
        // dd($expenses);
        $general_setting = DB::table('general_settings')->latest()->first();
        
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('transaction_adjustments-index')){
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';

            if($request->starting_date) {
                $starting_date = $request->starting_date;
                $ending_date = $request->ending_date;
            }
            else {
                $starting_date = $general_setting->fiscal_year;
                $ending_date = date("Y-m-d");
            }
             if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            $lims_warehouse_list = Warehouse::select('name', 'id')->where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            return view('transaction_adjustment.index1', compact('lims_account_list', 'lims_warehouse_list', 'all_permission', 'starting_date', 'ending_date', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }


     
    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('warehousepurchases-add')){
             $lims_warehouse_list = Warehouse::where('is_active', true)->get();
             $lims_product_list_with_variant = ChartofAccount::all();
             /*$lims_new_product_list_with_variant = $this->newProductWithVariant();*/

            return view('transaction_adjustment.create', compact( 'lims_warehouse_list', 'lims_product_list_with_variant'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }


   
    public function journalData($id)
    {
        $lims_journal_data = DB::table('journal_entries')
        ->join('chartof_accounts','journal_entries.chartof_accounts_id'  , '=','chartof_accounts.id' )
        ->join('account_transactions', 'journal_entries.account_transaction_id'  , '=','account_transactions.id' )
        ->where('lims_AccountTransactionAdjustment_id', $id)
        ->select('chartof_accounts.code', 'chartof_accounts.name', 'account_transactions.debit', 'account_transactions.reference_no','account_transactions.credit')
        ->groupBy('chartof_accounts.code', 'chartof_accounts.name', 'account_transactions.debit','account_transactions.reference_no', 'account_transactions.credit')
        ->get();

 
        foreach ($lims_journal_data as $key => $journal_data) {
           
            $journal[0][$key] =$journal_data->code;
            $journal[1][$key] = $journal_data->name;
            $journal[2][$key] =$journal_data->debit;
            $journal[3][$key] = $journal_data->credit; 
        }
        return $journal;
    }
    // expenseData
    public function expenseData(Request $request)
    {
        $startdate = $request->input('starting_date');
        $enddate = $request->input('ending_date');
        
        $warehouse_id = $request->input('warehouse_id');
        $q = AccountTransactionAdjustment::where('is_all', 1);
        
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $q = $q->where('user_id', Auth::id());
        }
        if ($warehouse_id) {
            $q = $q->where('warehouse_id', $warehouse_id);
        }
        
        $totalData = $q->count();
        $totalFiltered = $totalData;
        
        if ($request->input('length') != -1) {
            $limit = $request->input('length');
        } else {
            $limit = $totalData;
        }
        
        if (empty($request->input('search.value'))) {
            $q = AccountTransactionAdjustment::where('is_all', 1)
                ->offset($request->input('start'))
                ->limit($limit)
                ->orderBy("created_at", "DESC");
        
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $q = $q->where('user_id', Auth::id());
            }
            if ($warehouse_id) {
                $q = $q->where('warehouse_id', $warehouse_id);
            }
        
            $expenses = $q->get();
        } else {
            $search = $request->input('search.value');
            $q = AccountTransactionAdjustment::where('is_all', 1)
                ->where(function ($query) use ($search) {
                    $query->whereDate('account_transactions_adjusment.created_at', '=', date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                        ->orWhere('reference_no', 'LIKE', "%{$search}%");
                })
                ->offset($request->input('start'))
                ->limit($limit)
                ->orderBy("created_at", "DESC");
        
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $q = $q->where('user_id', Auth::id());
                $totalFiltered = $q->count();
                $expenses = $q->get();
            } else {
                $expenses = $q->get();
                $totalFiltered = $q->count();
            }
        }
        
        $data = array();
        if (!empty($expenses)) {
            foreach ($expenses as $key => $expense) {
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($expense->created_at->toDateString()));
                $nestedData['reference_no'] = $expense->reference_no;
                
                if ($expense->warehouse_id == 0) {
                    $nestedData['warehouse'] = "For All Warehouse";
                } else {
                    $warehouse = Warehouse::where('id', $expense->warehouse_id)->first();
                    $nestedData['warehouse'] = $warehouse->name;
                }
                
                $user = User::where('id', $expense->user_id)->first();
                $nestedData['user'] = $user->name;
                $nestedData['options'] = '';
        
                // Data for details by one click
                $user = User::find($expense->user_id);
        
                $nestedData['purchase'] = array(
                    '[ "' . date(config('date_format'), strtotime($expense->created_at->toDateString())) . '"',
                    ' "' . $expense->reference_no . '"',
                    ' "' . $expense->id . '"',
                    ' "' . $expense->warehouse->name . '"',
                    ' "' . preg_replace('/\s+/S', " ", $expense->reason) . '"',
                    ' "' . $user->name . '"',
                    ' "' . $user->email . '"]'
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
        
        return response()->json($json_data);
        
    }



    
    public function journalAdjustmentData(Request $request)
    {
       $startdate= $request->input('starting_date');
       $enddate= $request->input('ending_date');
       
      
        $warehouse_id = $request->input('warehouse_id');
        $q = AccountTransactionAdjustment::whereBetween('created_at', [$startdate, $enddate])->where('is_adjustment',1);
                    
        if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('user_id', Auth::id());
        if($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
         if(empty($request->input('search.value'))) {
            $q = AccountTransactionAdjustment::where('is_adjustment',1)
                 ->limit($limit)
                ->orderBy("created_at", "DESC");
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
                $q = $q->where('user_id', Auth::id());
            if($warehouse_id)
                $q = $q->where('warehouse_id', $warehouse_id);
            $expenses = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = AccountTransactionAdjustment::whereDate('account_transactions_adjusment.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
            ->where('is_adjustment',1)
                  ->limit($limit)
                ->orderBy("created_at", "DESC");
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $expenses =  $q->select('account_transactions_adjusment.*')
                ->where('is_adjustment',1)
                                 ->where('account_transactions_adjusment.user_id', Auth::id())
                                ->orwhere([
                                    ['reference_no', 'LIKE', "%{$search}%"],
                                    ['user_id', Auth::id()]
                                ])
                                ->get();
                $totalFiltered = $q->where('account_transactions_adjusment.user_id', Auth::id())->count();
            }
            else {
                $expenses =  $q->select('account_transactions_adjusment.*')
                ->where('is_adjustment',1)
                                 ->orwhere('reference_no', 'LIKE', "%{$search}%")
                                ->get();

                $totalFiltered = $q->orwhere('account_transactions_adjusment.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($expenses))
        {
            foreach ($expenses as $key=>$expense)
            {
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($expense->created_at->toDateString()));
                $nestedData['reference_no'] = $expense->reference_no;
                if ($expense->warehouse_id==0) {
                    # code...
                    $nestedData['warehouse'] = "For All Warehouse";

                } else {
                    # code...
                    $warehouse=Warehouse::where('id',$expense->warehouse_id)->first();
                    $nestedData['warehouse'] = $warehouse->name;

                }
                $user=User::where('id',$expense->user_id)->first();
                $nestedData['user'] = $user->name;
                $nestedData['options'] = ' <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                            <li>
                            <button type="button" class="btn btn-link view"><i class="fa fa-eye"></i> '.trans('file.View').'</button>
                        </li>';
              
                if(in_array("expenses-delete", $request['all_permission']))
                    $nestedData['options'] .= \Form::open(["route" => ["transaction_adjustments.destroy", $expense->id], "method" => "DELETE"] ).'
                            <li>
                              <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.trans("file.delete").'</button> 
                            </li>'.\Form::close().'
                        </ul>
                    </div>';
        // data for  details by one click
        $user = User::find($expense->user_id);

        $nestedData['purchase'] = array( '[ "'.date(config('date_format'), strtotime($expense->created_at->toDateString())).'"', ' "'.$expense->reference_no.'"',' "'.$expense->id.'"', ' "'.$expense->warehouse->name.'"',  ' "'.preg_replace('/\s+/S', " ", $expense->reason).'"', ' "'.$user->name.'"', ' "'.$user->email.'"]'
        );
        $data[] = $nestedData;            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );   
          echo json_encode($json_data);
    }

    public function store(Request $request)
    {

        DB::beginTransaction();
        try {
        $data = $request->except('document');
        $data2['reference_no'] = 'transaction-adjustment-' . date("Ymd") . '-'. date("his"); 


        if($data['reason']=="" || ctype_space($data['reason'])  || $data['reason']== null )
        {
            return redirect()->back()->with('not_permitted', 'Reason can not be empty, You shold write the reason why you are adjusting the transaction ');

        }  
        if(strlen($data['reason'])< 3 )
        {
            return redirect()->back()->with('not_permitted', ' Reason Should be at least 50 Characters');
        } 
        $data2['reason'] = $data['reason'];
        if(isset($data['created_at']))
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        else
            $data['created_at'] = date("Y-m-d H:i:s");

        $data2['user_id'] = Auth::id();
        $data2['created_at'] = $data['created_at'] ;
        $data2['warehouse_id'] = $data['warehouse_id'] ;
        $data2['is_adjustment'] = true ;
         $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($data2);

        
        $chartof_accounts_id = $data['product_id'];
        $name = $data['product_name'];
        $debit = $data['debit'];
        $credit = $data['credit'];
        $journal_entry = [];
        foreach ($chartof_accounts_id as $i => $id) {
            $journal_entry['lims_AccountTransactionAdjustment_id'] = $lims_AccountTransactionAdjustment_data->id ;
            if( $debit[$i]>0 && $credit[$i]>0 )
            {
                return redirect()->back()->with('not_permitted', 'You can not have debit and and credit value at the same time, the one side must be zero ');
    
            }
    
            if($debit[$i]==0 &&$credit[$i]==0 )
            {
                return redirect()->back()->with('not_permitted', ' debit and and credit value can not be 0 at the same time ');
    
            }



            if ($credit[$i]==0) {
                # code...
                $transaction = new AccountTransaction;
                $transaction->reference_no =   $lims_AccountTransactionAdjustment_data->reference_no;
                $transaction->date = date("Y-m-d H:i:s");
                $transaction->user_id = Auth::id();
                $transaction->warehouse_id =$lims_AccountTransactionAdjustment_data->warehouse_id;
                $transaction->debit = $debit[$i];
                $transaction->credit = 0;
                $transaction->chartof_accounts_id = $id;
                $transaction->accounttransactiontdjustment_id = $lims_AccountTransactionAdjustment_data->id;
                $transaction->save();
                $coaname=ChartofAccount::where('name',$name[$i])->first();
                if($coaname){
                    $journal_entry['account_id'] = $coaname->id;
    
                }
                $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
                $journal_entry['chartof_accounts_id'] = $id; 
                $journal_entry['account_transaction_id'] = $transaction->id;
                Journal_Entry::create($journal_entry);

    
            }else {
                $transaction = new AccountTransaction;
                $transaction->reference_no = $lims_AccountTransactionAdjustment_data->reference_no;   
                $transaction->date = date("Y-m-d H:i:s");
                $transaction->user_id = Auth::id();
                $transaction->warehouse_id =  $lims_AccountTransactionAdjustment_data->warehouse_id;
                $transaction->debit = 0;
                $transaction->credit = $credit[$i];
                $transaction->chartof_accounts_id = $id;
                $transaction->accounttransactiontdjustment_id = $lims_AccountTransactionAdjustment_data->id;
                $transaction->save();

                $lims_transaction_data = AccountTransaction::latest()->first();

                $coaname=ChartofAccount::where('name',$name[$i])->first();
                if($coaname){
                    $journal_entry['account_id'] = $coaname->id;
    
                }
                $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
                $journal_entry['chartof_accounts_id'] = $id; 
                $journal_entry['account_transaction_id'] = $transaction->id;
                Journal_Entry::create($journal_entry);
     
            }     

    


            
        }
            // Log the rejection
            activity()
            ->performedOn($lims_AccountTransactionAdjustment_data)
            ->causedBy(Auth::user())
               ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = ChartofAccount::STATUS_APPROVED; // Set the value of the `is_active` column

            })
            
            ->log('New  New Journal Inserted');
    DB::commit();
    return redirect('transaction_adjustments')->with('message', 'New Journal data has been saved successfully.');
    
        
    } catch (Exception $e) {
        DB::rollback();
        return redirect()->back()->with('not_permitted', 'An error occurred while saving the Journal data. Please try again later.'.$e->getMessage());
    }
   }
    // public function store1(Request $request)
    // {

    //     DB::beginTransaction();
    //     try {
    //     // expense_category_id
    //     $data = $request->all();





    //     $coaname=ChartofAccount::where('id',$data['chartof_accounts_id'])->first();
    //     $baname=Account::where('id',$data['account_id'])->first();


    //    if (Str::startsWith($coaname->name, "Cash")){
    //         # code...
    //         if ($coaname->name!=$baname->name) {
    //             # code...
    //             return redirect('transaction_adjustments')->with('not_permitted', ' The Cash in chart of account and The Cash At the Bank are not the same ');

    //         }
 
    //     }


   
    //     $lims_chart_of_account_data=ChartofAccount::find($data['chartof_accounts_id']);
    //     $data['date']=date("Y-m-d H:i:s");
    //     if(isset($data['created_at']))
    //         $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
    //     else
    //         $data['created_at'] = date("Y-m-d H:i:s");
    //      $data['user_id'] = Auth::id();
        
    //     $lims_AccountTransactionAdjustment_data= AccountTransactionAdjustment::create($data);
         

                     
       

    //     DB::commit();
    
    //     return redirect('transaction_adjustments')->with('message', 'Transaction Adjusted successfully');

    // } catch (Exception $e) {
    //     DB::rollback();

    //     return redirect('expenses')->with('error', $e->getMessage());
    // }
    // }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('hasPermissionTo-edit')) {
            $lims_expense_data = AccountTransactionAdjustment::find($id);
            $lims_expense_data->date = date('d-m-Y', strtotime($lims_expense_data->created_at->toDateString()));
            return $lims_expense_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {

        DB::beginTransaction();
        try {
        // expense_category_id
        $data = $request->all();

        if($data['reason']=="" || ctype_space($data['reason'])  || $data['reason']== null )
        {
            redirect('transaction_adjustments')->with('not_permitted', 'Reason can not be empty, You shold write the reason why you are adjusting the transaction ');

        }

        if($data['debit']>0 && $data['credit']>0 )
        {
            return redirect('transaction_adjustments')->with('not_permitted', 'You can nott have debit and and credit value at the same time, the one side must be zero ');

        }

        if($data['debit']==0 && $data['credit']==0 )
        {
            return redirect('transaction_adjustments')->with('not_permitted', ' debit and and credit value can not be 0 at the same time ');

        }

        $coaname=ChartofAccount::where('id',$data['chartof_accounts_id'])->first();
        $baname=Account::where('id',$data['account_id'])->first();


       if (Str::startsWith($coaname->name, "Cash")){
            # code...
            if ($coaname->name!=$baname->name) {
                # code...
                return redirect('transaction_adjustments')->with('not_permitted', ' The Cash in chart of account and The Cash At the Bank are not the same ');

            }
 
        }


     if(strlen($data['reason'])< 100 )
        {
            return redirect('transaction_adjustments')->with('not_permitted', ' Reason Should be at least 100 Characters');

        }
 
        $lims_chart_of_account_data=ChartofAccount::find($data['chartof_accounts_id']);
        $data['date']=date("Y-m-d H:i:s");
        $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
   
        
         
        $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::find($data['expense_id']);
        $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        $lims_AccountTransactionAdjustment_data->update($data);

                 

        $transaction = AccountTransaction::where('accounttransactiontdjustment_id', $lims_AccountTransactionAdjustment_data->id)->first();       
        if ($data['credit']==0) {
            # code...
             
            $transaction->warehouse_id = $data['warehouse_id'];
            $transaction->debit = $data['debit'];
            $transaction->credit = 0;
            $accountType = ChartofAccount::where('name', $lims_chart_of_account_data->name, '')->first();
            $transaction->chartof_accounts_id = $accountType->id;
            $transaction->accounttransactiontdjustment_id = $lims_AccountTransactionAdjustment_data->id;
            $transaction->save();

        }else {
            $data['reference_no'] = 'credit-transaction-adjustment-' . date("Ymd") . '-'. date("his");
            $transaction->warehouse_id =  $data['warehouse_id'] ;
            $transaction->debit = 0;
            $transaction->credit = $data['credit'];
            $accountType = ChartofAccount::where('name',$lims_chart_of_account_data->name, '')->first();
            $transaction->chartof_accounts_id = $accountType->id;
            $transaction->accounttransactiontdjustment_id = $lims_AccountTransactionAdjustment_data->id;
            $transaction->save();
        }

        DB::commit();
    
        return redirect('transaction_adjustments')->with('message', 'Data updated successfully');

    } catch (Exception $e) {
        DB::rollback();

        return redirect('expenses')->with('error', $e->getMessage());
    }
    }
    

    public function deleteBySelection(Request $request)
    {
        DB::beginTransaction();
        try {
        $expense_id = $request['expenseIdArray'];
        $data = array();
        $expenseIdArray = array();
        foreach ($expense_id as $id) {
            $lims_expense_data = AccountTransactionAdjustment::find($id);
            $lims_expense_data->delete();
            $data[] = $lims_expense_data;
            $expenseIdArray[] = $lims_expense_data->id;

        }

         // Log the status change and the old and new values
         activity()
         ->performedOn($lims_expense_data)
         ->causedBy(Auth::user())
         ->withProperties([
             'data' => $data,
             'expenseIdArray' => $expenseIdArray,
       
          ])
          ->tap(function ($activity) {
             $activity->is_active = true; // Set the value of the `is_active` column
             $activity->status = Expense::STATUS_APPROVED; // Set the value of the `is_active` column
             $activity->url = "transaction_adjustments"; // Set the value of the `is_active` column
             $activity->is_root = 1; // Set the value of the `is_active` column

         });

        DB::commit();
    
        return redirect('transaction_adjustments')->with('not_permitted', 'Data deleted successfully');

    } catch (Exception $e) {
        DB::rollback();

        return redirect('expenses')->with('error', $e->getMessage());
    }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
        $lims_expense_data = AccountTransactionAdjustment::find($id);
        $lims_expense_data->delete();

        activity()
        ->performedOn($lims_expense_data)
        ->causedBy(Auth::user())
        ->withProperties([
            'data' => $lims_expense_data,
       
         ])
         ->tap(function ($activity) {
            $activity->is_active = true; // Set the value of the `is_active` column
            $activity->status = Expense::STATUS_APPROVED; // Set the value of the `is_active` column
            $activity->url = "transaction_adjustments"; // Set the value of the `is_active` column
            $activity->is_root = 1; // Set the value of the `is_active` column

        });

        DB::commit();
    
        return redirect('transaction_adjustments')->with('not_permitted', 'Data deleted successfully');

    } catch (Exception $e) {
        DB::rollback();

        return redirect('expenses')->with('error', $e->getMessage());
    }

}
}
