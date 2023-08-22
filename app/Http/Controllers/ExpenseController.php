<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\AccountTransactionAdjustment;
use App\ChartofAccount;
use App\ExpenseCategory;
use App\Journal_Entry;
use Exception;
use Illuminate\Http\Request;
use App\Expense;
use App\Account;
use App\Warehouse;
use App\CashRegister;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('expenses-index')){
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
                $starting_date = date('Y-m-01', strtotime('-1 year', strtotime(date('Y-m-d'))));
                $ending_date = date("Y-m-d");
            }

            if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            $lims_warehouse_list = Warehouse::select('name', 'id')->where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            return view('expense.index', compact('lims_account_list', 'lims_warehouse_list', 'all_permission', 'starting_date', 'ending_date', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function expenseData(Request $request)
    {
        $columns = array( 
            1 => 'created_at', 
            2 => 'reference_no',
        );
        
        $warehouse_id = $request->input('warehouse_id');
        $q = Expense::whereDate('created_at', '>=' ,$request->input('starting_date'))
                     ->whereDate('created_at', '<=' ,$request->input('ending_date'));
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
        $start = $request->input('start');
        $order = 'expenses.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if(empty($request->input('search.value'))) {
            $q = Expense::with('warehouse')
                ->whereDate('created_at', '>=' ,$request->input('starting_date'))
                ->whereDate('created_at', '<=' ,$request->input('ending_date'))
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
                $q = $q->where('user_id', Auth::id());
            if($warehouse_id)
                $q = $q->where('warehouse_id', $warehouse_id);
            $expenses = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = Expense::whereDate('expenses.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                ->offset($start)
                ->limit($limit)
                ->orderBy($order,$dir);
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $expenses =  $q->select('expenses.*')
                                ->with('warehouse')
                                ->where('expenses.user_id', Auth::id())
                                ->orwhere([
                                    ['reference_no', 'LIKE', "%{$search}%"],
                                    ['user_id', Auth::id()]
                                ])
                                ->get();
                $totalFiltered = $q->where('expenses.user_id', Auth::id())->count();
            }
            else {
                $expenses =  $q->select('expenses.*')
                                ->with('warehouse')
                                ->orwhere('reference_no', 'LIKE', "%{$search}%")
                                ->get();

                $totalFiltered = $q->orwhere('expenses.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($expenses))
        {
            foreach ($expenses as $key=>$expense)
            {
                $nestedData['id'] = $expense->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($expense->created_at->toDateString()));
                $nestedData['reference_no'] = $expense->reference_no;
                $chartofaccount=ChartofAccount::where('id',$expense->expense_category_id)->first();
                $nestedData['warehouse'] = $expense->warehouse->name;
                $nestedData['expenseCategory'] = $chartofaccount->name;
                $nestedData['amount'] = number_format($expense->amount, 2);
                $nestedData['note'] = $expense->note;
                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';
                if(in_array("expenses-edit", $request['all_permission'])) {
                    $nestedData['options'] .= '<li>
                        <button type="button" data-id="'.$expense->id.'" class="open-Editexpense_categoryDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i>'.trans('file.edit').'</button>
                        </li>';
                }
                if(in_array("expenses-delete", $request['all_permission']))
                    $nestedData['options'] .= \Form::open(["route" => ["expenses.destroy", $expense->id], "method" => "DELETE"] ).'
                            <li>
                              <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.trans("file.delete").'</button> 
                            </li>'.\Form::close().'
                        </ul>
                    </div>';
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );    
        echo json_encode($json_data);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
    
            $data = $request->all();
            if(isset($data['created_at']))
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            else
                $data['created_at'] = date("Y-m-d H:i:s");
            $data['reference_no'] = 'er-' . date("Ymd") . '-'. date("his");
            $data['user_id'] = Auth::id();
            $cash_register_data = CashRegister::where([
                ['user_id', $data['user_id']],
                ['warehouse_id', $data['warehouse_id']],
                ['status', true]
            ])->first();
            if($cash_register_data)
                $data['cash_register_id'] = $cash_register_data->id;
            $lims_expense_data=Expense::create($data);
            $dataad['user_id'] = Auth::id();
            $dataad['created_at'] = $data['created_at'] ;
            $dataad['warehouse_id'] = $data['warehouse_id'] ;
            $dataad['reference_no'] = $data['reference_no'] ;
            $dataad['reason'] = $data['reference_no'] .$data['note'] ;
            $dataad['is_adjustment'] = false ;
            $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);

       

            
            $transaction1 = new AccountTransaction;
            $transaction1->reference_no = $data['reference_no'] ;
            $transaction1->date = date("Y-m-d H:i:s");
            $transaction1->user_id	 = Auth::id();
            $transaction1->warehouse_id = $data['warehouse_id'];
            $transaction1->debit = $data['amount'];
            $transaction1->credit = 0;
            $transaction1->chartof_accounts_id = $data['expense_category_id'];
            $transaction1->expense_id = $lims_expense_data->id;
            $transaction1->save();

            $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
            $journal_entry['chartof_accounts_id'] = $data['expense_category_id'];
            $journal_entry['account_transaction_id'] = $transaction1->id;
            Journal_Entry::create($journal_entry);

            $transaction2 = new AccountTransaction;
            $transaction2->reference_no = $data['reference_no'] ;
            $transaction2->date = date("Y-m-d H:i:s");
            $transaction2->user_id	 = Auth::id();
            $transaction2->warehouse_id = $data['warehouse_id'];
            $transaction2->debit = 0;
            $transaction2->credit = $data['amount'];
            $account_name=Account::where('id',$data['account_id'])->first();
            $accountType2 = ChartofAccount::where('name', $account_name->cname)->first();
            $transaction2->chartof_accounts_id = $accountType2->id;
            $transaction2->expense_id = $lims_expense_data->id;
            $transaction2->save();

            $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
            $journal_entry['account_id'] = $data['account_id'];
            $journal_entry['chartof_accounts_id'] = $accountType2->id;
            $journal_entry['account_transaction_id'] = $transaction2->id;

            Journal_Entry::create($journal_entry);

            DB::commit();
    
            return redirect('expenses')->with('message', 'Data inserted successfully');
    
        } catch (Exception $e) {
            DB::rollback();
             return redirect('expenses')->with('not_permitted', $e->getMessage());
        }
    }
    

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('expenses-edit')) {
            $lims_expense_data = Expense::find($id);
            $lims_expense_data->date = date('d-m-Y', strtotime($lims_expense_data->created_at->toDateString()));
            return $lims_expense_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));

            $lims_expense_data = Expense ::where('id', $data['expense_id'])->lockForUpdate()->firstOrFail();
    
            // Check if the lims_expense_data is a draft or approved
            if ($lims_expense_data->isDraft()) {
                throw new Exception('Expense is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
            }
    
            // Get the original data before making any changes
            $originalData = $lims_expense_data->getOriginal();
    
            // // Update the lims_expense_data
            $data['updated_by'] = Auth::user()->id;
            $lims_expense_data->update($data);
    
           
            
            // Get the original data before making any changes
                
                $newData = $lims_expense_data->getAttributes();
                $lims_expense_data->update($originalData);
                // Get the attributes that exist in both $request and $originalData             
    
                if ($newData != $originalData) {
                // There is an update
                $lims_expense_data->status = Expense::STATUS_DRAFT;
                $lims_expense_data->updated_by = Auth::user()->id;
                $lims_expense_data->save();
    
                // Log the status change and the old and new values
                activity()
                    ->performedOn($lims_expense_data)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'old' => $originalData,
                        'new' => $newData,
                  
                     ])
                     ->tap(function ($activity) {
                        $activity->is_active = true; // Set the value of the `is_active` column
                        $activity->status = Expense::STATUS_DRAFT; // Set the value of the `is_active` column
                        $activity->url = "expenses"; // Set the value of the `is_active` column
                        $activity->is_root = 1; // Set the value of the `is_active` column

                    })
                    
                    ->log('Expense status updated');
                    }

    
            DB::commit();
    
            return redirect('expenses')->with('message', 'Data updated successfully, Please wait for approval');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'An error occurred while updating data. Please try again later.'.$e->getMessage());
        }
    } 

    public function deleteBySelection(Request $request)
    {
        $expense_id = $request['expenseIdArray'];
        foreach ($expense_id as $id) {
            $lims_expense_data = Expense::find($id);
            $lims_expense_data->delete();
        }
        return 'Expense deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_expense_data = Expense::find($id);
        $lims_expense_data->delete();
        return redirect('expenses')->with('not_permitted', 'Data deleted successfully');
    }

    public function approve(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));

            $lims_expense_data = Expense ::where('id', $id)->lockForUpdate()->firstOrFail();
    
            // Check if the lims_expense_data is a draft or approved
            if (!$lims_expense_data->isDraft()) {
                throw new Exception('Expense is not in pending status for Aproval!!');
            }

            $logs = Activity::where('subject_type', Expense::class)
            ->where('subject_id', $id)
            ->where('status', Expense::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $newData = $properties['new'];
            $lims_expense_data->update($newData);
            $lims_expense_data->status = Expense::STATUS_APPROVED;
            $lims_expense_data->save();
    
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => Expense::STATUS_APPROVED]);
            $lims_account_transaction_data = AccountTransaction::where('fixed_asset_id', $id)->get();

            foreach ($lims_account_transaction_data as $transaction) {
                if ($transaction->debit > 0) {
                    $transaction->debit = $data['amount'];
                    $expensename=ExpenseCategory::where('id',  $data['expense_category_id'])->first();
                    $accountType1 = ChartofAccount::where('name',  $expensename->name)->first();
                    $transaction->chartof_accounts_id = $accountType1->id;
                } else {
                    $transaction->credit = $data['amount'];
                    $account_name=Account::where('id',$data['account_id'])->first();
                    $accountType2 = ChartofAccount::where('name', $account_name->name)->first();
                    $transaction->chartof_accounts_id = $accountType2->id;
                }
                $transaction->save();
            }
    
            // Log the rejection
            activity()
            ->performedOn($lims_expense_data)
            ->causedBy(Auth::user())
            ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = Expense::STATUS_APPROVED; // Set the value of the `is_active` column

            })

            ->log('Expense update approved');
            DB::commit();
    
            return redirect('expenses')->with('message', 'Data updated successfully, Please wait for approval');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'An error occurred while updating data. Please try again later.'.$e->getMessage());
        }
    }


    public function reject(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));

            $lims_expense_data = Expense ::where('id', $data['expense_id'])->lockForUpdate()->firstOrFail();
    
            // Check if the lims_expense_data is a draft or approved
            if ($lims_expense_data->isDraft()) {
                throw new Exception('Expense is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
            }

            $logs = Activity::where('subject_type', FixedAsset::class)
            ->where('subject_id', $id)
            ->where('status', Expense::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $oldData = $properties['old'];
            $lims_expense_data->update($oldData);
            $lims_expense_data->status = Expense::STATUS_APPROVED;
            $lims_expense_data->save();
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => Expense::STATUS_APPROVED]);
            // Log the rejection
            activity()
            ->performedOn($lims_expense_data)
            ->causedBy(Auth::user())
            ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = Expense::STATUS_APPROVED; // Set the value of the `is_active` column
            })
            ->log('Expense update Rejected');
            DB::commit();
            return redirect('expenses')->with('message', 'Data updated successfully, Please wait for approval');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'An error occurred while updating data. Please try again later.'.$e->getMessage());
        }
    }
}
