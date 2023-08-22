<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\ChartofAccountCategory;
use App\PrePaidRent;
use Auth;
use Doctrine\DBAL\Statement;
use Illuminate\Http\Request;
use App\Warehouse;
use App\User;
use App\ChartofAccount;
use Illuminate\Support\Facades\DB;

use Andegna\DateTime as AndegnaDateTime;

 use App\FixedAsset;
use App\FixedAssetCategory;
use App\GeneralSetting;
use DateInterval;
use DateTime;
use Exception;

class AccountTransactionController extends Controller
{


    public function journalEntries(Request $request)
    {
        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();



        return view('accounttransaction.journal_entry', compact('start_date', 'end_date', 'warehouse_id', 'lims_warehouse_list'));
    }

    public function journalEntriesData(Request $request)
    {

        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];


        
        $totalData = AccountTransaction::whereBetween('date', [$start_date, $end_date])->count();
        $totalFiltered = $totalData;

        if ($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        //return $request;
        $start = $request->input('start');
         if ($request->input('search.value')) {
            $search = $request->input('search.value');
            $totalData = AccountTransaction::whereBetween('date', [$start_date, $end_date])
                ->where('date', 'LIKE', "%{$search}%")
                ->count();
            $lims_transaction_all = AccountTransaction::whereBetween('date', [$start_date, $end_date])
                ->where('date', 'LIKE', "%{$search}%")
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();



        } else {
            $lims_transaction_all = $transactions = AccountTransaction::whereBetween('date', [$start_date, $end_date])->get();

        }


        $data = [];
        foreach ($lims_transaction_all as $transaction) {
            if ($warehouse_id == 0) {
                $nestedData['key'] = count($data);
                $nestedData['date'] = $transaction->date;
                $nestedData['user'] = User::join('account_transactions', 'users.id', '=', 'account_transactions.user_id')
                    ->where('account_transactions.user_id', $transaction->user_id)
                    ->select('users.name')
                    ->first()->name;
                $nestedData['store'] = Warehouse::join('account_transactions', 'warehouses.id', '=', 'account_transactions.warehouse_id')
                    ->where('account_transactions.warehouse_id', $transaction->warehouse_id)
                    ->select('warehouses.name')
                    ->first()->name;
                $nestedData['reference_no'] = $transaction->reference_no;
                $nestedData['account'] = ChartofAccount::join('account_transactions', 'chartof_accounts.id', '=', 'account_transactions.chartof_accounts_id')
                    ->where('account_transactions.chartof_accounts_id', $transaction->chartof_accounts_id)
                    ->select('chartof_accounts.name')
                    ->first()->name;
                if ($transaction->debit == 0) {
                    # code...
                    $nestedData['debit'] = "";

                } else {

                    $nestedData['debit'] = $transaction->debit;
                }


                if ($transaction->credit == 0) {
                    # code...
                    $nestedData['credit'] = "";

                } else {

                    $nestedData['credit'] = $transaction->credit;
                }
                $data[] = $nestedData;
            } else {
                if ($warehouse_id != $transaction->warehouse_id) {
                    # code...
                    continue;
                }

                $nestedData['key'] = count($data);
                $nestedData['date'] = $transaction->date;
                $nestedData['user'] = User::join('account_transactions', 'users.id', '=', 'account_transactions.user_id')
                    ->where('account_transactions.user_id', $transaction->user_id)
                    ->select('users.name')
                    ->first()->name;
                $nestedData['store'] = Warehouse::join('account_transactions', 'warehouses.id', '=', 'account_transactions.warehouse_id')
                    ->where('account_transactions.warehouse_id', $transaction->warehouse_id)
                    ->select('warehouses.name')
                    ->first()->name;
                $nestedData['reference_no'] = $transaction->reference_no;
                $nestedData['account'] = ChartofAccount::join('account_transactions', 'chartof_accounts.id', '=', 'account_transactions.chartof_accounts_id')
                    ->where('account_transactions.chartof_accounts_id', $transaction->chartof_accounts_id)
                    ->select('chartof_accounts.name')
                    ->first()->name;
                $nestedData['debit'] = $transaction->debit;
                $nestedData['credit'] = $transaction->credit;
                $data[] = $nestedData;

            }
        }
        // $totalData = 131;
        // $totalFiltered = 10;
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        );

        echo json_encode($json_data);
    }

    public function generalLedger(Request $request)
    {
        $data = $request->all();
        $end_date = $data['end_date'];
        $generalseting=GeneralSetting::find(1);
        $start_date = $generalseting->fiscal_year;


        // dd( $end_date);

        
        $warehouse_id = $data['warehouse_id'];

        if ($warehouse_id==0) {
            # code...
            $warehouse_name = "All";
        } else {
            # code...
            $warehouse=Warehouse::find($warehouse_id);
            $warehouse_name = $warehouse->name;

        }
        $lims_warehouse_list = Warehouse::select('name', 'id')->where('is_active', true)->get();
 


 
        return view('accounttransaction.general_ledger', compact('start_date', 'end_date', 'warehouse_id', 'lims_warehouse_list','warehouse_name'));
    }

    public function generalLedgerData(Request $request)
{
    $this->storeacumulatedDeperciation();
    $this->storeacumulatedRent();

        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $warehouse_id = intVal($data['warehouse_id']);
        // dd($warehouse_id+5);


        $columns = array(
            1 => 'date',
        );
        
         $q = AccountTransaction::whereDate('created_at', '>=' , $start_date)
                     ->whereDate('created_at', '<=' ,$end_date);
        if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('user_id', Auth::id());
        if($warehouse_id>0)
            $q = $q->where('warehouse_id', $warehouse_id);
        
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'account_transactions.created_at';
        $dir = "desc";
        if(empty($request->input('search.value'))) {
            $q = AccountTransaction::with('warehouse', 'chartofAccounts')
                ->whereDate('created_at', '>=' , $start_date)
                ->whereDate('created_at', '<=' ,$end_date)
                ->offset($start)
                 ->limit($limit)
                ->orderBy($order, $dir);
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
                $q = $q->where('user_id', Auth::id());
            
            
            
            
            
        if($warehouse_id>0)
        $q = $q->where('warehouse_id', $warehouse_id);


 
            $lims_transaction_all = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = AccountTransaction::whereDate('account_transactions.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                 ->limit($limit)
                ->orderBy($order,$dir);
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $lims_transaction_all =  $q->select('account_transactions.*')
                                ->with('warehouse', 'chartofAccounts')
                                ->where('account_transactions.user_id', Auth::id())
                                ->orwhere([
                                    ['reference_no', 'LIKE', "%{$search}%"],
                                    ['user_id', Auth::id()]
                                ])
                                ->get();
                $totalFiltered = $q->where('account_transactions.user_id', Auth::id())->count();
            }
            else {
                $lims_transaction_all =  $q->select('account_transactions.*')
                                ->with('warehouse', 'chartofAccounts')
                                ->orwhere('reference_no', 'LIKE', "%{$search}%")
                                ->get();

                $totalFiltered = $q->orwhere('account_transactions.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }

        $data = array();
        if(!empty($lims_transaction_all))
        {
        foreach ($lims_transaction_all as $transaction) {
                 $nestedData['key'] = count($data);
                $nestedData['date'] = $transaction->date; 
                $nestedData['warehouse']=$transaction->warehouse->name;
                $nestedData['reference_no'] = $transaction->reference_no;
                $nestedData['account'] = $transaction->chartofAccounts->name;
                if ($transaction->debit == 0) {
                    # code...
                    $nestedData['debit'] = "";

                } else {

                    $nestedData['debit'] = $transaction->debit;
                }


                if ($transaction->credit == 0) {
                    # code...
                    $nestedData['credit'] = "";

                } else {

                    $nestedData['credit'] = $transaction->credit;
                }
                $data[] = $nestedData;
              
        }
    }
        // $totalData = 131;
        // $totalFiltered = 10;
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        );

        echo json_encode($json_data);
    }


    public function financialStatement1(Request $request){
    

    }
    public function financialStatement(Request $request)
    {
        $data = $request->all();
        $generalseting=GeneralSetting::find(1);
        $start_date = $generalseting->fiscal_year;
        $end_date = $data['end_date'];
        $warehouse_id = 0;
        $warehouse_name = "All";
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
     
 

        return view('accounttransaction.financialstatement', compact('start_date', 'end_date', 'warehouse_id', 'lims_warehouse_list','warehouse_name'));
    }

    public function Income_Statement(Request $request)
    {
        $data = $request->all();
        $generalseting=GeneralSetting::find(1);
        $start_date = $generalseting->fiscal_year;
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];

        if ($warehouse_id==0) {
            # code...
            $warehouse_name = "All";
        } else {
            # code...
            $warehouse=Warehouse::find($warehouse_id);
            $warehouse_name = $warehouse->name;

        }
        
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        

 

        return view('accounttransaction.incomestatement', compact('start_date', 'end_date', 'warehouse_id', 'lims_warehouse_list','warehouse_name'));
    }


    public function Balance_Sheet(Request $request)
    {
        $data = $request->all();
        $generalseting=GeneralSetting::find(1);
        $start_date = $generalseting->fiscal_year;
        $end_date = $data['end_date'];
        $warehouse_id =0;

        // dd($warehouse_id);
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
       
        if ($warehouse_id==0) {
            # code...
            $warehouse_name = "All";
        } else {
            # code...
            $warehouse=Warehouse::find($warehouse_id);
            $warehouse_name = $warehouse->name;

        }

 

        return view('accounttransaction.balancesheet', compact('start_date', 'end_date', 'warehouse_id', 'lims_warehouse_list','warehouse_name'));
    }

    

    public function Close_Account(Request $request)
    {
        $data = $request->all();
        $generalseting=GeneralSetting::find(1);
        $start_date = $generalseting->fiscal_year;
        $end_date = $data['end_date'];
         # code...
        // Define the date range for the balance sheet
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $bigData= array();
        foreach ($lims_warehouse_list as $key => $warehouse) {
            # code...
            $bigData[]= $this->balanceddata($start_date,$end_date,$warehouse->id);

        }
 
   foreach ($bigData as $key => $data) {
    # code...

    foreach ($data as $key => $closedata) {
        # code...
        echo ($closedata['account'] ." ".$closedata['Debit'] ." ".$closedata['Credit'] ."</br>");
        if ($closedata['Debit']!=="") {
            # code...
            echo "Debit".$closedata['warehouse_id'] ."</br>";


            
        }

        if ($closedata['Credit']!=="") {
            # code...
            echo "Credit".$closedata['warehouse_id'] ."</br>";
        }
     }
   }


 

     }

     public function balanceddata($start_date, $end_date, $warehouse_id)
     {
 
                // Get the account balances for each account at the end of the date range
        $account_transactions = DB::table('account_transactions')
        ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
        ->whereBetween('account_transactions.date', [$start_date, $end_date])
        ->where('account_transactions.warehouse_id', $warehouse_id)
        ->select('chartof_accounts.name','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
        ->groupBy('chartof_accounts.id','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
        ->orderBy('chartof_accounts.name')
        ->get();
       


        # code...
         
        $account_transactions_count=$account_transactions->count();
        // Categorize the accounts into current and non-current assets, current and non-current liabilities, and equity
        $current_assets = [];
        $non_current_assets = [];
        $liabilities = [];
        $equity = [];
        $data = [];
                
        if ($account_transactions_count > 0) {
            # code...
            $checkBegInv=0;
            foreach ($account_transactions as $account_transaction) {
                if ($account_transaction->is_current_asset ==1) {
                    $current_assets[] = $account_transaction;
                } 

                $a= "Beginning Inventory"==$account_transaction->name;
                if ($a) {
                    # code...
                    $checkBegInv=1;

                }
            }

            foreach ($account_transactions as $account_transaction) {
                if ($account_transaction->is_current_asset ==0 && $account_transaction->is_current_asset !==null) {
                    $non_current_assets[] = $account_transaction;
                } 
            }

            foreach ($account_transactions as $account_transaction) {
                if ($account_transaction->chartof_account_categories_id ==2) {
                    $liabilities[] = $account_transaction;
                } 
            }


            foreach ($account_transactions as $account_transaction) {
                if ($account_transaction->chartof_account_categories_id ==3) {
                    $equity[] = $account_transaction;
                } 
            }
             // Calculate the total amount for each category
            $current_assets_total = collect($current_assets)->sum(function ($account_transaction) {
                return $account_transaction->debit - $account_transaction->credit;
            });
    
            $non_current_assets_total = collect($non_current_assets)->sum(function ($account_transaction) {
                return $account_transaction->debit - $account_transaction->credit;
            });
    
            $liabilities_total = collect($liabilities)->sum(function ($account_transaction) {
                return $account_transaction->credit - $account_transaction->debit;
            });
    
           
    
            $equity_total = collect($equity)->sum(function ($account_transaction) {
                return $account_transaction->credit - $account_transaction->debit;
            });
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "<h2>Asset</h2>";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";
            $nestedData['warehouse_id'] = $warehouse_id;


            $data[] = $nestedData;
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4> Current Assets</h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";
            $nestedData['warehouse_id'] = $warehouse_id;



            $data[] = $nestedData;

            if ($checkBegInv==0) {
                # code...
                $nestedData['key'] = count($data);
                $nestedData['Account_category'] = "";
                $nestedData['Account_category_type'] = "";
                $nestedData['account'] = "Beginning Inventory";
                $nestedData['Debit'] = 0;
                $nestedData['Credit'] = "";
                $nestedData['total'] = "";
                $nestedData['warehouse_id'] = $warehouse_id;


                $data[] = $nestedData;
            }
            foreach ($current_assets as $account_transaction) {
 
                
                    $nestedData['key'] = count($data);
                    $nestedData['Account_category'] = "";
                    $nestedData['Account_category_type'] = "";
                    $nestedData['account'] = $account_transaction->name;
                    $nestedData['Debit'] = $account_transaction->debit - $account_transaction->credit;
                    $nestedData['Credit'] = "";
                    $nestedData['total'] = "";
                    $nestedData['warehouse_id'] = $warehouse_id;


                    $data[] = $nestedData;
                
            }

           
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4>Total Current  Assets,</h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = $current_assets_total;
            $nestedData['warehouse_id'] = $warehouse_id;



            $data[] = $nestedData;
    
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4>Fixed Assets</h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";
            $nestedData['warehouse_id'] = $warehouse_id;



            $data[] = $nestedData;

            

            arsort($non_current_assets);
            foreach ($non_current_assets as $account_transaction) {
                     $nestedData['key'] = count($data);
                    $nestedData['Account_category'] = "";
                    $nestedData['Account_category_type'] = "";
                    $nestedData['account'] = $account_transaction->name;

                    $nestedData['Debit'] = $account_transaction->debit - $account_transaction->credit;
                    $nestedData['Credit'] = "";
                    $nestedData['total'] = "";
                    $nestedData['warehouse_id'] = $warehouse_id;


                    $data[] = $nestedData;
                
            }
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4> Total Fixed Assets </h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = $non_current_assets_total;
            $nestedData['warehouse_id'] = $warehouse_id;


            $data[] = $nestedData;
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "<h2>Liablity</h1>";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";
            $nestedData['warehouse_id'] = $warehouse_id;


            $data[] = $nestedData;
    
     
            foreach ($liabilities as $account_transaction) {
                     $nestedData['key'] = count($data);
                    $nestedData['Account_category'] = "";
                    $nestedData['Account_category_type'] = "";
                    $nestedData['account'] = $account_transaction->name;
                    $nestedData['Debit'] = "";
                    $nestedData['Credit'] = $account_transaction->credit - $account_transaction->debit;
                    $nestedData['total'] = "";
                    $nestedData['warehouse_id'] = $warehouse_id;


                    $data[] = $nestedData;
                
            }
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = " <h4>Total   Liablities<h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = $liabilities_total;
            $nestedData['warehouse_id'] = $warehouse_id;

            $data[] = $nestedData;
    
      
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "<h2>Capital</h2>";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";
            $nestedData['warehouse_id'] = $warehouse_id;


            $data[] = $nestedData;
    
    
            foreach ($equity as $account_transaction) {
                     $nestedData['key'] = count($data);
                    $nestedData['Account_category'] = "";
                    $nestedData['Account_category_type'] = "";
                    $nestedData['account'] = $account_transaction->name;
                    $nestedData['Debit'] = "";
                    $nestedData['Credit'] = $account_transaction->credit - $account_transaction->debit;
                    $nestedData['total'] = "";
                    $nestedData['warehouse_id'] = $warehouse_id;


                    $data[] = $nestedData;
                
            }
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4>Total Equity<h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] ="";
            $nestedData['total'] =  $equity_total;
            $nestedData['warehouse_id'] = $warehouse_id;


            $data[] = $nestedData;
    
    
            // Calculate the total liabilities and equity, to ensure they match the total assets
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4>Total Capital + Total Liablities </h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = $equity_total + $liabilities_total;
            $nestedData['warehouse_id'] = $warehouse_id;
            $data[] = $nestedData;
        }
     return $data;
     }

    public function Trial_Balance(Request $request)
    {
        $data = $request->all();
        $generalseting=GeneralSetting::find(1);
        $start_date = $generalseting->fiscal_year;
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];

        if ($warehouse_id==0) {
            # code...
            $warehouse_name = "All";
        } else {
            # code...
            $warehouse=Warehouse::find($warehouse_id);
            $warehouse_name = $warehouse->name;

        }
        
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('accounttransaction.trialbalance', compact('start_date', 'end_date', 'warehouse_id', 'lims_warehouse_list','warehouse_name'));
    }



    public function balanceSheet(Request $request)
    {
        $this->storeacumulatedDeperciation();
        $this->storeacumulatedRent();

        # code...
        // Define the date range for the balance sheet
        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];
        $account_transactions_count=0;
        
        if ($warehouse_id==0) {
            # code...
                // Get the account balances for each account at the end of the date range
        $account_transactions = DB::table('account_transactions')
        ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
        ->whereBetween('account_transactions.date', [$start_date, $end_date])
        ->select('chartof_accounts.name','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
        ->groupBy('chartof_accounts.id','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
        ->orderBy('chartof_accounts.name')
        ->get();
        } else {
            # code...
                // Get the account balances for each account at the end of the date range
        $account_transactions = DB::table('account_transactions')
        ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
        ->whereBetween('account_transactions.date', [$start_date, $end_date])
        ->where('account_transactions.warehouse_id', $warehouse_id)
        ->select('chartof_accounts.name','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
        ->groupBy('chartof_accounts.id','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
        ->orderBy('chartof_accounts.name')
        ->get();
        }
        # code...
         
        $account_transactions_count=$account_transactions->count();
        // Categorize the accounts into current and non-current assets, current and non-current liabilities, and equity
        $current_assets = [];
        $non_current_assets = [];
        $liabilities = [];
        $equity = [];
        $data = [];
                
        if ($account_transactions_count > 0) {
            # code...
            foreach ($account_transactions as $account_transaction) {
                if ($account_transaction->is_current_asset ==1) {
                    $current_assets[] = $account_transaction;
                } 
            }

            foreach ($account_transactions as $account_transaction) {
                if ($account_transaction->is_current_asset ==0 && $account_transaction->is_current_asset !==null) {
                    $non_current_assets[] = $account_transaction;
                } 
            }

            foreach ($account_transactions as $account_transaction) {
                if ($account_transaction->chartof_account_categories_id ==2) {
                    $liabilities[] = $account_transaction;
                } 
            }


            foreach ($account_transactions as $account_transaction) {
                if ($account_transaction->chartof_account_categories_id ==3) {
                    $equity[] = $account_transaction;
                } 
            }
             // Calculate the total amount for each category
            $current_assets_total = collect($current_assets)->sum(function ($account_transaction) {
                return $account_transaction->debit - $account_transaction->credit;
            });
    
            $non_current_assets_total = collect($non_current_assets)->sum(function ($account_transaction) {
                return $account_transaction->debit - $account_transaction->credit;
            });
    
            $liabilities_total = collect($liabilities)->sum(function ($account_transaction) {
                return $account_transaction->credit - $account_transaction->debit;
            });
    
           
    
            $equity_total = collect($equity)->sum(function ($account_transaction) {
                return $account_transaction->credit - $account_transaction->debit;
            });
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "<h2>Asset</h2>";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";


            $data[] = $nestedData;
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4> Current Assets</h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";


            $data[] = $nestedData;
            $inventory=0;
            foreach ($current_assets as $account_transaction) {
                if ( $account_transaction->name=="Purchase"  ||   $account_transaction->name== "Beginning Inventory") {
                    # code...
                    $inventory += $account_transaction->debit - $account_transaction->credit;
                }                
            }


            foreach ($current_assets as $account_transaction) {
                if ( $account_transaction->name=="Purchase"  ||   $account_transaction->name== "Beginning Inventory") {
                    # code...
                    continue;
                }
                
                    $nestedData['key'] = count($data);
                    $nestedData['Account_category'] = "";
                    $nestedData['Account_category_type'] = "";
                    $nestedData['account'] = $account_transaction->name;
                    $nestedData['Debit'] = $account_transaction->debit - $account_transaction->credit;
                    $nestedData['Credit'] = "";
                    $nestedData['total'] = "";

                    $data[] = $nestedData;
                
            }

            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "Inventory";
            $nestedData['Debit'] = $inventory;
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";
            $data[] = $nestedData;
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4>Total Current  Assets,</h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = number_format($current_assets_total,2);
            $data[] = $nestedData;
    
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4>Fixed Assets</h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";


            $data[] = $nestedData;

            

            arsort($non_current_assets);
            foreach ($non_current_assets as $account_transaction) {
                     $nestedData['key'] = count($data);
                    $nestedData['Account_category'] = "";
                    $nestedData['Account_category_type'] = "";
                    $nestedData['account'] = $account_transaction->name;

                    $nestedData['Debit'] = $account_transaction->debit - $account_transaction->credit;
                    $nestedData['Credit'] = "";
                    $nestedData['total'] = "";

                    $data[] = $nestedData;
                
            }
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4> Total Fixed Assets </h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = $non_current_assets_total;
            $data[] = $nestedData;
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4> Total Assets </h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = number_format($current_assets_total+$non_current_assets_total,2);
            $data[] = $nestedData;


            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "<h2>Liablity</h1>";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";

            $data[] = $nestedData;
    
     
            foreach ($liabilities as $account_transaction) {
                     $nestedData['key'] = count($data);
                    $nestedData['Account_category'] = "";
                    $nestedData['Account_category_type'] = "";
                    $nestedData['account'] = $account_transaction->name;
                    $nestedData['Debit'] = "";
                    $nestedData['Credit'] = $account_transaction->credit - $account_transaction->debit;
                    $nestedData['total'] = "";

                    $data[] = $nestedData;
                
            }
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = " <h4>Total   Liablities<h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = $liabilities_total;
            $data[] = $nestedData;
    
      
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "<h2>Capital</h2>";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] = "";

            $data[] = $nestedData;
    
            $netprofit=$this->netProfit($start_date,$end_date,0);
            $reserved_fund=(70/100)* $netprofit;
            $deividend=(30/100)* $netprofit;


            foreach ($equity as $account_transaction) {
                     $nestedData['key'] = count($data);
                    $nestedData['Account_category'] = "";
                    $nestedData['Account_category_type'] = "";
                    $nestedData['account'] = $account_transaction->name;
                    $nestedData['Debit'] = "";
                    $nestedData['Credit'] = $account_transaction->credit - $account_transaction->debit;
                    $nestedData['total'] = "";

                    $data[] = $nestedData;
                
            }


            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "Reserve Fund";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = number_format($reserved_fund,2);
            $nestedData['total'] = "";
            $data[] = $nestedData;


            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "Un Distributed Dividend";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = number_format($deividend,2);
            $nestedData['total'] = "";
            $data[] = $nestedData;
        

            // reserved_fund_percentage
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4>Total Capital<h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] ="";
            $nestedData['total'] = number_format( $equity_total +$deividend+$reserved_fund,2);

            $data[] = $nestedData;
    
    
            // Calculate the total liabilities and equity, to ensure they match the total assets
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "<h4>Total Capital + Total Liablities </h4>";
            $nestedData['account'] = "";
            $nestedData['Debit'] = "";
            $nestedData['Credit'] = "";
            $nestedData['total'] =number_format( $equity_total +$deividend+$reserved_fund + $liabilities_total,2);
            $data[] = $nestedData;

        }

        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => 14,
            "recordsFiltered" => 14,
            "data" => $data
        );


        echo json_encode($json_data);

    }

    public function trialBalance(Request $request)
    { $this->storeacumulatedDeperciation();
        $this->storeacumulatedRent();

        # code...
        // Define the date range for the balance sheet
        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];
        $account_transactions_count=0;


        if ($warehouse_id==0) {
            # code...
                // Get the account balances for each account at the end of the date range
        $account_transactions = DB::table('account_transactions')
        ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
        ->whereBetween('account_transactions.date', [$start_date, $end_date])
        ->select('chartof_accounts.name','chartof_accounts.default_side','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
        ->groupBy('chartof_accounts.id','chartof_accounts.default_side','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
        ->orderBy('chartof_accounts.name')
        ->get();

        $returned_purchase_transactions = DB::table('account_transactions')
        ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
        ->whereNotNull('purchase_return_id')
        ->orWhereNotNull('warehouse_purchase_return_id')
        ->where('chartof_accounts_id',3)
        ->whereBetween('account_transactions.date', [$start_date, $end_date])
        ->select( DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
        ->groupBy('chartof_accounts.id','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
        ->orderBy('chartof_accounts.name')
        ->get();
        } else {
            # code...
                // Get the account balances for each account at the end of the date range
        $account_transactions = DB::table('account_transactions')
        ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
        ->whereBetween('account_transactions.date', [$start_date, $end_date])
        ->where('account_transactions.warehouse_id', $warehouse_id)
        ->select('chartof_accounts.name','chartof_accounts.default_side','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
        ->groupBy('chartof_accounts.id','chartof_accounts.default_side','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
        ->orderBy('chartof_accounts.name')
        ->get();

        $returned_purchase_transactions = DB::table('account_transactions')
        ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
        ->whereNotNull('purchase_return_id')
        ->orWhereNotNull('warehouse_purchase_return_id')
        ->where('chartof_accounts_id',3)
        ->whereBetween('account_transactions.date', [$start_date, $end_date])
        ->where('account_transactions.warehouse_id', $warehouse_id)
        ->select( DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
        ->groupBy('chartof_accounts.id','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
        ->orderBy('chartof_accounts.name')
        ->get();
        }
        # code...
        $account_transactions_count=$account_transactions->count();
        $returned_purchase_transactions_count=$returned_purchase_transactions->count();
        // Categorize the accounts into current and non-current assets, current and non-current liabilities, and equity
        $current_assets = [];
        $returned_purchase = [];
        $non_current_assets = [];
        $liabilities = [];
        $revenue = [];
        $equity = [];
        $expense = [];
        $data = [];
                
        if($returned_purchase_transactions_count > 0)
        {
        foreach ($returned_purchase_transactions as $account_transaction) {
                $returned_purchase[] = $account_transaction;
            
        }
            
       }  

         // Create an array to store the account balances
        $accountBalances = [];
       if($account_transactions_count > 0)
       {
         // Loop through the account transactions to calculate the balances
         foreach ($account_transactions as $account_transaction) {
            if ($account_transaction->is_current_asset ==1) {
                $current_assets[] = $account_transaction;
            } 
        }
           // Loop through the account transactions to calculate the balances
        foreach ($account_transactions as $account_transaction) {
            if ($account_transaction->is_current_asset ==0 && $account_transaction->is_current_asset !==null) {
                $non_current_assets[] = $account_transaction;
            } 
        }
        foreach ($account_transactions as $account_transaction) {
            if ($account_transaction->chartof_account_categories_id ==2) {
                $liabilities[] = $account_transaction;
            } 
        }
        foreach ($account_transactions as $account_transaction) {
            if ($account_transaction->chartof_account_categories_id ==3) {
                $equity[] = $account_transaction;
            } 
        }

        foreach ($account_transactions as $account_transaction) {
            if ($account_transaction->chartof_account_categories_id ==4) {
                $revenue[] = $account_transaction;
            } 
        }
        foreach ($account_transactions as $account_transaction) {
            if ($account_transaction->chartof_account_categories_id ==5) {
                $expense[] = $account_transaction;
            } 
        }
        $deducted_purchase = collect($returned_purchase)->sum(function ($account_transaction) {
            return $account_transaction->credit;
        });
        // Sort the account balances by name
        foreach ($current_assets as $account_transaction) {
            if ($account_transaction->name==="Purchase") {
                # code...
                if($deducted_purchase===null){
                    $deducted_purchase=0;
                }
                
                $nestedData['key'] = count($data);
                $nestedData['account'] = $account_transaction->name;
                $nestedData['debit'] =  number_format( $account_transaction->debit - $deducted_purchase, 2) ;
                $nestedData['credit'] = "";
                $data[] = $nestedData;
            } else {
                # code...
                if($account_transaction->credit==null){
                    $account_transaction->credit=0;
                }
                $nestedData['key'] = count($data);
                $nestedData['account'] = $account_transaction->name;
                $nestedData['debit'] = $account_transaction->debit - $account_transaction->credit;
                $nestedData['credit'] = "";
                $data[] = $nestedData;
            }
        }
        krsort($non_current_assets);
        foreach ($non_current_assets as $account_transaction) {
            if ($account_transaction->default_side==="debit") {
                # code...
                if($account_transaction->credit==null){
                    $account_transaction->credit=0;
                }
                $nestedData['key'] = count($data);
                $nestedData['account'] = $account_transaction->name;
                $nestedData['debit'] = number_format($account_transaction->debit - $account_transaction->credit, 2) ;
                $nestedData['credit'] = "";
                $data[] = $nestedData;
            } else {
                # code...
                if($account_transaction->debit==null){
                    $account_transaction->debit=0;
                }
                $nestedData['key'] = count($data);
                $nestedData['account'] = $account_transaction->name;
                $nestedData['debit'] = "";
                $nestedData['credit'] = number_format($account_transaction->credit - $account_transaction->debit, 2) ;
                $data[] = $nestedData;
            }
        }
        foreach ($liabilities as $account_transaction) {
                # code...
                if($account_transaction->debit==null){
                    $account_transaction->debit=0;
                }
                $nestedData['key'] = count($data);
                $nestedData['account'] = $account_transaction->name;
                $nestedData['debit'] = "";
                $nestedData['credit'] = number_format($account_transaction->credit - $account_transaction->debit, 2) ;
                $data[] = $nestedData;
            
        }

        foreach ($revenue as $account_transaction) {
            # code...
            if($account_transaction->debit==null){
                $account_transaction->debit=0;
            }
            $nestedData['key'] = count($data);
            $nestedData['account'] = $account_transaction->name;
            $nestedData['debit'] = "";
            $nestedData['credit'] = number_format($account_transaction->credit - $account_transaction->debit, 2) ;
            $data[] = $nestedData;
        
    }
    foreach ($expense as $account_transaction) {
        # code...
        if($account_transaction->credit==null){
            $account_transaction->credit=0;
        }
        $nestedData['key'] = count($data);
        $nestedData['account'] = $account_transaction->name;
        $nestedData['debit'] = number_format($account_transaction->debit  - $account_transaction->credit,2);
        $nestedData['credit'] = "";
        $data[] = $nestedData;
        
    }
    foreach ($equity as $account_transaction) {
        # code...
        if($account_transaction->debit==null){
            $account_transaction->debit=0;
        }
        $nestedData['key'] = count($data);
        $nestedData['account'] = $account_transaction->name;
        $nestedData['debit'] = "";
        $nestedData['credit'] = number_format($account_transaction->credit - $account_transaction->debit,2);
        $data[] = $nestedData;
    
}


       }

        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => 14,
            "recordsFiltered" => 14,
            "data" => $data
        );


        echo json_encode($json_data);


    }

    public function incomeStatement(Request $request)
    {
        $this->storeacumulatedRent();

        $this->storeacumulatedDeperciation();
 
        # code...
        // Define the date range for the balance sheet
        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];
        $account_transactions_count=0;
        $data = [];
        // Get the account balances for each account at the end of the date range
        if ($warehouse_id == 0) {
            
            $account_transactions = DB::table('account_transactions')
            ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
            ->join('chartof_account_categories', 'chartof_accounts.chartof_account_categories_id', '=', 'chartof_account_categories.id')
            ->where('chartof_accounts.chartof_account_categories_id', 4)
            ->orwhere('chartof_accounts.is_current_asset', 1)
            ->orwhere('chartof_accounts.chartof_account_categories_id', 5)
            ->select('chartof_accounts.chartof_account_categories_id', 'chartof_accounts.name', 'chartof_accounts.is_admin_expense',  DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
            ->groupBy('chartof_accounts.id', 'chartof_accounts.name', 'chartof_accounts.is_admin_expense', 'chartof_accounts.chartof_account_categories_id')
            ->orderBy('chartof_accounts.id')
            ->get();


            $returned_purchase_transactions = DB::table('account_transactions')
            ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
            ->whereNotNull('purchase_return_id')
            ->orWhereNotNull('warehouse_purchase_return_id')
            ->where('chartof_accounts_id',3)
            ->whereBetween('account_transactions.date', [$start_date, $end_date])
            ->select( DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
            ->groupBy('chartof_accounts.id','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
            ->orderBy('chartof_accounts.name')
            ->get();
            # code...
        }else {
            # code...
           
        


            $account_transactions = DB::table('account_transactions')
            ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
            ->join('chartof_account_categories', 'chartof_accounts.chartof_account_categories_id', '=', 'chartof_account_categories.id')
            ->where('chartof_accounts.chartof_account_categories_id', 4)
             ->orwhere('chartof_accounts.chartof_account_categories_id', 5)
            ->where('account_transactions.warehouse_id', $warehouse_id)
            ->select('chartof_accounts.chartof_account_categories_id', 'chartof_accounts.name', 'chartof_accounts.is_admin_expense',  DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
            ->groupBy('chartof_accounts.id', 'chartof_accounts.name', 'chartof_accounts.is_admin_expense', 'chartof_accounts.chartof_account_categories_id')
            ->orderBy('chartof_accounts.id')
            ->get();


            $returned_purchase_transactions = DB::table('account_transactions')
            ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
            ->whereNotNull('purchase_return_id')
            ->orWhereNotNull('warehouse_purchase_return_id')
            ->where('chartof_accounts_id',3)
            ->whereBetween('account_transactions.date', [$start_date, $end_date])
            ->where('account_transactions.warehouse_id', $warehouse_id)
            ->select( DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
            ->groupBy('chartof_accounts.id','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
            ->orderBy('chartof_accounts.name')
            ->get();
        }
        
        $account_transactions_count=$account_transactions->count();


        $returned_purchase_transactions_count=$returned_purchase_transactions->count();
     
       

    // Categorize the accounts into current and non-current assets, current and non-current liabilities, and equity
    $sales = [];
    $revenues = [];
    $adminexpenses = []; 
    $cogs = []; 
    $Beginv = []; 
    $purchase = []; 
    $expense = []; 

          
    if($returned_purchase_transactions_count > 0)
    {
    foreach ($returned_purchase_transactions as $account_transaction) {
            $returned_purchase[] = $account_transaction;
        
    }
        
   } 
    if($account_transactions_count >0)
    {
        foreach ($account_transactions as $account_transaction) {
            if ($account_transaction->chartof_account_categories_id ==4) {

                if ($account_transaction->name!=="Sales") {
                    # code...
                    $revenues[] = $account_transaction;

                } else {
                    # code...
                    $sales[] = $account_transaction;


                }
                
            } elseif ($account_transaction->chartof_account_categories_id ==5) {

                if ($account_transaction->is_admin_expense ==0 && $account_transaction->is_admin_expense !==null && $account_transaction->name=="Cost of Goods Sold") {
                    # code...
                    $cogs = $account_transaction;

                }elseif($account_transaction->is_admin_expense ==0 && $account_transaction->is_admin_expense !==null) {
                    # code...
                    $expense[] = $account_transaction;
                    
                }  

                if ($account_transaction->is_admin_expense ==1 && $account_transaction->is_admin_expense !==null) {
                    # code...
                    $adminexpenses[] = $account_transaction;
                } 
             
            } else {

                if ($account_transaction->name=="Purchase") {
                    # code...
                    $purchase = $account_transaction;

                }

                if ($account_transaction->name=="Beginning Inventory") {
                    # code...
                    $Beginv = $account_transaction;

                }
                continue;
            }
        }
    

 
        // Calculate the total amount for each category
        $sales_total = collect($sales)->sum(function ($account_transaction) {
            return $account_transaction->credit - $account_transaction->debit;
        });

        $revenue_total = collect($revenues)->sum(function ($account_transaction) {
            return $account_transaction->credit - $account_transaction->debit;
        });

        // $cogs_total = collect($cogs)->sum(function ($account_transaction) {
        //     return $account_transaction->debit - $account_transaction->credit;
        // });

        // $purchase_total = collect($purchase)->sum(function ($account_transaction) {
        //     return  $account_transaction->debit;
        // });
        // $Beginv_total = collect($Beginv)->sum(function ($account_transaction) {
        //     return $account_transaction->debit - $account_transaction->credit;
        // });
    
        $expenses_total = collect($expense)->sum(function ($account_transaction) {
            return $account_transaction->debit - $account_transaction->credit;
        });

        $adminexpenses_total = collect($adminexpenses)->sum(function ($account_transaction) {
            return $account_transaction->debit - $account_transaction->credit;
        });

        $deducted_purchase = collect($returned_purchase)->sum(function ($account_transaction) {
            return $account_transaction->credit;
        });
        
        $data = [];

        foreach ($sales as $account_transaction) {
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "Sales";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "";
            $nestedData['debit'] = "";
            $nestedData['credit'] = number_format( $account_transaction->credit- $account_transaction->debit,2);
             $data[] = $nestedData;  
            
        }
    
     
        $nestedData['key'] = count($data);
        $nestedData['Account_category'] = "";
        $nestedData['Account_category_type'] = "";
        $nestedData['account'] =$Beginv->name;
        $nestedData['debit'] = number_format( $Beginv->debit- $Beginv->credit,2);;
        $nestedData['credit'] = "";
            $data[] = $nestedData;  
            
        
        $nestedData['key'] = count($data);
        $nestedData['Account_category'] = "";
        $nestedData['Account_category_type'] = "";
        $nestedData['account'] = $purchase->name;
        $nestedData['debit'] = number_format( $purchase->debit - $deducted_purchase,2);;
        $nestedData['credit'] = "";
        $data[] = $nestedData;  
            
        $nestedData['key'] = count($data);
        $nestedData['Account_category'] = "";
        $nestedData['Account_category_type'] = "Total Available for Sale";
        $nestedData['account'] = "";
        $nestedData['debit'] = number_format( ($Beginv->debit - $Beginv->credit) + $purchase->debit -$deducted_purchase,2);;
        $nestedData['credit'] = "";
         $data[] = $nestedData;  
 

     
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = "Ending Inventory";
            $nestedData['debit'] = number_format( ($Beginv->debit - $Beginv->credit)+ $purchase->debit- $purchase->credit,2);;
            $nestedData['credit'] = "";
             $data[] = $nestedData;  
            
      
     
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] ="";
            $nestedData['account'] = $cogs->name;
            $nestedData['debit'] = number_format(10,2);
            $nestedData['credit'] = "";
             $data[] = $nestedData;  
            
        

        $nestedData['key'] = count($data);
        $nestedData['Account_category'] = "Gross Profit";
        $nestedData['Account_category_type'] = "";
        $nestedData['account'] = "";
        $nestedData['debit'] = "";
        $nestedData['credit'] =number_format( $sales_total - (10),2);
         $data[] = $nestedData;

        $nestedData['key'] = count($data);
        $nestedData['Account_category'] = "";
        $nestedData['Account_category_type'] = "";
        $nestedData['account'] = "Other Income";
        $nestedData['debit'] = "";
        $nestedData['credit'] = "";
         $data[] = $nestedData;


            
        foreach ($revenues as $account_transaction) {
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = $account_transaction->name;
            $nestedData['debit'] = number_format( $account_transaction->credit- $account_transaction->debit,2);
            $nestedData['credit'] = "";
             $data[] = $nestedData;  
            
        }


        $nestedData['key'] = count($data);
        $nestedData['Account_category'] = "Expense";
        $nestedData['Account_category_type'] = "";
        $nestedData['account'] = "";
        $nestedData['debit'] = "";
        $nestedData['credit'] = "";
         $data[] = $nestedData;

        foreach ($expense as $account_transaction) {
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = $account_transaction->name;
            $nestedData['debit'] = number_format( $account_transaction->debit- $account_transaction->credit,2);
            $nestedData['credit'] = "";
             $data[] = $nestedData;  
            
        }

        $nestedData['key'] = count($data);
        $nestedData['Account_category'] = "";
        $nestedData['Account_category_type'] = "Admin Expense";
        $nestedData['account'] = "";
        $nestedData['debit'] = "";
        $nestedData['credit'] = "";
         $data[] = $nestedData;


        foreach ($adminexpenses as $account_transaction) {
    
            $nestedData['key'] = count($data);
            $nestedData['Account_category'] = "";
            $nestedData['Account_category_type'] = "";
            $nestedData['account'] = $account_transaction->name;
            $nestedData['debit'] = number_format( $account_transaction->debit- $account_transaction->credit,2);
            $nestedData['credit'] = "";
             $data[] = $nestedData;  
            
        }

        $nestedData['key'] = count($data);
        $nestedData['Account_category'] = "Total Expense";
        $nestedData['Account_category_type'] = "";
        $nestedData['account'] = "";
        $nestedData['debit'] =number_format($expenses_total + $adminexpenses_total,2);
        $nestedData['credit'] = "";
        $data[] = $nestedData;

        $nestedData['key'] = count($data);
        $nestedData['Account_category'] = "Net Profit";
        $nestedData['Account_category_type'] = "";
        $nestedData['account'] = "";
        $nestedData['debit'] = "";
        $nestedData['credit'] = number_format(($sales_total - ($cogs->debit- $cogs->credit))-($expenses_total + $adminexpenses_total),2);
         $data[] = $nestedData;
   
         
    
    }
    $json_data = array(
        "draw" => intval($request->input('draw')),
        "recordsTotal" => 20,
        "recordsFiltered" => 20,
        "data" => $data
    );
 
    echo json_encode($json_data);

    }
    public function netProfit($start_date,$end_date,$warehouse_id)
    {
 
        $account_transactions_count=0;
        $data = [];

        
        // Get the account balances for each account at the end of the date range
        if ($warehouse_id == 0) {
            
            $account_transactions = DB::table('account_transactions')
            ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
            ->join('chartof_account_categories', 'chartof_accounts.chartof_account_categories_id', '=', 'chartof_account_categories.id')
            ->where('chartof_accounts.chartof_account_categories_id', 4)
            ->orwhere('chartof_accounts.is_current_asset', 1)
            ->orwhere('chartof_accounts.chartof_account_categories_id', 5)
            ->select('chartof_accounts.chartof_account_categories_id', 'chartof_accounts.name', 'chartof_accounts.is_admin_expense',  DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
            ->groupBy('chartof_accounts.id', 'chartof_accounts.name', 'chartof_accounts.is_admin_expense', 'chartof_accounts.chartof_account_categories_id')
            ->orderBy('chartof_accounts.id')
            ->get();


            $returned_purchase_transactions = DB::table('account_transactions')
            ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
            ->whereNotNull('purchase_return_id')
            ->orWhereNotNull('warehouse_purchase_return_id')
            ->where('chartof_accounts_id',3)
            ->whereBetween('account_transactions.date', [$start_date, $end_date])
            ->select( DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
            ->groupBy('chartof_accounts.id','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
            ->orderBy('chartof_accounts.name')
            ->get();
            # code...
        }else {
            # code...
           
        


            $account_transactions = DB::table('account_transactions')
            ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
            ->join('chartof_account_categories', 'chartof_accounts.chartof_account_categories_id', '=', 'chartof_account_categories.id')
            ->where('chartof_accounts.chartof_account_categories_id', 4)
            ->orwhere('chartof_accounts.is_current_asset', 1)
            ->orwhere('chartof_accounts.chartof_account_categories_id', 5)
            ->where('account_transactions.warehouse_id', $warehouse_id)
            ->select('chartof_accounts.chartof_account_categories_id', 'chartof_accounts.name', 'chartof_accounts.is_admin_expense',  DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
            ->groupBy('chartof_accounts.id', 'chartof_accounts.name', 'chartof_accounts.is_admin_expense', 'chartof_accounts.chartof_account_categories_id')
            ->orderBy('chartof_accounts.id')
            ->get();


            $returned_purchase_transactions = DB::table('account_transactions')
            ->join('chartof_accounts', 'account_transactions.chartof_accounts_id', '=', 'chartof_accounts.id')
            ->whereNotNull('purchase_return_id')
            ->orWhereNotNull('warehouse_purchase_return_id')
            ->where('chartof_accounts_id',3)
            ->whereBetween('account_transactions.date', [$start_date, $end_date])
            ->where('account_transactions.warehouse_id', $warehouse_id)
            ->select( DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
            ->groupBy('chartof_accounts.id','chartof_accounts.chartof_account_categories_id','chartof_accounts.default_side','chartof_accounts.is_current_asset', 'chartof_accounts.name')
            ->orderBy('chartof_accounts.name')
            ->get();
        }
        
        $account_transactions_count=$account_transactions->count();


        $returned_purchase_transactions_count=$returned_purchase_transactions->count();
     
       

    // Categorize the accounts into current and non-current assets, current and non-current liabilities, and equity
    $sales = [];
    $revenues = [];
    $adminexpenses = []; 
    $cogs = []; 
    $Beginv = []; 
    $purchase = []; 
    $expense = []; 

          
    if($returned_purchase_transactions_count > 0)
    {
    foreach ($returned_purchase_transactions as $account_transaction) {
            $returned_purchase[] = $account_transaction;
        
    }
        
   } 
    if($account_transactions_count >0)
    {
        foreach ($account_transactions as $account_transaction) {
            if ($account_transaction->chartof_account_categories_id ==4) {

                if ($account_transaction->name!=="Sales") {
                    # code...
                    $revenues[] = $account_transaction;

                } else {
                    # code...
                    $sales[] = $account_transaction;


                }
                
            } elseif ($account_transaction->chartof_account_categories_id ==5) {

                if ($account_transaction->is_admin_expense ==0 && $account_transaction->is_admin_expense !==null && $account_transaction->name=="Cost of Goods Sold") {
                    # code...
                    $cogs = $account_transaction;

                }elseif($account_transaction->is_admin_expense ==0 && $account_transaction->is_admin_expense !==null) {
                    # code...
                    $expense[] = $account_transaction;
                    
                }  

                if ($account_transaction->is_admin_expense ==1 && $account_transaction->is_admin_expense !==null) {
                    # code...
                    $adminexpenses[] = $account_transaction;
                } 
             
            } else {

                if ($account_transaction->name=="Purchase") {
                    # code...
                    $purchase = $account_transaction;

                }

                if ($account_transaction->name=="Beginning Inventory") {
                    # code...
                    $Beginv = $account_transaction;

                }
                continue;
            }
        }
    

 
        // Calculate the total amount for each category
        $sales_total = collect($sales)->sum(function ($account_transaction) {
            return $account_transaction->credit - $account_transaction->debit;
        });

        $revenue_total = collect($revenues)->sum(function ($account_transaction) {
            return $account_transaction->credit - $account_transaction->debit;
        });

        // $cogs_total = collect($cogs)->sum(function ($account_transaction) {
        //     return $account_transaction->debit - $account_transaction->credit;
        // });

        // $purchase_total = collect($purchase)->sum(function ($account_transaction) {
        //     return  $account_transaction->debit;
        // });
        // $Beginv_total = collect($Beginv)->sum(function ($account_transaction) {
        //     return $account_transaction->debit - $account_transaction->credit;
        // });
    
        $expenses_total = collect($expense)->sum(function ($account_transaction) {
            return $account_transaction->debit - $account_transaction->credit;
        });

        $adminexpenses_total = collect($adminexpenses)->sum(function ($account_transaction) {
            return $account_transaction->debit - $account_transaction->credit;
        });

        $deducted_purchase = collect($returned_purchase)->sum(function ($account_transaction) {
            return $account_transaction->credit;
        });
        
       return  ($sales_total - ($cogs->debit- $cogs->credit))-($expenses_total + $adminexpenses_total);
    
    }
   


    }
 


    function storeacumulatedDeperciation()
    {
        DB::beginTransaction();
    
        try {
            $fixed_asset = FixedAsset::all();
    
            foreach ($fixed_asset as $key => $fa) {
                $dep = $this->acumulatedDeperciation($fa->created_at, $fa->total_cost, $fa->fixed_asset_category_id);
                $lims_fixedasset_category = FixedAssetCategory::where('id', $fa->fixed_asset_category_id)->first();
                $fiscla_year = GeneralSetting::where('id', 1)->first();
                AccountTransaction::where('fixed_asset_id', $fa->id)
                    ->where('is_auto_generated', 1)
                    ->forceDelete();
    
                $accdep = 0;
                foreach ($dep as $key => $d) {
                    $accdep += $d['depreciation'];
                    if ($fiscla_year->fiscal_year > ($d['depreciation_date']->toGregorian()->format('y-m-d'))) {
                        
                        continue;
                    }
    
                    $transaction = new AccountTransaction;
                    $transaction->reference_no = 'automatic' . $d['depreciation_date']->toGregorian()->format('y-m-d');
                    $transaction->date = $d['depreciation_date']->toGregorian()->format('y-m-d');
                    $transaction->created_at = $d['depreciation_date']->toGregorian()->format('y-m-d');
                    $transaction->user_id = Auth::id();
                    $transaction->warehouse_id = $fa->warehouse_id;
                    $transaction->debit = $d['depreciation'];
                    $transaction->credit = 0;
                    $accountType = ChartofAccount::where('name', "Dep Expense Of ". $lims_fixedasset_category->name)->first();
                    $transaction->chartof_accounts_id = $accountType->id;
                    $transaction->fixed_asset_id = $fa->id;
                    $transaction->is_auto_generated = 1;
                    $transaction->save();

                 }
    
                $transaction = new AccountTransaction;
                $transaction->reference_no = 'automatic Accdep';
                $transaction->date = new DateTime();
                $transaction->created_at = new DateTime();
                $transaction->user_id = Auth::id();
                $transaction->warehouse_id = $fa->warehouse_id;
                $transaction->debit = 0;
                $transaction->credit = $accdep;
                $accountType = ChartofAccount::where('name', "Acc.Dep Of " . $lims_fixedasset_category->name)->first();
                $transaction->chartof_accounts_id = $accountType->id;
                $transaction->fixed_asset_id = $fa->id;
                $transaction->is_auto_generated = 1;
                $transaction->save();
            }
    
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    



    function storeacumulatedRent()
    {
        DB::beginTransaction();
    
        try {
            $prepaid_rent = PrePaidRent::all();
    
            foreach ($prepaid_rent as $key => $fa) {
                $dep = $this->acumulatedRent($fa->created_at, $fa->total_cost, $fa->life_time);
                 $fiscla_year = GeneralSetting::where('id', 1)->first();
                AccountTransaction::where('prepaid_rent_id', $fa->id)
                    ->where('is_auto_generated', 1)
                    ->forceDelete();
    
                $accdep = 0;
                foreach ($dep as $key => $d) {
                     if ($fiscla_year->fiscal_year > ($d['depreciation_date']->toGregorian()->format('y-m-d'))) {
                        continue;
                    }
    
                    $transaction = new AccountTransaction;
                    $transaction->reference_no = 'automatic_rent_exense_debit' . $d['depreciation_date']->toGregorian()->format('y-m-d');
                    $transaction->date = $d['depreciation_date']->toGregorian()->format('y-m-d');
                    $transaction->created_at = $d['depreciation_date']->toGregorian()->format('y-m-d');
                    $transaction->user_id = Auth::id();
                    $transaction->warehouse_id = $fa->warehouse_id;
                    $transaction->debit = $d['depreciation'];
                    $transaction->credit = 0;
                    $accountType = ChartofAccount::where('name', "Rent Expense")->first();
                    $transaction->chartof_accounts_id = $accountType->id;
                    $transaction->prepaid_rent_id = $fa->id;
                    $transaction->is_auto_generated = 1;
                    $transaction->save();


                    $transaction = new AccountTransaction;
                    $transaction->reference_no = 'automatic_prepaid_rent_credit' . $d['depreciation_date']->toGregorian()->format('y-m-d');
                    $transaction->date = $d['depreciation_date']->toGregorian()->format('y-m-d');
                    $transaction->created_at = $d['depreciation_date']->toGregorian()->format('y-m-d');
                    $transaction->user_id = Auth::id();
                    $transaction->warehouse_id = $fa->warehouse_id;
                    $transaction->debit = 0;
                    $transaction->credit =$d['depreciation'];
                    $accountType = ChartofAccount::where('name', "PrePaid Rent")->first();
                    $transaction->chartof_accounts_id = $accountType->id;
                    $transaction->prepaid_rent_id = $fa->id;
                    $transaction->is_auto_generated = 1;
                    $transaction->save();
                }
    
               
            }
    
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    


    function acumulatedDeperciation($purchasedate,$totalCost, $category){

        $lims_fixedasset_category= FixedAssetCategory::where('id',$category)->first();
        $cost=$totalCost-1;
        $yearlyDepreciation=$cost/$lims_fixedasset_category->life_time;
        $monthlyDepreciation = $yearlyDepreciation/12;
        $dailyDepreciation=$monthlyDepreciation/30;
    
    
        // convert the purchase date and end date to Ethiopian Calendar
        $startDateEthiopian = new AndegnaDateTime($purchasedate);
        $endDateEthiopian = new AndegnaDateTime(new DateTime());
        
        // initialize variables
        $accumulatedDepreciation = 0;
        $depreciationData = [];
        
        // set the current date to the purchase date
         $currentDateEthiopian = clone new AndegnaDateTime($purchasedate);
        
        // calculate monthly depreciation for each month from the purchase date to the end date
        $prev=0;
        $ispagume=false;
        while ($currentDateEthiopian <= $endDateEthiopian) {
            $isnehase=false;
            $isnumDayschanged=false;

            if ($currentDateEthiopian->getYear() == $endDateEthiopian->getYear()
            && $currentDateEthiopian->getMonth() == $endDateEthiopian->getMonth()
            && $currentDateEthiopian->getDay() == $endDateEthiopian->getDay()
        ) {
            // day one
            
            $depreciation=$dailyDepreciation;
            $accumulatedDepreciation=$depreciation;
            $date = clone $currentDateEthiopian;
            $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
            $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
 
            $depreciationData[] = [
                'depreciation_date' => $depreciationDateEthiopian,
                'depreciation' => $depreciation,
                'accumulated_depreciation' => $accumulatedDepreciation,
            ];

            break;
        }
            
            if ($cost<=1) {
                # code...
                $currentDateEthiopian->add(new DateInterval('P'.$numDays.'D'));
                continue;
            } else {
                # code...
                // calculate the number of days in the current month
            // skip month 13
            if ($currentDateEthiopian->getMonth() == 13) {
                $currentDateEthiopian->add(new DateInterval('P1Y'));
                $currentDateEthiopian->setDate($currentDateEthiopian->getYear(),1,1);
                $ispagume=true;
    
             }
            // calculate the number of days in the current month
            if ($currentDateEthiopian->getYear() == $startDateEthiopian->getYear()
                && $currentDateEthiopian->getMonth() == $startDateEthiopian->getMonth()
                && $currentDateEthiopian->getDay() == $startDateEthiopian->getDay()
            ) {
                // first month
                $isnumDayschanged=true;
                $isFirstMonth=false;
                $numDays = 30 - $startDateEthiopian->getDay();
            } elseif ($currentDateEthiopian->getYear() == $endDateEthiopian->getYear() && $currentDateEthiopian->getMonth() == $endDateEthiopian->getMonth()) 
            {
                // last month
                
                 $numDays = 30;
 
            } else {
                // full month
                $numDays = 30;
 
            }
            // calculate the depreciation for the current month
            if($isnumDayschanged){
                $num= $numDays + 1;
                $depreciation = $num* $dailyDepreciation;

            }else
            {
                $depreciation = $numDays * $dailyDepreciation;

            }
           
            if($depreciation>=$cost){
                $depreciation=$cost;
            }
            $cost-=$depreciation;
            // add the depreciation to the accumulated depreciation
            $accumulatedDepreciation += $depreciation;
            if ($ispagume) {
                # code...
                $ispagume=false;
                $isFirstMonth=false;
                $numDays=29;
            $date = clone $currentDateEthiopian;
            $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
            $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
            $depreciationDateEthiopian->add(new DateInterval('P'.$numDays.'D'));

            $depreciationData[] = [
                'depreciation_date' => $depreciationDateEthiopian,
                'depreciation' => $depreciation,
                'accumulated_depreciation' => $accumulatedDepreciation,
            ];
            // $currentDateEthiopian->setDate($currentDateEthiopian->getYear(),2,1);
            // $isnehase=true;
            }elseif ($currentDateEthiopian->getMonth() == 12) {
            # code...
            if ($isFirstMonth==true) {
                # code...
                $isFirstMonth=false;
                $numDays=29;
            }
            $date = clone $currentDateEthiopian;
            $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
            $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
            $depreciationDateEthiopian->add(new DateInterval('P'.$numDays.'D'));
            $depreciationData[] = [
                'depreciation_date' => $depreciationDateEthiopian,
                'depreciation' => $depreciation,
                'accumulated_depreciation' => $accumulatedDepreciation,
            ];
            $currentDateEthiopian->add(new DateInterval('P1Y'));
            $currentDateEthiopian->setDate($currentDateEthiopian->getYear(),1,1);
            $ispagume=true;   
            $isnehase=true;
            } else {

                if ($isFirstMonth==true) {
                    # code...
                    $isFirstMonth=false;
                    $numDays=29;
                }
                # code...
            $date = clone $currentDateEthiopian;
            $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
            $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
            $depreciationDateEthiopian->add(new DateInterval('P'.$numDays.'D'));
            $depreciationData[] = [
                'depreciation_date' => $depreciationDateEthiopian,
                'depreciation' => $depreciation,
                'accumulated_depreciation' => $accumulatedDepreciation,
            ];
            $isnehase=false;
            }
            // set the depreciation date
            // add the depreciation data for the current month to the depreciation data array
           
           if ($isnehase==false) {

            # code...
            if ($isnumDayschanged==true) {
                # code...
                $numDays1=$numDays+1;
                $isFirstMonth=true;
                
                $currentDateEthiopian->add(new DateInterval('P'.$numDays1 .'D'));

            }else {
                # code...
                $currentDateEthiopian->add(new DateInterval('P'.$numDays .'D'));

            }
    
           }
            }
            
    
        }
        return $depreciationData;
    
     }




     
    function acumulatedRent($purchasedate,$totalCost, $life_time){

         $cost=$totalCost;
        $yearlyDepreciation=$cost/$life_time;
        $monthlyDepreciation = $yearlyDepreciation/12;
        $dailyDepreciation=$monthlyDepreciation/30;
    
    
        // convert the purchase date and end date to Ethiopian Calendar
        $startDateEthiopian = new AndegnaDateTime($purchasedate);
        $endDateEthiopian = new AndegnaDateTime(new DateTime('2023-04-10'));
        
        // initialize variables
        $accumulatedDepreciation = 0;
        $depreciationData = [];
        
        // set the current date to the purchase date
         $currentDateEthiopian = clone new AndegnaDateTime($purchasedate);
        
        // calculate monthly depreciation for each month from the purchase date to the end date
        $prev=0;
        $ispagume=false;
        while ($currentDateEthiopian <= $endDateEthiopian) {
            $isnehase=false;
            $isnumDayschanged=false;

            if ($currentDateEthiopian->getYear() == $endDateEthiopian->getYear()
            && $currentDateEthiopian->getMonth() == $endDateEthiopian->getMonth()
            && $currentDateEthiopian->getDay() == $endDateEthiopian->getDay()
        ) {
            // day one
            
            $depreciation=$dailyDepreciation;
            $accumulatedDepreciation=$depreciation;
            $date = clone $currentDateEthiopian;
            $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
            $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
 
            $depreciationData[] = [
                'depreciation_date' => $depreciationDateEthiopian,
                'depreciation' => $depreciation,
                'accumulated_depreciation' => $accumulatedDepreciation,
            ];

            break;
        }
            
            if ($cost<=0) {
                # code...
                $currentDateEthiopian->add(new DateInterval('P'.$numDays.'D'));
                continue;
            } else {
                # code...
                // calculate the number of days in the current month
            // skip month 13
            if ($currentDateEthiopian->getMonth() == 13) {
                $currentDateEthiopian->add(new DateInterval('P1Y'));
                $currentDateEthiopian->setDate($currentDateEthiopian->getYear(),1,1);
                $ispagume=true;
    
             }
            // calculate the number of days in the current month
            if ($currentDateEthiopian->getYear() == $startDateEthiopian->getYear()
                && $currentDateEthiopian->getMonth() == $startDateEthiopian->getMonth()
                && $currentDateEthiopian->getDay() == $startDateEthiopian->getDay()
            ) {
                // first month
                $isnumDayschanged=true;
                $isFirstMonth=false;
                $numDays = 30 - $startDateEthiopian->getDay();
            } elseif ($currentDateEthiopian->getYear() == $endDateEthiopian->getYear() && $currentDateEthiopian->getMonth() == $endDateEthiopian->getMonth()) 
            {
                // last month
                
                 $numDays = 30;
 
            } else {
                // full month
                $numDays = 30;
 
            }
            // calculate the depreciation for the current month
            if($isnumDayschanged){
                $num= $numDays + 1;
                $depreciation = $num* $dailyDepreciation;

            }else
            {
                $depreciation = $numDays * $dailyDepreciation;

            }
           
            if($depreciation>=$cost){
                $depreciation=$cost;
            }
            $cost-=$depreciation;
            // add the depreciation to the accumulated depreciation
            $accumulatedDepreciation += $depreciation;
            if ($ispagume) {
                # code...
                $ispagume=false;
                $isFirstMonth=false;
                $numDays=29;
            $date = clone $currentDateEthiopian;
            $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
            $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
            $depreciationDateEthiopian->add(new DateInterval('P'.$numDays.'D'));

            $depreciationData[] = [
                'depreciation_date' => $depreciationDateEthiopian,
                'depreciation' => $depreciation,
                'accumulated_depreciation' => $accumulatedDepreciation,
            ];
            // $currentDateEthiopian->setDate($currentDateEthiopian->getYear(),2,1);
            // $isnehase=true;
            }elseif ($currentDateEthiopian->getMonth() == 12) {
            # code...
            if ($isFirstMonth==true) {
                # code...
                $isFirstMonth=false;
                $numDays=29;
            }
            $date = clone $currentDateEthiopian;
            $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
            $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
            $depreciationDateEthiopian->add(new DateInterval('P'.$numDays.'D'));
            $depreciationData[] = [
                'depreciation_date' => $depreciationDateEthiopian,
                'depreciation' => $depreciation,
                'accumulated_depreciation' => $accumulatedDepreciation,
            ];
            $currentDateEthiopian->add(new DateInterval('P1Y'));
            $currentDateEthiopian->setDate($currentDateEthiopian->getYear(),1,1);
            $ispagume=true;   
            $isnehase=true;
            } else {

                if ($isFirstMonth==true) {
                    # code...
                    $isFirstMonth=false;
                    $numDays=29;
                }
                # code...
            $date = clone $currentDateEthiopian;
            $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
            $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
            $depreciationDateEthiopian->add(new DateInterval('P'.$numDays.'D'));
            $depreciationData[] = [
                'depreciation_date' => $depreciationDateEthiopian,
                'depreciation' => $depreciation,
                'accumulated_depreciation' => $accumulatedDepreciation,
            ];
            $isnehase=false;
            }
            // set the depreciation date
            // add the depreciation data for the current month to the depreciation data array
           
           if ($isnehase==false) {

            # code...
            if ($isnumDayschanged==true) {
                # code...
                $numDays1=$numDays+1;
                $isFirstMonth=true;
                
                $currentDateEthiopian->add(new DateInterval('P'.$numDays1 .'D'));

            }else {
                # code...
                $currentDateEthiopian->add(new DateInterval('P'.$numDays .'D'));

            }
    
           }
            }
            
    
        }
        return $depreciationData;
    
     }








}