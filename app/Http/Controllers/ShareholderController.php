<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\AccountTransactionAdjustment;
use App\CashRegister;
use App\ChartofAccount;
use App\Journal_Entry;
use App\Payment;
use App\PaymentWithCheque;
use App\PaymentWithCreditCard;
use App\PaymentWithMobile;
use App\PaymentWithPOSATM;
use App\PosSetting;
use App\Shareholder;
use App\Shareholder_Payment;
use App\User;
use App\Warehouse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use NumberToWords\NumberToWords;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use DB;
use Illuminate\Validation\Rule;
use Stripe\Stripe;

class ShareholderController extends Controller
{  
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('shareholders-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';
            $lims_shareholders_all = Shareholder::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();
            $lims_account_list = Account::where('is_active', true)->get();
    
            return view('shareholders.index', compact('lims_shareholders_all', 'all_permission', 'lims_account_list'));
        } else {
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
    }
    

    public function getPayment()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('shareholders-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';
                $lims_shareholders_all = Shareholder_Payment::with('shareholder')
                ->orderBy('created_at', 'desc')
                ->get();
             $lims_account_list = Account::where('is_active', true)->get();
    
            return view('shareholders.payment', compact('lims_shareholders_all', 'all_permission', 'lims_account_list'));
        } else {
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
    }
    
    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('shareholders-add')) {

            return view('shareholders.create');
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function store(Request $request)
    {
        $data = $request->except('image');
        $message = 'shareholder created successfully';

        // Begin transaction
        DB::beginTransaction();

        try {
            // Validation in employee table
            $this->validate($request, [
                'email' => [
                    'max:255',
                    Rule::unique('shareholders')->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'image' => 'image|mimes:jpg,jpeg,png,gif|max:130000',
            ]);

            $image = $request->image;
            if ($image) {
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $request['email']);
                $imageName = $imageName . '.' . $ext;
                $image->move('public/images/shareholder', $imageName);
                $data['image'] = $imageName;
            }

            $data['name'] = $data['shareholder_name'];
            $data['is_active'] = true;
            Shareholder::create($data);

            // Commit the transaction
            DB::commit();

            return redirect('shareholders')->with('message', $message);
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollBack();
            return redirect('shareholders')->with('not_permitted', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $lims_employee_data = Shareholder::find($request['employee_id']);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Validation in employee table
            $this->validate($request, [
                'email' => [
                    'email',
                    'max:255',
                    Rule::unique('Shareholders')->ignore($lims_employee_data->id)->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);

            $data = $request->except('image');
            $image = $request->image;
            if ($image) {
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $request['email']);
                $imageName = $imageName . '.' . $ext;
                $image->move('public/images/employee', $imageName);
                $data['image'] = $imageName;
            }

            $lims_employee_data->update($data);

            // Commit the transaction
            DB::commit();

            return redirect('shareholders')->with('message', 'Shareholder updated successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollBack();
            return redirect('shareholders')->with('not_permitted', $e->getMessage());

             
        }
    }
    public function addregistrationfee(Request $request)
    {
        DB::beginTransaction();
    
        try {
        $data = $request->all();
        if (!$data['amount'])
            $data['amount'] = 0.00;
        // Check if the amount is a 0
        if ($data['amount']==0) {
            throw new Exception('Zero value can not be added to the transaction!!
            </br>  Contact Your support staff');
        }
        $lims_addregistrationfee_data = new Shareholder_Payment();
        $lims_addregistrationfee_data->reason="Registration Fee";
        $lims_addregistrationfee_data->pt="+";

        $lims_addregistrationfee_data->amount=$data['amount'];
        $lims_addregistrationfee_data->share_holder_id=$data['share_holder_id'];  
        $lims_addregistrationfee_data->user_id = Auth::id(); 
        $lims_addregistrationfee_data->save();
        
        if ($data['paid_by_id'] == 1)
            $paying_method = 'Cash';
        $cash_register_data = CashRegister::where([
            ['user_id', Auth::id()],
            ['warehouse_id', 3],
            ['status', true]
        ])->first();

        $lims_payment_data = new Payment();
        $lims_payment_data->user_id = Auth::id();
        $lims_payment_data->share_holder_payment_id = $lims_addregistrationfee_data->id;
        if ($cash_register_data)
            $lims_payment_data->cash_register_id = $cash_register_data->id;
        $lims_payment_data->account_id = $data['account_id'];
        $data['payment_reference'] = 'registrationfee-' . date("Ymd") . '-' . date("his");
        $lims_payment_data->payment_reference = $data['payment_reference'];
        $lims_payment_data->amount = $data['amount'];
        $lims_payment_data->change = 0;
        $lims_payment_data->paying_method = $paying_method;
        $lims_payment_data->payment_note = $data['payment_note'];
        $lims_payment_data->save();
 

        $dataad['user_id'] = Auth::id();
        $dataad['created_at'] = $lims_payment_data->created_at ;
        $dataad['warehouse_id'] =3;
        $dataad['reference_no'] = $lims_payment_data->payment_reference ;
        $dataad['reason'] = $lims_payment_data->payment_reference ;
        $dataad['is_adjustment'] = false ;
        $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);
        
        # code...
        $transaction = new AccountTransaction;
        $transaction->reference_no = $lims_payment_data->payment_reference;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id = Auth::id();
        $transaction->warehouse_id =3;
        $transaction->debit = 0;
        $transaction->credit = $data['amount'];
        $accountType = ChartofAccount::where('name', 'Other Income', '')->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->save();
        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);
        $account_name=Account::where('id',$data['account_id'])->first();
        $transaction = new AccountTransaction;
        $transaction->reference_no = $lims_payment_data->payment_reference;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id = Auth::id();
        $transaction->warehouse_id =3;
        $transaction->debit = $data['amount'];
        $transaction->credit = 0;
        $accountType = ChartofAccount::where('name', $account_name->cname)->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->save();

        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);
        

  


        
        DB::commit();

        $message = 'Payment created successfully';

        // Rest of the code...

         return redirect('shareholders/gen_invoice/' . $lims_addregistrationfee_data->id)->with('message', $message);

    } catch (Exception $e) {
        DB::rollback();
        // Handle the exception or display an error message
        return redirect('shareholders')->with('not_permitted', 'An error occurred while creating the payment.'.$e->getMessage());
    }
    }




    public function addshare(Request $request)
    {
        DB::beginTransaction();
    
        try {
        $data = $request->all();
        if (!$data['amount'])
            $data['amount'] = 0.00;
        $lims_share_holder_data = Shareholder::find($data['share_holder_id']);

        $lims_share_holder_data->share+= $data['amount'];
        $lims_share_holder_data->save();
        $lims_addshare_data = new Shareholder_Payment();
        $lims_addshare_data->reason="Buy new share";
        $lims_addshare_data->pt="+";
        $lims_addshare_data->amount=$data['amount'];
        $lims_addshare_data->share_holder_id=$data['share_holder_id'];  
        $lims_addshare_data->user_id = Auth::id(); 
        $lims_addshare_data->save();
        
        if ($data['paid_by_id'] == 1)
            $paying_method = 'Cash';
        $cash_register_data = CashRegister::where([
            ['user_id', Auth::id()],
            ['warehouse_id', 3],
            ['status', true]
        ])->first();

        $lims_payment_data = new Payment();
        $lims_payment_data->user_id = Auth::id();
        $lims_payment_data->share_holder_payment_id = $lims_addshare_data->id;
        if ($cash_register_data)
            $lims_payment_data->cash_register_id = $cash_register_data->id;
        $lims_payment_data->account_id = $data['account_id'];
        $data['payment_reference'] = 'registrationfee-' . date("Ymd") . '-' . date("his");
        $lims_payment_data->payment_reference = $data['payment_reference'];
        $lims_payment_data->amount = $data['amount'];
        $lims_payment_data->change = 0;
        $lims_payment_data->paying_method = $paying_method;
        $lims_payment_data->payment_note = $data['payment_note'];
        $lims_payment_data->save();
 

        $dataad['user_id'] = Auth::id();
        $dataad['created_at'] = $lims_payment_data->created_at ;
        $dataad['warehouse_id'] =3;
        $dataad['reference_no'] = $lims_payment_data->payment_reference ;
        $dataad['reason'] = $lims_payment_data->payment_reference ;
        $dataad['is_adjustment'] = false ;
        $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);
        
        # code...
        $transaction = new AccountTransaction;
        $transaction->reference_no = $lims_payment_data->payment_reference;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id = Auth::id();
        $transaction->warehouse_id =3;
        $transaction->debit = 0;
        $transaction->credit = $data['amount'];
        $accountType = ChartofAccount::where('name', 'Share', '')->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->save();
        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);
        $account_name=Account::where('id',$data['account_id'])->first();
        $transaction = new AccountTransaction;
        $transaction->reference_no = $lims_payment_data->payment_reference;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id = Auth::id();
        $transaction->warehouse_id =3;
        $transaction->debit = $data['amount'];
        $transaction->credit = 0;
        $accountType = ChartofAccount::where('name', $account_name->cname)->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->save();

        $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
        $journal_entry['chartof_accounts_id'] = $accountType->id;
        $journal_entry['account_transaction_id'] = $transaction->id;
        Journal_Entry::create($journal_entry);
        
        DB::commit();

        $message = 'Payment created successfully';

        // Rest of the code...

         return redirect('shareholders/gen_invoice/' . $lims_addshare_data->id)->with('message', $message);

    } catch (Exception $e) {
        DB::rollback();
        // Handle the exception or display an error message
        return redirect('shareholders')->with('not_permitted', 'An error occurred while creating the payment.'.$e->getMessage());
    }
    }

    public function withdrawshare(Request $request)
    {
            try 
            {
        DB::beginTransaction();
        $data = $request->all();
        $lims_share_holder_data = Shareholder::find($data['share_holder_id']);

        $lims_share_holder_data->share-= $data['amount'];
        $lims_share_holder_data->save();
        $lims_Withdraw_data = new Shareholder_Payment();
        $lims_Withdraw_data->reason="share Withdraw";
        $lims_Withdraw_data->pt="-";
        $lims_Withdraw_data->amount=$data['amount'];
        $lims_Withdraw_data->share_holder_id=$data['share_holder_id'];  
        $lims_Withdraw_data->user_id = Auth::id(); 
        $lims_Withdraw_data->save();
    
           if($data['paid_by_id'] == 1)
               $paying_method = 'Cash';
           elseif ($data['paid_by_id'] == 2)
               $paying_method = 'Gift Card';
           elseif ($data['paid_by_id'] == 3)
               $paying_method = 'Credit Card';
           elseif ($data['paid_by_id'] == 4)
               $paying_method = 'Cheque';
           elseif ($data['paid_by_id'] == 5)
               $paying_method = 'Paypal';
           elseif ($data['paid_by_id'] == 11)
               $paying_method = 'Mobile';
           elseif ($data['paid_by_id'] == 12)
               $paying_method = 'POS ATM';
           else
               $paying_method = 'Cheque';
   
           $lims_payment_data = new Payment();
           $lims_payment_data->user_id = Auth::id();
           $lims_payment_data->share_holder_payment_id = $lims_Withdraw_data->id;
           $lims_payment_data->account_id = $data['account_id'];
           $lims_payment_data->payment_reference = 'shareWithdraw-' . date("Ymd") . '-'. date("his");
           $lims_payment_data->amount = $data['amount'];
           $lims_payment_data->change = 0;
           $lims_payment_data->paying_method = $paying_method;
           $lims_payment_data->payment_note = $data['payment_note'];
           $lims_payment_data->save();
           $lims_payment_data = Payment::latest()->first();
           $data['payment_id'] = $lims_payment_data->id;
   
           $dataad['user_id'] = Auth::id();
           $dataad['created_at'] = $lims_payment_data->created_at ;
           $dataad['warehouse_id'] = 3 ;
           $dataad['reference_no'] = $lims_payment_data->payment_reference;
           $dataad['reason'] = $lims_payment_data->payment_reference ;
           $dataad['is_adjustment'] = false ;
           $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);
   
           $transaction = new AccountTransaction;
           $transaction->reference_no = $lims_payment_data->payment_reference;
           $transaction->date = date("Y-m-d H:i:s");
           $transaction->user_id	 = Auth::id();
           $transaction->warehouse_id = 3; 
           $transaction->debit = $data['amount'];
           $transaction->credit = 0;
           $accountType = ChartofAccount::where('name',"Share")->first();
           $transaction->chartof_accounts_id = $accountType->id;
           $transaction->payment_id = $lims_payment_data->id;
           $transaction->save();
   
           $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
           $journal_entry['chartof_accounts_id'] = $accountType->id;
           $journal_entry['account_transaction_id'] = $transaction->id;
           Journal_Entry::create($journal_entry);
   
           $transaction = new AccountTransaction;
           $account_name=Account::where('id',$data['account_id'])->first();
           $transaction->reference_no = $lims_payment_data->payment_reference ;
           $transaction->date = date("Y-m-d H:i:s");
           $transaction->user_id	 = Auth::id();
           $transaction->warehouse_id = 3; 
           $transaction->debit = 0;
           $transaction->credit = $data['amount'];;
           $accountType = ChartofAccount::where('name', $account_name->cname)->first();
           $transaction->chartof_accounts_id = $accountType->id;
           $transaction->payment_id = $lims_payment_data->id;
           $transaction->save();
           
           $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
           $journal_entry['chartof_accounts_id'] = $accountType->id;
           $journal_entry['account_transaction_id'] = $transaction->id;
           Journal_Entry::create($journal_entry);
    
           $lims_account_data = Account::where('id', $data['account_id'])->first();
   
           if($paying_method == 'Credit Card'){
               $lims_pos_setting_data = PosSetting::latest()->first();
               Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
               $token = $data['stripeToken'];
               $amount = $data['amount'];
   
               // Charge the Customer
               $charge = \Stripe\Charge::create([
                   'amount' => $amount * 100,
                   'currency' => 'usd',
                   'source' => $token,
               ]);
   
               $data['charge_id'] = $charge->id;
               PaymentWithCreditCard::create($data);
           }
           elseif ($paying_method == 'Cheque') {
            $data['cheque_bank']=$accountType->name;
               PaymentWithCheque::create($data);
           }elseif ($paying_method == 'Mobile') {
               $data['mobile_bank']=$lims_account_data->name;
               PaymentWithMobile::create($data);
           }elseif ($paying_method == 'POS ATM') {
               $data['pos_bank']=$lims_account_data->name;
               PaymentWithPOSATM::create($data);
           }
   
                       // Log the rejection
                       activity()
                       ->performedOn($lims_payment_data)
                       ->causedBy(Auth::user())
                          ->tap(function ($activity) {
                           $activity->is_active = false; // Set the value of the `is_active` column
                           $activity->status = Payment::STATUS_APPROVED; // Set the value of the `is_active` column
           
                       })
                       
                       ->log('New Payment update Inserted');
                   
           
           DB::commit(); 
           return redirect('shareholders')->with('message', 'Payment created successfully');
    
       } catch (ModelNotFoundException $e) {
          DB::rollBack();
          return redirect()->back()->with('not_permitted', 'Payment not found');
      }  catch (Exception $e) {
          DB::rollBack();
          return redirect()->back()->with('not_permitted', $e->getMessage());
      }
       }


       public function withdrawdividend(Request $request)
       {
               try 
               {
           DB::beginTransaction();
           $data = $request->all();
           $lims_share_holder_data = Shareholder::find($data['share_holder_id']);
   
           $lims_share_holder_data->dividend-= $data['amount'];
           $lims_share_holder_data->save();
           $lims_Withdraw_data = new Shareholder_Payment();
           $lims_Withdraw_data->reason="dividend Withdraw";
           $lims_Withdraw_data->pt="-";
           $lims_Withdraw_data->amount=$data['amount'];
           $lims_Withdraw_data->share_holder_id=$data['share_holder_id'];  
           $lims_Withdraw_data->user_id = Auth::id(); 
           $lims_Withdraw_data->save();
       
              if($data['dividend_paid_by_id'] == 1)
                  $paying_method = 'Cash';
              elseif ($data['dividend_paid_by_id'] == 2)
                  $paying_method = 'Gift Card';
              elseif ($data['dividend_paid_by_id'] == 3)
                  $paying_method = 'Credit Card';
              elseif ($data['dividend_paid_by_id'] == 4)
                  $paying_method = 'Cheque';
              elseif ($data['dividend_paid_by_id'] == 5)
                  $paying_method = 'Paypal';
              elseif ($data['dividend_paid_by_id'] == 11)
                  $paying_method = 'Mobile';
              elseif ($data['dividend_paid_by_id'] == 12)
                  $paying_method = 'POS ATM';
              else
                  $paying_method = 'Cheque';
      
              $lims_payment_data = new Payment();
              $lims_payment_data->user_id = Auth::id();
              $lims_payment_data->share_holder_payment_id = $lims_Withdraw_data->id;
              $lims_payment_data->account_id = $data['account_id'];
              $lims_payment_data->payment_reference = 'dividendWithdraw-' . date("Ymd") . '-'. date("his");
              $lims_payment_data->amount = $data['amount'];
              $lims_payment_data->change = 0;
              $lims_payment_data->paying_method = $paying_method;
              $lims_payment_data->payment_note = $data['payment_note'];
              $lims_payment_data->save();
              $lims_payment_data = Payment::latest()->first();
              $data['payment_id'] = $lims_payment_data->id;
      
              $dataad['user_id'] = Auth::id();
              $dataad['created_at'] = $lims_payment_data->created_at ;
              $dataad['warehouse_id'] = 3 ;
              $dataad['reference_no'] = $lims_payment_data->payment_reference;
              $dataad['reason'] = $lims_payment_data->payment_reference ;
              $dataad['is_adjustment'] = false ;
              $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);
      
              $transaction = new AccountTransaction;
              $transaction->reference_no = $lims_payment_data->payment_reference;
              $transaction->date = date("Y-m-d H:i:s");
              $transaction->user_id	 = Auth::id();
              $transaction->warehouse_id = 3; 
              $transaction->debit = 0;
              $transaction->credit = $data['amount'];
              $account_name=Account::where('id',$data['account_id'])->first();
              $accountType = ChartofAccount::where('name', $account_name->cname)->first();
              $transaction->chartof_accounts_id = $accountType->id;
              $transaction->payment_id = $lims_payment_data->id;
              $transaction->save();
      
              $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
              $journal_entry['chartof_accounts_id'] = $accountType->id;
              $journal_entry['account_transaction_id'] = $transaction->id;
              Journal_Entry::create($journal_entry);
      
              $transaction = new AccountTransaction;
              $transaction->reference_no = $lims_payment_data->payment_reference ;
              $transaction->date = date("Y-m-d H:i:s");
              $transaction->user_id	 = Auth::id();
              $transaction->warehouse_id = 3; 
              $transaction->debit = $data['amount'];
              $transaction->credit = 0;
              $accountType = ChartofAccount::where('name', 'Dividend Payble')->first();
              $transaction->chartof_accounts_id = $accountType->id;
              $transaction->payment_id = $lims_payment_data->id;
              $transaction->save();
              
              $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
              $journal_entry['chartof_accounts_id'] = $accountType->id;
              $journal_entry['account_transaction_id'] = $transaction->id;
              Journal_Entry::create($journal_entry);
       
              $lims_account_data = Account::where('id', $data['account_id'])->first();
      
              if($paying_method == 'Credit Card'){
                  $lims_pos_setting_data = PosSetting::latest()->first();
                  Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                  $token = $data['stripeToken'];
                  $amount = $data['amount'];
      
                  // Charge the Customer
                  $charge = \Stripe\Charge::create([
                      'amount' => $amount * 100,
                      'currency' => 'usd',
                      'source' => $token,
                  ]);
      
                  $data['charge_id'] = $charge->id;
                  PaymentWithCreditCard::create($data);
              }
              elseif ($paying_method == 'Cheque') {
               $data['cheque_bank']=$accountType->name;
                  PaymentWithCheque::create($data);
              }elseif ($paying_method == 'Mobile') {
                  $data['mobile_bank']=$lims_account_data->name;
                  PaymentWithMobile::create($data);
              }elseif ($paying_method == 'POS ATM') {
                  $data['pos_bank']=$lims_account_data->name;
                  PaymentWithPOSATM::create($data);
              }
      
                          // Log the rejection
                          activity()
                          ->performedOn($lims_payment_data)
                          ->causedBy(Auth::user())
                             ->tap(function ($activity) {
                              $activity->is_active = false; // Set the value of the `is_active` column
                              $activity->status = Payment::STATUS_APPROVED; // Set the value of the `is_active` column
              
                          })
                          
                          ->log('New Payment update Inserted');
                      
              
              DB::commit(); 
              return redirect('shareholders')->with('message', 'Withdrawed successfully');
       
          } catch (ModelNotFoundException $e) {
             DB::rollBack();
             return redirect()->back()->with('not_permitted', 'Payment not found');
         }  catch (Exception $e) {
             DB::rollBack();
             return redirect()->back()->with('not_permitted', $e->getMessage());
         }
          }
   



    public function genInvoice($id)
    {
        $lims_shareholder_Payment_data = Shareholder_Payment::find($id);
        $lims_warehouse_data = Warehouse::find(3);
        $lims_biller_data=User::find($lims_shareholder_Payment_data->user_id);
        $lims_customer_data = Shareholder::find($lims_shareholder_Payment_data->share_holder_id);
        $lims_payment_data = Payment::where('share_holder_payment_id', $id)->first();

        $numberToWords = new NumberToWords();
        if (\App::getLocale() == 'ar' || \App::getLocale() == 'hi' || \App::getLocale() == 'vi' || \App::getLocale() == 'en-gb')
            $numberTransformer = $numberToWords->getNumberTransformer('en');
        else
            $numberTransformer = $numberToWords->getNumberTransformer(\App::getLocale());
        $numberInWords = $numberTransformer->toWords($lims_shareholder_Payment_data->grand_total);

        return view('shareholders.invoiceforregistrationfee', compact('lims_shareholder_Payment_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_payment_data','lims_customer_data', 'numberInWords'));
    }


    public function genDetail($id)
    {
        $lims_shareholder_Payment_data = Shareholder_Payment::find($id);
        $lims_warehouse_data = Warehouse::find(3);
        $lims_biller_data=User::find($lims_shareholder_Payment_data->user_id);
        $lims_customer_data = Shareholder::find($lims_shareholder_Payment_data->share_holder_id);
        $lims_payment_data = Payment::where('share_holder_payment_id', $id)->first();

        $numberToWords = new NumberToWords();
        if (\App::getLocale() == 'ar' || \App::getLocale() == 'hi' || \App::getLocale() == 'vi' || \App::getLocale() == 'en-gb')
            $numberTransformer = $numberToWords->getNumberTransformer('en');
        else
            $numberTransformer = $numberToWords->getNumberTransformer(\App::getLocale());
        $numberInWords = $numberTransformer->toWords($lims_shareholder_Payment_data->grand_total);

        return view('shareholders.detail', compact('lims_shareholder_Payment_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_payment_data','lims_customer_data', 'numberInWords'));
    }
    
    public function deleteBySelection(Request $request)
    {
        $shareholder_id = $request['employeeIdArray'];

        // Begin transaction
        DB::beginTransaction();

        try {
            foreach ($shareholder_id as $id) {
                $lims_employee_data = Shareholder::find($id);

                $lims_employee_data->is_active = false;
                $lims_employee_data->save();
            }

            // Commit the transaction
            DB::commit();

            return 'Shareholder deleted successfully!';
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollBack();
            return redirect('shareholders')->with('not_permitted', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $lims_employee_data = Shareholder::find($id);

        // Begin transaction
        DB::beginTransaction();

        try {
            $lims_employee_data->is_active = false;
            $lims_employee_data->save();

            // Commit the transaction
            DB::commit();

            return redirect('shareholders')->with('not_permitted', 'Shareholder deleted successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollBack();
            return redirect('shareholders')->with('not_permitted', $e->getMessage());
        }
    }
}
