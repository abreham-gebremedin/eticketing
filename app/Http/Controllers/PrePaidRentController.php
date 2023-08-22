<?php

namespace App\Http\Controllers;
use Andegna\DateTime as AndegnaDateTime;
use App\Account;
 use App\AccountTransaction;
use App\AccountTransactionAdjustment;
use App\ChartofAccount;
use App\GeneralSetting;
use App\Journal_Entry;
use App\Payment;
use App\PaymentWithCheque;
use App\PaymentWithCreditCard;
use App\PaymentWithGiftCard;
use App\PaymentWithMobile;
use App\PaymentWithPOSATM;
use App\PosSetting;
use App\PrePaidRent;
use App\Warehouse;
use Auth;
use DateInterval;
use DateTime;
use DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;// add this line

class PrePaidRentController extends Controller
{    public function index(Request $request)
    {


        // dd($this->acumulatedDeperciation(new DateTime('2023-03-01'),1801,22));
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('fixed_asset-index')){
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';

             
            if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;
            //  dd( $all_permission);
            $lims_warehouse_list = Warehouse::select('name', 'id')->where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
        

            return view('prepaid_rent.index', compact('lims_account_list', 'lims_warehouse_list', 'all_permission', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function prepaid_rentData(Request $request)
    {
 
       
        $warehouse_id = $request->input('warehouse_id');
        $q=PrePaidRent::where('is_active', 1);
        if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
           {
            $q=$q->where('user_id', Auth::id());
           }
        if($warehouse_id)
           {
            $q=$q->where('warehouse_id', $warehouse_id);
           }
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;

            
         if(empty($request->input('search.value'))) {
            $q = PrePaidRent::where('is_active', 1)
                ->limit($limit);
             if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
                $q = $q->where('user_id', Auth::id());
            if($warehouse_id)
                $q = $q->where('warehouse_id', $warehouse_id);
         $prepaid_rent = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = PrePaidRent::whereDate('prepaid_rent.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                 ->limit($limit);
             if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $prepaid_rent =  $q->select('prepaid_rent.*')
                                ->with('warehouse')
                                ->where('prepaid_rent.user_id', Auth::id())
                                ->orwhere([
                                    ['reference_no', 'LIKE', "%{$search}%"],
                                    ['user_id', Auth::id()]
                                ])
                                ->get();
                $totalFiltered = $q->where('prepaid_rent.user_id', Auth::id())->count();
            }
            else {
                $prepaid_rent =  $q->select('prepaid_rent.*')
                                ->with('warehouse')
                                ->orwhere('reference_no', 'LIKE', "%{$search}%")
                                ->get();

                $totalFiltered = $q->orwhere('prepaid_rent.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($prepaid_rent))
        {
            foreach ($prepaid_rent as $key=>$fa)
            {
                $nestedData['id'] = $fa->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($fa->created_at->toDateString()));
                $nestedData['reference_no'] = $fa->reference_no;
                $nestedData['name'] = $fa->name;
                $nestedData['warehouse'] = $fa->warehouse->name;
                // $nestedData['warehouse'] = "sasasa";
                // $nestedData['fixedAssetCategory'] = "xcxc";
                 $nestedData['qty'] =$fa->life_time;
                $nestedData['total_cost'] = number_format($fa->total_cost, 2);
               
                // $nestedData['options'] ="";
                $accumulatedExpense=$this->acumulatedDeperciation($fa->created_at,$fa->total_cost,$fa->life_time);
                $remaining=$fa->total_cost - $accumulatedExpense;
                $nestedData['rent_expense'] = number_format($accumulatedExpense, 2);
                $nestedData['accdep'] = number_format($remaining,2);
                  $nestedData['note'] = $fa->note;

                 $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';
                if(in_array("fixed_asset-edit", $request['all_permission'])) {
                    $nestedData['options'] .= '<li>
                        <button type="button" data-id="'.$fa->id.'" class="open-EditPrePaidRent_categoryDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i>'.trans('file.edit').'</button>
                        </li>';
                
                }
                 
                if(in_array("fixed_asset-delete", $request['all_permission']))
                    $nestedData['options'] .= \Form::open(["route" => ["prepaid_rent.destroy", $fa->id], "method" => "DELETE"] ).'
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
    
            if ($data['unit_cost'] <= 0 || $data['life_time'] <= 0) {
                return redirect('prepaid_rent')->with('not_permitted', 'Unit cost or Quantity can not be less than or equal to 0 ');
            }
    
            if ($data['name'] == "" || ctype_space($data['name']) || $data['name'] == null) {
                return redirect('prepaid_rent')->with('not_permitted', 'Name can not be empty ');
            }
    
            if (isset($data['created_at'])) {
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            } else {
                $data['created_at'] = date("Y-m-d H:i:s");
            }
    
            $data['reference_no'] = 'Prepaid_rent-' . date("Ymd") . '-' . date("his");
            $data['user_id'] = Auth::id();
            $data['total_cost'] =$data['unit_cost'];
    
            $lims_prepaid_rent_data = PrePaidRent::create($data);
    
            $dataad['user_id'] = Auth::id();
            $dataad['created_at'] = $data['created_at'] ;
            $dataad['warehouse_id'] = $data['warehouse_id'] ;
            $dataad['reference_no'] = $data['reference_no'] ;
            $dataad['reason'] = $data['reference_no'] .$data['note'] ;
            $dataad['is_adjustment'] = false ;
            $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);
        
             
            $transaction = new AccountTransaction;
            $transaction->reference_no = $data['reference_no'];
            $transaction->date = date("Y-m-d H:i:s");
            $transaction->user_id = Auth::id();
            $transaction->warehouse_id = $data['warehouse_id'];
            $transaction->debit = $data['total_cost'];
            $transaction->credit = 0;
            $accountType = ChartofAccount::where('name', "PrePaid Rent")->first();
            $transaction->chartof_accounts_id = $accountType->id;
            $transaction->prepaid_rent_id = $lims_prepaid_rent_data->id;
            $transaction->save();

            $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
            $journal_entry['chartof_accounts_id'] = $accountType->id;
            $journal_entry['account_transaction_id'] = $transaction->id;
            Journal_Entry::create($journal_entry);
    
            $transaction = new AccountTransaction;
            $transaction->reference_no = $data['reference_no'];
            $transaction->date = date("Y-m-d H:i:s");
            $transaction->user_id = Auth::id();
            $transaction->warehouse_id = $data['warehouse_id'];
            $transaction->debit = 0;
            $transaction->credit = $data['total_cost'];
    
            $account_name=Account::where('id',$data['account_id'])->first();
            $accountType = ChartofAccount::where('name', $account_name->cname)->first();
            $transaction->chartof_accounts_id = $accountType->id;
            $transaction->prepaid_rent_id = $lims_prepaid_rent_data->id;
            $transaction->save();

            $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
            $journal_entry['chartof_accounts_id'] = $accountType->id;
            $journal_entry['account_transaction_id'] = $transaction->id;
            Journal_Entry::create($journal_entry);
    
            DB::commit();
            return redirect('prepaid_rent')->with('message', 'Data inserted successfully');
        } catch (Exception $e) {
            DB::rollback();
            dd($e->getMessage());
            return redirect('prepaid_rent')->with('not_permitted', 'An error occurred while inserting data'.$e->getMessage());
        }
    }

    public function show($id)
    {
        //
    }
     public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('fixed_asset-edit')) {
            $lims_prepaid_rent_data = PrePaidRent::find($id);
            $lims_prepaid_rent_data->date = date('d-m-Y', strtotime($lims_prepaid_rent_data->created_at->toDateString()));
            return $lims_prepaid_rent_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {
        // $data = $request->all();
        //
        // 
        // $lims_prepaid_rent_data->update($data);
        // return redirect('prepaid_rent')->with('message', 'Data updated successfully');


        $data = $request->all();

        if($data['total_cost']<=0  || $data['life_time']<=0 )
        {
            redirect('prepaid_rent')->with('not_permitted', 'Total Paid Amount or Rent Duration can not be lesthan or Equals to 0 ');

        }

        if($data['name']=="" || ctype_space($data['name'])  || $data['name']== null )
        {
            redirect('prepaid_rent')->with('not_permitted', 'Name can not be empty ');

        }
        $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        $id = $data['prepaid_rent_id'];
         if ($data['name'] == "" || ctype_space($data['name']) || $data['name'] == null) {
            return redirect('prepaid_rent')->with('not_permitted', 'Name can not be empty ');
        }
    
        try {
            DB::beginTransaction();
    
            // Get the lims_prepaid_rent_data and lock the record for update
            $lims_prepaid_rent_data = PrePaidRent::where('id', $id)->lockForUpdate()->firstOrFail();
    
            // Check if the lims_prepaid_rent_data is a draft or approved
            if ($lims_prepaid_rent_data->isDraft()) {
                throw new Exception('PrePaidRent is waiting for Approval or rejection, you can not double update untill the Approval process is done!!
                </br>  Contact Your Admin');
            }
    
            // Get the original data before making any changes
            $originalData = $lims_prepaid_rent_data->getOriginal();
    
            // // Update the lims_prepaid_rent_data
            // $lims_prepaid_rent_data->update($data);
    
           
            
            // Get the original data before making any changes
                $originalData = $lims_prepaid_rent_data->getOriginal();

                // Get the attributes that exist in both $request and $originalData
                $commonAttributes = array_intersect_key($data, $originalData);

                
    
                if ($commonAttributes != $originalData) {
                // There is an update
                $lims_prepaid_rent_data->status = PrePaidRent::STATUS_DRAFT;
                $lims_prepaid_rent_data->updated_by = Auth::user()->id;
                $lims_prepaid_rent_data->save();
    
                // Log the status change and the old and new values
                activity()
                    ->performedOn($lims_prepaid_rent_data)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'old' => $originalData,
                        'new' => $commonAttributes,
                  
                     ])
                     ->tap(function ($activity) {
                        $activity->is_active = true; // Set the value of the `is_active` column
                        $activity->status = PrePaidRent::STATUS_DRAFT; // Set the value of the `is_active` column
                        $activity->url = "prepaid_rent"; // Set the value of the `is_active` column
                        $activity->is_root = 1; // Set the value of the `is_active` column

                    })
                    
                    ->log('PrePaidRent status updated');

 
                    $lims_account_transaction_data = AccountTransaction::where('prepaid_rent_id', $id)->get();
                    if ($lims_account_transaction_data->count() > 0) {
                        foreach ($lims_account_transaction_data as $k=>$account_transaction) {
                            // Get the original data before making any changes
                            
                         
                            $originalAccountTransactiontData = $account_transaction->getOriginal();
                             
                            if ($account_transaction->debit >0) {
     
                                $account_transaction->status = AccountTransaction::STATUS_DRAFT;
                                $account_transaction->updated_by = Auth::user()->id;
                                $account_transaction->debit = $data['total_cost'];
                                $account_transaction->credit =0;
                                 $accountType = ChartofAccount::where('name',  "PrePaid Rent")->first();
                                $account_transaction->chartof_accounts_id = $accountType->id;
                             } 
                             
                             if (number_format($account_transaction->credit) >0 ) {
                                $account_transaction->status = AccountTransaction::STATUS_DRAFT;
                                $account_transaction->updated_by = Auth::user()->id;
                                $account_transaction->credit = $data['total_cost'];
                                $account_transaction->debit =0;
                                $account_name=Account::where('id',$data['account_id'])->first();
                                $accountType = ChartofAccount::where('name', $account_name->cname)->first();
                                $account_transaction->chartof_accounts_id = $accountType->id;
                             }
                            $account_transaction->save();
                            $newData = $account_transaction->getAttributes();
                            $account_transaction->update($originalAccountTransactiontData);
    
                             // Log the status change and the old and new values
                            activity()
                            ->performedOn($account_transaction)
                            ->causedBy(Auth::user())
                            ->withProperties(['old' => $originalAccountTransactiontData, 'new' => $newData 
                            ])
                            ->tap(function ($activity) {
                                $activity->is_active = true; // Set the value of the `is_active` column
                                $activity->status = AccountTransaction::STATUS_DRAFT; // Set the value of the `is_active` column
                                $activity->url = "accounttransaction"; // Set the value of the `is_active` column
                                $activity->is_root = 0; // Set the value of the `is_active` column
        
                            })
                            ->log('AccountTransaction status updated due to PrePaidRent update');
    
                        }
                    }
                    
            }
    
             DB::commit();
             $undoUrl='prepaid_rent/restore/'.$id;
             return redirect('prepaid_rent')->with('message', 'PrePaidRent updated successfully, Please Wait for Approval or Contact System Administrator ')->with('undoUrl', $undoUrl);
    
     
      
    
      } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'PrePaidRent not found');
        } catch (QueryException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'PrePaidRent is being updated by another user. Please try again later.');
        }
        
        catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', $e->getMessage());
        }
    }

    // public function deleteBySelection(Request $request)
    // {
    //     try {
    //         DB::beginTransaction();
    
    //         $lims_prepaid_rent_array_data = array();
    //         $prepaid_rent_id = $request['expenseIdArray'];
    //         foreach ($prepaid_rent_id as $id) {
    //             $lims_prepaid_rent_data = PrePaidRent::find($id);
    //             if ($lims_prepaid_rent_data) {
    //                 if ($lims_prepaid_rent_data->isDraft()) {
    //                     throw new Exception('Pre Paid Rent Data with id ' . $lims_prepaid_rent_data->name . ' is in pending status and cannot be deleted. please contact your system Admin');
    //                 }
    //                 $lims_prepaid_rent_array_data[] = $lims_prepaid_rent_data;
    //                 $lims_prepaid_rent_data->delete();
    //             }
    //         }
    
    //         $subject = $lims_prepaid_rent_array_data[0];
    
    //         activity()
    //             ->causedBy(Auth::user())
    //             ->performedOn($subject)
    //             ->withProperties([
    //                 'old' => $lims_prepaid_rent_array_data,
    //                 'IdArray' => $request['expenseIdArray'],
    //             ])
    //             ->tap(function ($activity) {
    //                 $activity->is_active = true;
    //                 $activity->status = AccountTransaction::STATUS_DRAFT;
    //                 $activity->url = "prepaid_rent";
    //                 $activity->is_root = 1;
    //                 $activity->is_deleted = 1;
    //             })
    //             ->log('PrePaidRent Deleted');
    
    //         DB::commit();
    //         $undoUrl="prepaid_rent/restorebyselection";
    //         return redirect('prepaid_rent')->with('message', 'selected Pre Paid Rent Data Data deleted successfully, Please Wait for Approval or Contact System Administrator ')->with('deletebyselectionUrl', $undoUrl)->with('IdArray', $request['expenseIdArray']);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return redirect('prepaid_rent')->with('error', $e->getMessage());
    //     }
    // }
    

    // public function restoreBySelection(Request $request)
    // {
    //     try {
    //         DB::beginTransaction();
            
    //         $prepaid_rent_id = $request['IdArray'];
    //         foreach ($prepaid_rent_id as $id) {
    //             $lims_prepaid_rent_data = PrePaidRent::withTrashed()->where('id', $id)->first();
    //             if ($lims_prepaid_rent_data) {
    //                 $lims_prepaid_rent_data->restore();
    //             }
                
               
    //         }

    //     $logs = Activity::where('subject_type', PrePaidRent::class)
    //     ->where('subject_id', $prepaid_rent_id[0])
    //     ->where('status', PrePaidRent::STATUS_DRAFT)
    //     ->where('is_active', true)
    //     ->latest()
    //     ->firstOrFail();
        
    //     $logs->update(['is_active' => false]);
    //     $logs->update(['status' => PrePaidRent::STATUS_APPROVED]);
        
    //     activity()
    //         ->performedOn($lims_prepaid_rent_data)
    //         ->causedBy(Auth::user())
    //         ->log('PrePaidRent Restored');
    //         DB::commit();
    //         return redirect('prepaid_rent')->with('message', 'Data restored successfully');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return redirect('prepaid_rent')->with('not_permitted', 'error occured while restoring your data'.$e->getMessage());
    //     }
    // }
    

    // public function deletePermanentlyBySelection(Request $request)
    // {
    //     try {
    //         DB::beginTransaction();
    
    //         $prepaid_rent_id = $request['IdArray'];
    
    //         foreach ($prepaid_rent_id as $id) {
    //             $lims_prepaid_rent_data = PrePaidRent::find($id);
    //             $lims_prepaid_rent_data->forceDelete();
    //         }
    
    //         DB::commit();
    //         return redirect('prepaid_rent')->with('message', 'Data permanently deleted successfully');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return redirect('prepaid_rent')->with('not_permitted','error ocured while deleting your selected data'. $e->getMessage());
    //     }
    // }
    

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
    
            $lims_prepaid_rent_data = PrePaidRent::find($id);
    
            if($lims_prepaid_rent_data->isDraft()){
                throw new Exception('This Pre Paid Rent Data is  on pending status you can not delete it ');
            }

            $lims_prepaid_rent_data->delete();

            $lims_account_transaction_data = AccountTransaction::where('prepaid_rent_id',$id);
            foreach ($lims_account_transaction_data as $key => $transaction) {
                # code...
                $transaction->delete();
            }
    
            // Log the status change and the old and new values
            activity()
                ->performedOn($lims_prepaid_rent_data)
                ->causedBy(Auth::user())
                ->withProperties(['old' => $lims_prepaid_rent_data])
                ->tap(function ($activity) {
                    $activity->is_active = true; // Set the value of the `is_active` column
                    $activity->status = AccountTransaction::STATUS_DRAFT; // Set the value of the `is_active` column
                    $activity->url = "prepaid_rent"; // Set the value of the `is_active` column
                    $activity->is_root = 1; // Set the value of the `is_active` column
                    $activity->is_deleted = 1; // Set the value of the `is_active` column
                })
                ->log('PrePaidRent Deleted');
    
            DB::commit();
            $undoUrl = 'prepaid_rent/restore/'.$id;
            return redirect('prepaid_rent')->with('message', 'Pre Paid Rent Data Data deleted successfully, Please Wait for Approval or Contact System Administrator ')->with('deleteUrl', $undoUrl);
        } catch (Exception $e) {
            DB::rollBack();
            return redirect('prepaid_rent')->with('not_permitted', $e->getMessage());
        }
    }
    


    public function restore($id)
    {
        try {
            DB::beginTransaction();
    
            $lims_prepaid_rent_data = PrePaidRent::withTrashed()->where('id', $id)->first();
    
            if (!$lims_prepaid_rent_data) {
                throw new Exception('Pre Paid Rent Data not found.');
            }
    
            $lims_prepaid_rent_data->restore();
            $lims_account_transaction_data = AccountTransaction::where('prepaid_rent_id',$id);
            foreach ($lims_account_transaction_data as $key => $transaction) {
                # code...
                $transaction->restore();
            }
    
            if ($lims_prepaid_rent_data->isDraft()) {
                throw new Exception('Pre Paid Rent Data is not in approved status.');
            }
    
            $logs = Activity::where('subject_type', PrePaidRent::class)
                ->where('subject_id', $id)
                ->where('status', PrePaidRent::STATUS_DRAFT)
                ->where('is_active', true)
                ->latest()
                ->firstOrFail();
    
            $logs->update(['is_active' => false]);
            $logs->update(['status' => PrePaidRent::STATUS_APPROVED]);
            $lims_prepaid_rent_data->status = PrePaidRent::STATUS_APPROVED;
            $lims_prepaid_rent_data->save();
    
            activity()
                ->performedOn($lims_prepaid_rent_data)
                ->causedBy(Auth::user())
                ->withProperties(['old' => $lims_prepaid_rent_data])
                ->tap(function ($activity) {
                    $activity->is_active = false; // Set the value of the `is_active` column
                      $activity->is_root = 1; // Set the value of the `is_active` column
                    $activity->is_deleted =1; // Set the value of the `is_active` column
       
       
                })
                ->log('PrePaidRent Restored');
    
            DB::commit();
    
            return redirect('prepaid_rent')->with('message', 'Data restored successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect('prepaid_rent')->with('not_permitted', 'error occured while restoring your data'.$e->getMessage());
        }
    }
    

    public function Permanentdestroy($id)
    {
        try {
            DB::beginTransaction();
    
            $lims_prepaid_rent_data = PrePaidRent::find($id);
    
            if($lims_prepaid_rent_data->isDraft()){
                throw new Exception('Pre Paid Rent Data is not in deleted recently');
            }
    
            $lims_prepaid_rent_data->forceDelete();
    
            $logs = Activity::where('subject_type', PrePaidRent::class)
                ->where('subject_id', $id)
                ->where('status', PrePaidRent::STATUS_DRAFT)
                ->where('is_active', true)
                ->latest()
                ->firstOrFail();
            
            $logs->update(['is_active' => false]);
            $logs->update(['status' => PrePaidRent::STATUS_APPROVED]);
            $lims_prepaid_rent_data->status=PrePaidRent::STATUS_APPROVED;
            $lims_prepaid_rent_data->save();
    
            activity()
                ->performedOn($lims_prepaid_rent_data)
                ->causedBy(Auth::user())
                ->withProperties(['old' => $lims_prepaid_rent_data])
                ->tap(function ($activity) {
                    $activity->is_active = false; // Set the value of the `is_active` column
                      $activity->is_root = 1; // Set the value of the `is_active` column
                    $activity->is_deleted =1; // Set the value of the `is_active` column
       
       
                })
                ->log('PrePaidRent Deleted Permanently');
    
            DB::commit();
            return redirect('prepaid_rent')->with('not_permitted', 'Data Permanently deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect('prepaid_rent')->with('error', $e->getMessage());
        }
    }
    



    
    public function approve($id)
    {

        try {
            DB::beginTransaction();
    
            // Get the lims_prepaid_rent_data and lock the record for update
            $lims_prepaid_rent_data = PrePaidRent::where('id', $id)->firstOrFail();
            // Check if the lims_prepaid_rent_data is a draft
            if (!$lims_prepaid_rent_data->isDraft()) {
                throw new Exception('PrePaidRent is not in Update Status');
            }
    
            // Restore the original data
            $logs = Activity::where('subject_type', PrePaidRent::class)
            ->where('subject_id', $id)
            ->where('status', PrePaidRent::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $newData = $properties['new'];
            $lims_prepaid_rent_data->update($newData);
            $lims_prepaid_rent_data->status = PrePaidRent::STATUS_APPROVED;
            $lims_prepaid_rent_data->save();
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => PrePaidRent::STATUS_APPROVED]);

             // Update the related AccountTransaction records
            $lims_account_transaction_data = AccountTransaction::where('prepaid_rent_id', $id)->get();
              if ($lims_account_transaction_data->count() > 0) {
                foreach ($lims_account_transaction_data as $account_transaction) {
                        // Restore the original data
                    $logsaccount_transaction = Activity::where('subject_type', AccountTransaction::class)
                    ->where('subject_id', $account_transaction->id)
                    ->where('status', AccountTransaction::STATUS_DRAFT)
                    ->where('is_active',true)
                    ->latest()
                        ->firstOrFail();
                        $propertieslogsaccount_transaction = $logsaccount_transaction->properties;
                     $newData_account_transaction = $propertieslogsaccount_transaction['new'];
                    if ($account_transaction->debit>0) {
                        $account_transaction->update($newData_account_transaction);
                         $account_transaction->debit = $newData_account_transaction['debit'];
                         $account_transaction->save();

                     } else {
                        $account_transaction->update($newData_account_transaction);
                        $account_transaction->credit = $newData_account_transaction['credit'];
                        $account_transaction->save();

                    }
                    $logsaccount_transaction->update(['is_active' => false]);
                }
            }
    
            // Log the rejection
            activity()
            ->performedOn($lims_prepaid_rent_data)
            ->causedBy(Auth::user())
               ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = PrePaidRent::STATUS_APPROVED; // Set the value of the `is_active` column

            })
            
            ->log('PrePaidRent update approved');
        
    
            DB::commit();
    
            return redirect()->back()->with('message', 'PrePaidRent update Approved');
        } 
        
        catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'PrePaidRent not found');
        } catch (QueryException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'PrePaidRent is being updated by another user. Please try again later.');
        } 
        
        catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', $e->getMessage());
        }
    }

    public function reject($id)
{

    try {
        DB::beginTransaction();

        // Get the lims_prepaid_rent_data and lock the record for update
        $lims_prepaid_rent_data = PrePaidRent::where('id', $id)->firstOrFail();
        // Check if the lims_prepaid_rent_data is a draft
        if (!$lims_prepaid_rent_data->isDraft()) {
            throw new Exception('PrePaidRent is not a draft');
        }

        // Restore the original data
        $logs = Activity::where('subject_type', PrePaidRent::class)
        ->where('subject_id', $id)
        ->where('status', PrePaidRent::STATUS_DRAFT)
        ->where('is_active',true)
        ->latest()
        ->firstOrFail();
        $properties = $logs->properties;
        $oldData = $properties['old'];
        $lims_prepaid_rent_data->update($oldData);
        $lims_prepaid_rent_data->status = PrePaidRent::STATUS_REJECTED;
        $lims_prepaid_rent_data->save();

        $activity = Activity::find($logs->id);
        $activity->update(['is_active' => false]);
        $activity->update(['status' => PrePaidRent::STATUS_REJECTED]);


        // Update the related AccountTransaction records
        $lims_account_transaction_data = AccountTransaction::where('prepaid_rent_id', $id)->get();
          if ($lims_account_transaction_data->count() > 0) {
            foreach ($lims_account_transaction_data as $account_transaction) {
                    // Restore the original data
                $logsaccount_transaction = Activity::where('subject_type', AccountTransaction::class)
                ->where('subject_id', $account_transaction->id)
                ->where('status', AccountTransaction::STATUS_DRAFT)
                ->where('is_active',true)
                ->latest()
                    ->firstOrFail();
                    $propertieslogsaccount_transaction = $logsaccount_transaction->properties;
                 $oldData_account_transaction = $propertieslogsaccount_transaction['old'];
                if ($account_transaction->debit>0) {
                    $account_transaction->update($oldData_account_transaction);
                     $account_transaction->debit = $oldData_account_transaction['debit'];
                     $account_transaction->save();

                 } else {
                    $account_transaction->update($oldData_account_transaction);
                    $account_transaction->credit = $oldData_account_transaction['credit'];
                    $account_transaction->save();

                }
                $logsaccount_transaction->update(['is_active' => false]);
            }
        }

        // Log the rejection
        activity()
        ->performedOn($lims_prepaid_rent_data)
        ->causedBy(Auth::user())
           ->tap(function ($activity) {
            $activity->is_active = false; // Set the value of the `is_active` column
            $activity->status = PrePaidRent::STATUS_REJECTED; // Set the value of the `is_active` column

        })
        
        ->log('PrePaidRent update Rejected');
    

        DB::commit();

        return redirect()->back()->with('not_permitted', 'PrePaidRent update Rejected');
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', 'PrePaidRent not found');
    } catch (QueryException $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', 'PrePaidRent is being updated by another user. Please try again later.');
    } catch (Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', $e->getMessage());
    }
}

 function calculateDeperciation(){
    $monthlyDepreciation = ((360 * 5) / 60) / 30;
    $purchaseDate = new DateTime('2022-06-06');
    
    // convert the purchase date and end date to Ethiopian Calendar
    $startDateEthiopian = new AndegnaDateTime($purchaseDate);
    $endDateEthiopian = new AndegnaDateTime(new DateTime());
    
    // initialize variables
    $accumulatedDepreciation = 0;
    $depreciationData = [];
    
    // set the current date to the purchase date
     $currentDateEthiopian = clone new AndegnaDateTime($purchaseDate);
    
    // calculate monthly depreciation for each month from the purchase date to the end date
    $prev=0;
    $ispagume=false;
    while ($currentDateEthiopian <= $endDateEthiopian) {
        $isnehase=false;
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
            $numDays = 30 - $startDateEthiopian->getDay();
        } elseif (  $currentDateEthiopian->getYear() == $endDateEthiopian->getYear() && $currentDateEthiopian->getMonth() == $endDateEthiopian->getMonth()) 
        {
            dd($endDateEthiopian->getYear());

            // last month
             $numDays = $endDateEthiopian->getDay();
        } else {
            // full month

            $numDays = 30;
        }
    
        // calculate the depreciation for the current month
        $depreciation = $numDays * 1;
    
        // add the depreciation to the accumulated depreciation
        $accumulatedDepreciation += $depreciation;
        if ($ispagume) {
            # code...
            $numDays=29;
        $date = clone $currentDateEthiopian;

        $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
        $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
        $depreciationDateEthiopian->add(new DateInterval('P'.$numDays.'D'));

        }elseif ($currentDateEthiopian->getMonth() == 12) {
        # code...
        $date = clone $currentDateEthiopian;
        $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
        $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
        $depreciationDateEthiopian->add(new DateInterval('P1Y'));
        $depreciationDateEthiopian->setDate($depreciationDateEthiopian->getYear(),1,30);
        $currentDateEthiopian->add(new DateInterval('P1Y'));
        $currentDateEthiopian->setDate($currentDateEthiopian->getYear(),1,30);
        $ispagume=true;   
        $isnehase=true;
        } else {
            # code...
        $date = clone $currentDateEthiopian;
        $depreciationDate = new DateTime($date->toGregorian()->format('Y-m-d'));
        $depreciationDateEthiopian = new AndegnaDateTime($depreciationDate);
        $depreciationDateEthiopian->add(new DateInterval('P'.$numDays.'D'));
        }
        
        // set the depreciation date
        
    
        // add the depreciation data for the current month to the depreciation data array
        $depreciationData[] = [
            'depreciation_date' => $depreciationDateEthiopian,
            'depreciation' => $depreciation,
            'accumulated_depreciation' => $accumulatedDepreciation,
        ];
         $ispagume=false;
       if ($isnehase==false) {
        # code...
        $currentDateEthiopian->add(new DateInterval('P'.$numDays.'D'));

       }
    }
 }


 function acumulatedDeperciation($purchasedate,$totalCost, $life_time){

   
    $cost=$totalCost;
    $yearlyDepreciation=$cost/$life_time;
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
        $islastdate=false;

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
        } else if ($currentDateEthiopian->getYear() == $endDateEthiopian->getYear() && $currentDateEthiopian->getMonth() == $endDateEthiopian->getMonth()) 
        {
            // last month
 
             $numDays = 30;
             $islastdate=true;
             $isnumDayschanged=false;



        } else {
            // full month
            $numDays = 30;

        }

        if ($currentDateEthiopian->getYear() == $endDateEthiopian->getYear() && $currentDateEthiopian->getMonth() == $endDateEthiopian->getMonth()) 
        {
            // last month
               $islastdate=true;

               $isnumDayschanged=false;


        }
        // calculate the depreciation for the current month
        if($isnumDayschanged==true){
            $num= $numDays + 1;
            $depreciation = $num* $dailyDepreciation;

        }elseif ($islastdate==true) {
            # code...
            $depreciation = $endDateEthiopian->getDay() * $dailyDepreciation;

        }else {
            $depreciation = $numDays* $dailyDepreciation;

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
    return $accumulatedDepreciation;

 }





}
