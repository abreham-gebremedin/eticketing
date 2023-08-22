<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\AccountTransactionAdjustment;
use App\ChartofAccount;
use App\FixedAssetCategory;
use App\Journal_Entry;
use App\MonthlyPayroll;
use App\PayrollOne;
use App\User;
use App\Warehouse;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use App\Account;
use App\Employee;
use App\Payroll;
use Auth;
use DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\UserNotification;
use Illuminate\Support\Facades\Mail;


class PayrollController extends Controller
{
    
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('payroll')){
            $lims_account_list = Account::where('is_active', true)->get();
            $lims_employee_list = Employee::where('is_active', true)->get();
            $general_setting = DB::table('general_settings')->latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();

            if(Auth::user()->role_id > 2 && $general_setting->staff_access == 'own')
                $lims_payroll_all = Payroll::orderBy('id', 'desc')->where('user_id', Auth::id())->get();
            else
                $lims_payroll_all = Payroll::orderBy('id', 'desc')->get();

            return view('payroll.index', compact('lims_account_list', 'lims_employee_list', 'lims_payroll_all','lims_warehouse_list'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }
    public function indexone()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('payroll')){
            $lims_account_list = Account::where('is_active', true)->get();
            $lims_employee_list = Employee::where('is_active', true)->get();

            $general_setting = DB::table('general_settings')->latest()->first();
            if(Auth::user()->role_id > 2 && $general_setting->staff_access == 'own')
                $lims_payroll_all = Payroll::orderBy('id', 'desc')->where('user_id', Auth::id())->get();
            else
                $lims_payroll_all = Payroll::orderBy('id', 'desc')->get();



            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';
            return view('payroll.index1', compact('lims_account_list', 'all_permission', 'lims_employee_list', 'lims_payroll_all'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }
    public function create(Request $request)
    {
        //




        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('payroll')) {
          
            $lims_account_list = Account::where('is_active', true)->get();             
            $lims_employee_data = Employee::where('is_active', true);
          
           
             
    
            if (Auth::user()->role_id > 2) {
                $lims_warehouse_list = Warehouse::where([
                    ['is_active', true],
                    ['id', Auth::user()->warehouse_id]
                ])->get();

                $warehouse_id =Auth::user()->warehouse_id;
                $lims_employee_data = $lims_employee_data->where('warehouse_id', Auth::user()->warehouse_id);
    
            }else {

                if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
                else
                $warehouse_id = 0;
                # code...
                if($warehouse_id)
                  $lims_employee_data = $lims_employee_data->where('warehouse_id', $warehouse_id);

                
                $lims_warehouse_list = Warehouse::where('is_active', true)->get();

            }
            $lims_employee_data =$lims_employee_data->get();

 
            return view('payroll.create', compact('lims_warehouse_list', 'lims_account_list','lims_employee_data','warehouse_id'));

        } else{

            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
            
        }
    }


    public function storeone(Request $request)
    {

        try {
            DB::beginTransaction();
        $data = $request->except('document');
        $data['user_id'] = Auth::id();
        if ($data['warehouse_id']==0) {
            # code...
            $data['reference_no'] = 'payroll-for-All branch-for-Month-of-'.\Carbon\Carbon::now()->subDays(15)->format('F'). '-'. date("Ymd") . '-'. date("his");

        }else {
            # code...

            $lims_warehouse= Warehouse::where([
                ['is_active', true],
                ['id', $data['warehouse_id']]
            ])->first();
            $data['reference_no'] = 'payroll-for-'.$lims_warehouse->name.'-for-Month-of-'.\Carbon\Carbon::now()->subDays(15)->format('F'). '-'. date("Ymd") . '-'. date("his");
        }
        $data['created_at'] = date("Y-m-d H:i:s");

        if ($data['warehouse_id']==0){
            $warehouse_id=3;

        }else {
            # code...
            $warehouse_id=$data['warehouse_id'];
        }

        //  dd($data);
        $lims_payroll_data = PayrollOne::create($data);

        $dataad['user_id'] = Auth::id();
        $dataad['created_at'] = $data['created_at'] ;
        $dataad['warehouse_id'] = $warehouse_id;
        $dataad['reference_no'] = $data['reference_no'] ;
        $dataad['reason'] = $data['reference_no'] ;
        $dataad['is_adjustment'] = false ;
        $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);

        
        $transaction = new AccountTransaction;
        $transaction->reference_no = $data['reference_no'] ;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id	 = Auth::id();
        $transaction->warehouse_id = $warehouse_id; 
        $transaction->credit = 0;
        $transaction->debit = $data['grand_total'];
        $accountType = ChartofAccount::where('name', 'Salary Expense')->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->payrollone_id = $lims_payroll_data->id;
        $transaction->save();


        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);


        $account_name=Account::where('id',$data['account_id'])->first();
        $transaction = new AccountTransaction;
        $transaction->reference_no = $data['reference_no'] ;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id	 = Auth::id();
        $transaction->warehouse_id = $warehouse_id; 
        $transaction->debit = 0;
        $transaction->credit =$data['grand_total'];
        $accountType = ChartofAccount::where('name', $account_name->cname)->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->payrollone_id = $lims_payroll_data->id;
        $transaction->save();
        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);


        
        // dd($lims_payroll_data);

        $employee_ids = $data['is_pay'];
        $day = $data['day'];

        $basic_salary = $data['basic_salary'];
         $transport_allowance = $data['transport_allowance'];
        $house_allowance = $data['house_allowance'];
        $income_tax=$data['income_tax'];
        $fuel = $data['fuel'];
        $ot = $data['ot'];
        $deduction = $data['deduction'];
        $position = $data['position'];
        $gross = $data['gross'];
        $total_taxable = $data['total_taxable'];
        $employee_pension = $data['employee_pension'];
        $company_pension = $data['company_pension'];
        $total_pension = $data['total_pension'];
        $net_income = $data['net_income'];
        $total = $data['total'];
        $monthly_payroll = [];
    
     

        foreach ($employee_ids as $i => $eid) {          

            $monthly_payroll['payrollone_id'] = $lims_payroll_data->id ;
            $monthly_payroll['employee_id'] = $eid ;
            $monthly_payroll['day'] = $day[$i];
            $monthly_payroll['basic_salary'] = $basic_salary[$i];
            $monthly_payroll['transport_allowance'] = $transport_allowance[$i];
            $monthly_payroll['house_allowance'] = $house_allowance[$i];
            $monthly_payroll['fuel'] = $fuel[$i];
            $monthly_payroll['ot'] =$ot[$i];
            $monthly_payroll['deduction'] = $deduction[$i];
            $monthly_payroll['position'] = $position[$i];
            $monthly_payroll['gross'] = $gross[$i];
            $monthly_payroll['total_taxable'] = $total_taxable[$i];
            $monthly_payroll['income_tax'] = $income_tax[$i];
            $monthly_payroll['employee_pension'] = $employee_pension[$i];
            $monthly_payroll['company_pension'] = $company_pension[$i];
            $monthly_payroll['total_pension'] = $total_pension[$i];
            $monthly_payroll['net_income'] = $net_income[$i];
            $monthly_payroll['total'] = $total[$i];
            MonthlyPayroll::create($monthly_payroll);
            
        }

            // Log the rejection
            activity()
            ->performedOn($lims_payroll_data)
            ->causedBy(Auth::user())
               ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = FixedAssetCategory::STATUS_APPROVED; // Set the value of the `is_active` column
   
            })
            ->log('Payroll created');


        DB::commit(); 
        return redirect('payroll/indexone')->with('message', 'Payroll created successfully');


    }  catch (Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', $e->getMessage());
    }


    }
    public function payrollData(Request $request)
    {
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $Data = PayrollOne::where('user_id', Auth::id());
            $totalData = $Data->count();
        } else {
            $Data = PayrollOne::query();
            $totalData = $Data->count();
        }
    
        $totalFiltered = $totalData;
    
        if ($request->input('length') != -1) {
            $limit = $request->input('length');
        } else {
            $limit = $totalData;
        }
    
        $payrolls = null;
    
        if (empty($request->input('search.value'))) {
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $payrolls = $Data->where('user_id', Auth::id())
                    ->orderByDesc('created_at')
                    ->skip($request->input('start'))
                    ->take($limit)
                    ->get();
            } else {
                $payrolls = $Data->orderByDesc('created_at')
                    ->skip($request->input('start'))
                    ->take($limit)
                    ->get();
            }
        } else {
            $search = $request->input('search.value');
            $q = PayrollOne::whereDate('payrollone.created_at', '=', date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                ->limit($limit);
    
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $payrolls = $q->select('payrollone.*')
                    ->where('payrollone.user_id', Auth::id())
                    ->orderByDesc('payrollone.created_at')
                    ->skip($request->input('start'))
                    ->take($limit)
                    ->get();
    
                $totalFiltered = $q->where('payrollone.user_id', Auth::id())
                    ->count();
            } else {
                $payrolls = $q->select('payrollone.*')
                    ->orderByDesc('payrollone.created_at')
                    ->skip($request->input('start'))
                    ->take($limit)
                    ->get();
    
                $totalFiltered = $q->orWhere('payrollone.reference_no', 'LIKE', "%{$search}%")
                    ->count();
            }
        }
    
        $data = array();
    
        if (!empty($payrolls)) {
            foreach ($payrolls as $key => $payroll) {
                $account = Account::where('id', $payroll->account_id)->first();
                $user1 = User::where('id', $payroll->user_id)->first();
    
                $nestedData['id'] = $payroll->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($payroll->created_at->toDateString()));
                $nestedData['reference_no'] = $payroll->reference_no;
                $nestedData['account'] = $account->name;
                $nestedData['user'] = $user1->name;
                $nestedData['total_net'] = number_format($payroll->total_net, 2);
                $nestedData['add_total_pension'] = number_format($payroll->add_total_pension, 2);
                $nestedData['total_income_tax'] = number_format($payroll->total_income_tax, 2);
                $nestedData['total_deduction'] = number_format($payroll->total_deduction, 2);
                $nestedData['grand_total'] = number_format($payroll->grand_total, 2);
                $nestedData['options'] = '<div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . trans("file.action") . '
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                        <li>
                            <button type="button" class="btn btn-link view"><i class="fa fa-eye"></i> ' . trans('file.View') . '</button>
                        </li>';
                // if(in_array("returns-edit", $request['all_permission'])) {
                //     $nestedData['options'] .= '<li>
                //         <a href="'.route('return-purchase.edit', $payroll->id).'" class="btn btn-link"><i class="dripicons-document-edit"></i> '.trans('file.edit').'</a>
                //     </li>';
                // }
                if (in_array("returns-delete", $request['all_permission'])) {
                    $nestedData['options'] .= \Form::open(["route" => ["payroll.destroyone", $payroll->id], "method" => "DELETE"]) .
                        '<li>
                            <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> ' . trans("file.delete") . '</button> 
                        </li>' . \Form::close();
                }
    
                $nestedData['return'] = array(
                    '[ "' . date(config('date_format'), strtotime($payroll->created_at->toDateString())) . '"',
                    ' "' . $payroll->reference_no . '"',
                    ' "' . $payroll->id . '"',
                    ' "' . $account->name . '"',
                    ' "' . $user1->name . '"',
                    ' "' . $payroll->total_net . '"',
                    ' "' . $payroll->add_total_pension . '"',
                    ' "' . $payroll->total_income_tax . '"',
                    ' "' . $payroll->total_deduction . '"',
                    ' "' . $payroll->grand_total . '"]'
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
    
    
    public function monthlyPayrollData($id)
    {
        $lims_monthly_payroll_data = MonthlyPayroll::where('payrollone_id', $id)->get();
        foreach ($lims_monthly_payroll_data as $key => $monthly_payroll_data) {
             $e1= Employee::where('id', $monthly_payroll_data->employee_id)->first();
            $product_purchase[0][$key] = $e1->name;
            $product_purchase[1][$key] = $monthly_payroll_data->day;
            $product_purchase[2][$key] = $monthly_payroll_data->basic_salary;
            $product_purchase[3][$key] = $monthly_payroll_data->transport_allowance;
            $product_purchase[4][$key] = $monthly_payroll_data->house_allowance;
            $product_purchase[5][$key] = $monthly_payroll_data->fuel;
            $product_purchase[6][$key] = $monthly_payroll_data->ot;
            $product_purchase[7][$key] = $monthly_payroll_data->deduction;
            $product_purchase[8][$key] = $monthly_payroll_data->position;
            $product_purchase[9][$key] = $monthly_payroll_data->gross;
            $product_purchase[10][$key] = $monthly_payroll_data->total_taxable;
            $product_purchase[11][$key] = $monthly_payroll_data->income_tax;
            $product_purchase[12][$key] = $monthly_payroll_data->employee_pension;
            $product_purchase[13][$key] = $monthly_payroll_data->company_pension;
            $product_purchase[14][$key] = $monthly_payroll_data->total_pension;
            $product_purchase[15][$key] = $monthly_payroll_data->net_income;
            $product_purchase[16][$key] = $monthly_payroll_data->total;
        }
        return $product_purchase;
    }



    public function store(Request $request)
    {

        try {
            DB::beginTransaction();
        $data = $request->all();
        if($data['starting_date']=="" || $data['starting_date']== null || $data['ending_date']=="" || $data['ending_date']== null)
        {
            return redirect("perdime")->with('not_permitted', 'Starting Date and Ending date can not be null');

        }  

        if(new DateTime($data['starting_date'] )> new DateTime($data['ending_date']))
        {
            return redirect("perdime")->with('not_permitted', 'Starting Date Should Be Before Ending Date');

        } 
        if(isset($data['created_at']))
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        else
            $data['created_at'] = date("Y-m-d H:i:s");

        $data['starting_date'] = date("Y-m-d H:i:s", strtotime($data['starting_date']));
        $data['ending_date'] = date("Y-m-d H:i:s", strtotime($data['ending_date']));

        $data['reference_no'] = 'Perdime-' .$data['created_at'] . '-'. date("his");
        $data['user_id'] = Auth::id();
         $data['daily_amount']= $data['amount'];
        $data['amount']=$data['amount']*$data['day'];;
        $lims_perdime_data=Payroll::create($data);
        $message = 'Perdime creared succesfully';
        

         $warehouse_id=$data['warehouse_id'] ;
 
        $dataad['user_id'] = Auth::id();
        $dataad['created_at'] = $data['created_at'] ;
        $dataad['warehouse_id'] = $warehouse_id;
        $dataad['reference_no'] = $data['reference_no'] ;
        $dataad['reason'] = $data['reference_no'] .$data['note'] ;
        $dataad['is_adjustment'] = false ;
        $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);

        
        $transaction = new AccountTransaction;
        $transaction->reference_no = $data['reference_no'] ;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id	 = Auth::id();
        $transaction->warehouse_id = $warehouse_id; 
        $transaction->credit = 0;
        $transaction->debit = $data['amount'];
        $accountType = ChartofAccount::where('name', 'Perdime')->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->perdime_id = $lims_perdime_data->id;

        $transaction->save();

        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);


        $account_name=Account::where('id',$data['account_id'])->first();
        $transaction = new AccountTransaction;
        $transaction->reference_no = $data['reference_no'] ;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id	 = Auth::id();
        $transaction->warehouse_id = $warehouse_id; 
        $transaction->debit = 0;
        $transaction->credit =$data['amount'];
        $accountType = ChartofAccount::where('name', $account_name->cname)->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->perdime_id = $lims_perdime_data->id;
        $transaction->save();


        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);


        //collecting mail data
        $lims_employee_data = Employee::find($data['employee_id']);
        $mail_data['reference_no'] = $data['reference_no'];
        $mail_data['amount'] = $data['amount'];
        $mail_data['name'] = $lims_employee_data->name;
        $mail_data['email'] = $lims_employee_data->email;
        try{
            Mail::send( 'mail.payroll_details', $mail_data, function( $message ) use ($mail_data)
            {
                $message->to( $mail_data['email'] )->subject( 'Perdime Details' );
            });
        }
        catch(Exception $e){
            $message = ' Perdime created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
        }

         // Log the rejection
         activity()
         ->performedOn($lims_perdime_data)
         ->causedBy(Auth::user())
            ->tap(function ($activity) {
             $activity->is_active = false; // Set the value of the `is_active` column
             $activity->status = FixedAssetCategory::STATUS_APPROVED; // Set the value of the `is_active` column

         })
         
         ->log('Perdime created');
        DB::commit(); 
        return redirect('perdime')->with('message', $message);



    } catch (Exception $e) {
        DB::rollBack();
        dd($e->getMessage());
        // return redirect()->back()->with('not_permitted', $e->getMessage());
    }


    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        if($data['starting_date']=="" || $data['starting_date']== null || $data['ending_date']=="" || $data['ending_date']== null)
        {
            return redirect("perdime")->with('not_permitted', 'Starting Date and Ending date can not be null');

        }  

        if(new DateTime($data['starting_date'] )> new DateTime($data['ending_date']))
        {
            return redirect("perdime")->with('not_permitted', 'Starting Date Should Be Before Ending Date');

        } 

        if(isset($data['created_at']))
        $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
    

    $data['starting_date'] = date("Y-m-d H:i:s", strtotime($data['starting_date']));
    $data['ending_date'] = date("Y-m-d H:i:s", strtotime($data['ending_date']));
        $lims_payroll_data = Payroll::find($data['payroll_id']);
        $lims_payroll_data->update($data);
        return redirect('perdime')->with('message', 'Perdime updated succesfully');
    }

    public function deleteBySelection(Request $request)
    {
        $payroll_id = $request['payrollIdArray'];
        foreach ($payroll_id as $id) {
            $lims_payroll_data = Payroll::find($id);
            $lims_payroll_data->delete();
        }
        return 'perdime deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_payroll_data = Payroll::find($id);
        $lims_payroll_data->delete();
        return redirect('perdime')->with('not_permitted', 'Perdime deleted succesfully');
    }



    public function payrolldestroy($id)
    {
        $lims_payroll_data = Payroll::find($id);
        $lims_payroll_data->delete();
        return redirect('payroll')->with('not_permitted', 'Perdime deleted succesfully');
    }
}
