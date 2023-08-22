<?php

namespace App\Http\Controllers;
use Andegna\DateTime as AndegnaDateTime;
use App\Account;
 use App\AccountTransaction;
use App\AccountTransactionAdjustment;
use App\ChartofAccount;
use App\FixedAsset;
 use App\FixedAssetCategory;
use App\GeneralSetting;
use App\Journal_Entry;
use App\Payment;
use App\PaymentWithCheque;
use App\PaymentWithCreditCard;
use App\PaymentWithGiftCard;
use App\PaymentWithMobile;
use App\PaymentWithPOSATM;
use App\PosSetting;
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

class FixedAssetController extends Controller
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
            return view('fixed_asset.index', compact('lims_account_list', 'lims_warehouse_list', 'all_permission', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function fixed_assetData(Request $request)
    {
        $warehouse_id = $request->input('warehouse_id');
        $q = FixedAsset::where('is_active', 1);
    
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $q->where('user_id', Auth::id());
        }
    
        if ($warehouse_id) {
            $q->where('warehouse_id', $warehouse_id);
        }
    
        $totalData = $q->count();
        $totalFiltered = $totalData;
    
        $limit = $request->input('length', $totalData);
        $search = $request->input('search.value');
    
        if (!empty($search)) {
            $q->where(function ($query) use ($search) {
                $query->where('reference_no', 'LIKE', "%{$search}%");
    
                if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                    $query->where('user_id', Auth::id());
                }
            });
    
            $totalFiltered = $q->count();
        }
    
        $fixed_assets = $q->with('warehouse', 'fixedAssetCategory')
            ->skip($request->input('start', 0))
            ->take($limit)
            ->get();
    
        $data = [];
    
        foreach ($fixed_assets as $key => $fa) {
            $nestedData['id'] = $fa->id;
            $nestedData['key'] = $key;
            $nestedData['date'] = date(config('date_format'), strtotime($fa->created_at->toDateString()));
            $nestedData['reference_no'] = $fa->reference_no;
            $nestedData['name'] = $fa->name;
            $nestedData['warehouse'] = $fa->warehouse->name;
            $nestedData['fixedAssetCategory'] = $fa->fixedAssetCategory->name;
            $nestedData['qty'] = $fa->qty;
            $nestedData['unit_cost'] = number_format($fa->unit_cost, 2);
            $nestedData['total_cost'] = number_format($fa->total_cost, 2);
            $nestedData['paid_amount'] = number_format($fa->paid_amount, 2);
            $due = $fa->total_cost - $fa->paid_amount;
            $nestedData['due'] = number_format($due, 2);
            $nestedData['payment_status'] = $fa->payment_status == 1 ? '<div class="badge badge-danger">'.trans('file.Due').'</div>' : '<div class="badge badge-success">'.trans('file.Paid').'</div>';
            $nestedData['note'] = $fa->note;
            $accumulated = $this->acumulatedDeperciation($fa->created_at, $fa->total_cost, $fa->fixed_asset_category_id);
            $nestedData['accdep'] = number_format($accumulated, 2);
            $nbv = $fa->total_cost - floatval($accumulated);
            $nestedData['nbv'] = number_format($nbv, 2);
            $nestedData['options'] = '<div class="btn-group">
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                  <span class="caret"></span>
                  <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';
    
            if (in_array("fixed_asset-edit", $request['all_permission'])) {
                $nestedData['options'] .= '<li>
                    <button type="button" data-id="'.$fa->id.'" class="open-EditFixedAsset_categoryDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i>'.trans('file.edit').'</button>
                    </li>';
            }
    
            if (in_array("purchase-payment-index", $request['all_permission'])) {
                $nestedData['options'] .= 
                    '<li>
                        <button type="button" class="get-payment btn btn-link" data-id="'.$fa->id.'"><i class="fa fa-money"></i> '.trans('file.View Payment').'</button>
                    </li>';
            }
    
            if (in_array("purchase-payment-add", $request['all_permission'])) {
                $nestedData['options'] .= 
                    '<li>
                        <button type="button" class="add-payment btn btn-link" data-id="'.$fa->id.'" data-toggle="modal" data-target="#add-payment"><i class="fa fa-plus"></i> '.trans('file.Add Payment').'</button>
                    </li>';
            }
    
            if (in_array("fixed_asset-delete", $request['all_permission'])) {
                $nestedData['options'] .= \Form::open(["route" => ["fixed_asset.destroy", $fa->id], "method" => "DELETE"] ).'
                        <li>
                          <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.trans("file.delete").'</button> 
                        </li>'.\Form::close().'
                    </ul>
                </div>';
            }
    
            $data[] = $nestedData;
        }
    
        $json_data = [
            "draw" => intval($request->input('draw', 1)),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];
    
        return response()->json($json_data);
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
    
            if ($data['unit_cost'] <= 0 || $data['qty'] <= 0) {
                return redirect('fixed_asset')->with('not_permitted', 'Unit cost or Quantity can not be less than or equal to 0 ');
            }
    
            if ($data['name'] == "" || ctype_space($data['name']) || $data['name'] == null) {
                return redirect('fixed_asset')->with('not_permitted', 'Name can not be empty ');
            }
    
            if (isset($data['created_at'])) {
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            } else {
                $data['created_at'] = date("Y-m-d H:i:s");
            }
    
            $data['reference_no'] = 'Fixes-Asset-' . date("Ymd") . '-' . date("his");
            $data['user_id'] = Auth::id();
            $data['total_cost'] = $data['qty'] * $data['unit_cost'];
    
            $lims_fixed_asset_data = FixedAsset::create($data);
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
            $account_name = FixedAssetCategory::where('id', $data['fixed_asset_category_id'])->first();
            $accountType = ChartofAccount::where('name', $account_name->name)->first();
            $transaction->chartof_accounts_id = $accountType->id;
            $transaction->fixed_asset_id = $lims_fixed_asset_data->id;
            $transaction->save();

            $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
            $journal_entry['chartof_accounts_id'] =$accountType->id;
            $journal_entry['account_transaction_id'] = $transaction->id;
            Journal_Entry::create($journal_entry);

    
            $transaction = new AccountTransaction;
            $transaction->reference_no = $data['reference_no'];
            $transaction->date = date("Y-m-d H:i:s");
            $transaction->user_id = Auth::id();
            $transaction->warehouse_id = $data['warehouse_id'];
            $transaction->debit = 0;
            $transaction->credit = $data['total_cost'];
    
            $accountType = ChartofAccount::where('name',"Accounts Payable")->first();
            $transaction->chartof_accounts_id = $accountType->id;
            $transaction->fixed_asset_id = $lims_fixed_asset_data->id;
            $transaction->save();

            $journal_entry['lims_AccountTransactionAdjustment_id'] =$lims_AccountTransactionAdjustment_data->id;
            $journal_entry['chartof_accounts_id'] =$accountType->id;
            $journal_entry['account_transaction_id'] = $transaction->id;
            Journal_Entry::create($journal_entry);
    
            DB::commit();
            return redirect('fixed_asset')->with('message', 'Data inserted successfully');
        } catch (Exception $e) {
            DB::rollback();
            dd($e->getMessage());
            return redirect('fixed_asset')->with('not_permitted', 'An error occurred while inserting data'.$e->getMessage());
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
            $lims_fixed_asset_data = FixedAsset::find($id);
            $lims_fixed_asset_data->date = date('d-m-Y', strtotime($lims_fixed_asset_data->created_at->toDateString()));
            return $lims_fixed_asset_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {
        // $data = $request->all();
        //
        // 
        // $lims_fixed_asset_data->update($data);
        // return redirect('fixed_asset')->with('message', 'Data updated successfully');


        $data = $request->all();

        if($data['unit_cost']<=0  || $data['qty']<=0 )
        {
            redirect('fixed_asset')->with('not_permitted', 'Unit cost or Quantity can not be lesthan oe Equals to 0 ');

        }

        if($data['name']=="" || ctype_space($data['name'])  || $data['name']== null )
        {
            redirect('fixed_asset')->with('not_permitted', 'Name can not be empty ');

        }
         $data['total_cost'] =  $data['qty']* $data['unit_cost'];
        $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        $id = $data['fixed_asset_id'];
         if ($data['name'] == "" || ctype_space($data['name']) || $data['name'] == null) {
            return redirect('fixed_asset')->with('not_permitted', 'Name can not be empty ');
        }
    
        try {
            DB::beginTransaction();
    
            // Get the lims_fixed_asset_data and lock the record for update
            $lims_fixed_asset_data = FixedAsset::where('id', $id)->lockForUpdate()->firstOrFail();
    
            // Check if the lims_fixed_asset_data is a draft or approved
            if ($lims_fixed_asset_data->isDraft()) {
                throw new Exception('FixedAsset is waiting for Approval or rejection, you can not double update untill the Approval process is done!!
                </br>  Contact Your Admin');
            }
    
            // Get the original data before making any changes
            $originalData = $lims_fixed_asset_data->getOriginal();
    
            // // Update the lims_fixed_asset_data
            // $lims_fixed_asset_data->update($data);
    
           
            
            // Get the original data before making any changes
                $originalData = $lims_fixed_asset_data->getOriginal();

                // Get the attributes that exist in both $request and $originalData
                $commonAttributes = array_intersect_key($data, $originalData);

                
    
                if ($commonAttributes != $originalData) {
                // There is an update
                $lims_fixed_asset_data->status = FixedAsset::STATUS_DRAFT;
                $lims_fixed_asset_data->updated_by = Auth::user()->id;
                $lims_fixed_asset_data->save();
    
                // Log the status change and the old and new values
                activity()
                    ->performedOn($lims_fixed_asset_data)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'old' => $originalData,
                        'new' => $commonAttributes,
                  
                     ])
                     ->tap(function ($activity) {
                        $activity->is_active = true; // Set the value of the `is_active` column
                        $activity->status = FixedAsset::STATUS_DRAFT; // Set the value of the `is_active` column
                        $activity->url = "fixed_asset"; // Set the value of the `is_active` column
                        $activity->is_root = 1; // Set the value of the `is_active` column

                    })
                    
                    ->log('FixedAsset status updated');

 
                    $lims_account_transaction_data = AccountTransaction::where('fixed_asset_id', $id)->get();
                    if ($lims_account_transaction_data->count() > 0) {
                        foreach ($lims_account_transaction_data as $k=>$account_transaction) {
                            // Get the original data before making any changes
                            
                         
                            $originalAccountTransactiontData = $account_transaction->getOriginal();
                             
                            if ($account_transaction->debit >0) {
     
                                $account_transaction->status = AccountTransaction::STATUS_DRAFT;
                                $account_transaction->updated_by = Auth::user()->id;
                                $account_transaction->debit = $data['total_cost'];
                                $account_transaction->credit =0;
                                $account_name=FixedAssetCategory::where('id',  $data['fixed_asset_category_id'])->first();
                                $accountType = ChartofAccount::where('name',  $account_name->name)->first();
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
                            ->log('AccountTransaction status updated due to FixedAsset update');
    
                        }
                    }
                    
            }
    
             DB::commit();
             $undoUrl='fixed_asset/restore/'.$id;
             return redirect('fixed_asset')->with('message', 'FixedAsset updated successfully, Please Wait for Approval or Contact System Administrator ')->with('undoUrl', $undoUrl);
    
     
      
    
      } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'FixedAsset not found');
        } catch (QueryException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'FixedAsset is being updated by another user. Please try again later.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', $e->getMessage());
        }
    }

    // public function deleteBySelection(Request $request)
    // {
    //     try {
    //         DB::beginTransaction();
    
    //         $lims_fixed_asset_array_data = array();
    //         $fixed_asset_id = $request['expenseIdArray'];
    //         foreach ($fixed_asset_id as $id) {
    //             $lims_fixed_asset_data = FixedAsset::find($id);
    //             if ($lims_fixed_asset_data) {
    //                 if ($lims_fixed_asset_data->isDraft()) {
    //                     throw new Exception('Fixed Asset with id ' . $lims_fixed_asset_data->name . ' is in pending status and cannot be deleted. please contact your system Admin');
    //                 }
    //                 $lims_fixed_asset_array_data[] = $lims_fixed_asset_data;
    //                 $lims_fixed_asset_data->delete();
    //             }
    //         }
    
    //         $subject = $lims_fixed_asset_array_data[0];
    
    //         activity()
    //             ->causedBy(Auth::user())
    //             ->performedOn($subject)
    //             ->withProperties([
    //                 'old' => $lims_fixed_asset_array_data,
    //                 'IdArray' => $request['expenseIdArray'],
    //             ])
    //             ->tap(function ($activity) {
    //                 $activity->is_active = true;
    //                 $activity->status = AccountTransaction::STATUS_DRAFT;
    //                 $activity->url = "fixed_asset";
    //                 $activity->is_root = 1;
    //                 $activity->is_deleted = 1;
    //             })
    //             ->log('FixedAsset Deleted');
    
    //         DB::commit();
    //         $undoUrl="fixed_asset/restorebyselection";
    //         return redirect('fixed_asset')->with('message', 'selected Fixed Asset Data deleted successfully, Please Wait for Approval or Contact System Administrator ')->with('deletebyselectionUrl', $undoUrl)->with('IdArray', $request['expenseIdArray']);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return redirect('fixed_asset')->with('error', $e->getMessage());
    //     }
    // }
    

    // public function restoreBySelection(Request $request)
    // {
    //     try {
    //         DB::beginTransaction();
            
    //         $fixed_asset_id = $request['IdArray'];
    //         foreach ($fixed_asset_id as $id) {
    //             $lims_fixed_asset_data = FixedAsset::withTrashed()->where('id', $id)->first();
    //             if ($lims_fixed_asset_data) {
    //                 $lims_fixed_asset_data->restore();
    //             }
                
               
    //         }

    //     $logs = Activity::where('subject_type', FixedAsset::class)
    //     ->where('subject_id', $fixed_asset_id[0])
    //     ->where('status', FixedAsset::STATUS_DRAFT)
    //     ->where('is_active', true)
    //     ->latest()
    //     ->firstOrFail();
        
    //     $logs->update(['is_active' => false]);
    //     $logs->update(['status' => FixedAsset::STATUS_APPROVED]);
        
    //     activity()
    //         ->performedOn($lims_fixed_asset_data)
    //         ->causedBy(Auth::user())
    //         ->log('FixedAsset Restored');
    //         DB::commit();
    //         return redirect('fixed_asset')->with('message', 'Data restored successfully');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return redirect('fixed_asset')->with('not_permitted', 'error occured while restoring your data'.$e->getMessage());
    //     }
    // }
    

    // public function deletePermanentlyBySelection(Request $request)
    // {
    //     try {
    //         DB::beginTransaction();
    
    //         $fixed_asset_id = $request['IdArray'];
    
    //         foreach ($fixed_asset_id as $id) {
    //             $lims_fixed_asset_data = FixedAsset::find($id);
    //             $lims_fixed_asset_data->forceDelete();
    //         }
    
    //         DB::commit();
    //         return redirect('fixed_asset')->with('message', 'Data permanently deleted successfully');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return redirect('fixed_asset')->with('not_permitted','error ocured while deleting your selected data'. $e->getMessage());
    //     }
    // }
    

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
    
            $lims_fixed_asset_data = FixedAsset::find($id);
    
            if($lims_fixed_asset_data->isDraft()){
                throw new Exception('This Fixed Asset is  on pending status you can not delete it ');
            }

            $lims_fixed_asset_data->delete();

            $lims_account_transaction_data = AccountTransaction::where('fixed_asset_id',$id);
            foreach ($lims_account_transaction_data as $key => $transaction) {
                # code...
                $transaction->delete();
            }
    
            // Log the status change and the old and new values
            activity()
                ->performedOn($lims_fixed_asset_data)
                ->causedBy(Auth::user())
                ->withProperties(['old' => $lims_fixed_asset_data])
                ->tap(function ($activity) {
                    $activity->is_active = true; // Set the value of the `is_active` column
                    $activity->status = AccountTransaction::STATUS_DRAFT; // Set the value of the `is_active` column
                    $activity->url = "fixed_asset"; // Set the value of the `is_active` column
                    $activity->is_root = 1; // Set the value of the `is_active` column
                    $activity->is_deleted = 1; // Set the value of the `is_active` column
                })
                ->log('FixedAsset Deleted');
    
            DB::commit();
            $undoUrl = 'fixed_asset/restore/'.$id;
            return redirect('fixed_asset')->with('message', 'Fixed Asset Data deleted successfully, Please Wait for Approval or Contact System Administrator ')->with('deleteUrl', $undoUrl);
        } catch (Exception $e) {
            DB::rollBack();
            return redirect('fixed_asset')->with('not_permitted', $e->getMessage());
        }
    }
    


    public function restore($id)
    {
        try {
            DB::beginTransaction();
    
            $lims_fixed_asset_data = FixedAsset::withTrashed()->where('id', $id)->first();
    
            if (!$lims_fixed_asset_data) {
                throw new Exception('Fixed Asset not found.');
            }
    
            $lims_fixed_asset_data->restore();
            $lims_account_transaction_data = AccountTransaction::where('fixed_asset_id',$id);
            foreach ($lims_account_transaction_data as $key => $transaction) {
                # code...
                $transaction->restore();
            }
    
            if ($lims_fixed_asset_data->isDraft()) {
                throw new Exception('Fixed Asset is not in approved status.');
            }
    
            $logs = Activity::where('subject_type', FixedAsset::class)
                ->where('subject_id', $id)
                ->where('status', FixedAsset::STATUS_DRAFT)
                ->where('is_active', true)
                ->latest()
                ->firstOrFail();
    
            $logs->update(['is_active' => false]);
            $logs->update(['status' => FixedAsset::STATUS_APPROVED]);
            $lims_fixed_asset_data->status = FixedAsset::STATUS_APPROVED;
            $lims_fixed_asset_data->save();
    
            activity()
                ->performedOn($lims_fixed_asset_data)
                ->causedBy(Auth::user())
                ->withProperties(['old' => $lims_fixed_asset_data])
                ->tap(function ($activity) {
                    $activity->is_active = false; // Set the value of the `is_active` column
                      $activity->is_root = 1; // Set the value of the `is_active` column
                    $activity->is_deleted =1; // Set the value of the `is_active` column
       
       
                })
                ->log('FixedAsset Restored');
    
            DB::commit();
    
            return redirect('fixed_asset')->with('message', 'Data restored successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect('fixed_asset')->with('not_permitted', 'error occured while restoring your data'.$e->getMessage());
        }
    }
    

    public function Permanentdestroy($id)
    {
        try {
            DB::beginTransaction();
    
            $lims_fixed_asset_data = FixedAsset::find($id);
    
            if($lims_fixed_asset_data->isDraft()){
                throw new Exception('Fixed Asset is not in deleted recently');
            }
    
            $lims_fixed_asset_data->forceDelete();
    
            $logs = Activity::where('subject_type', FixedAsset::class)
                ->where('subject_id', $id)
                ->where('status', FixedAsset::STATUS_DRAFT)
                ->where('is_active', true)
                ->latest()
                ->firstOrFail();
            
            $logs->update(['is_active' => false]);
            $logs->update(['status' => FixedAsset::STATUS_APPROVED]);
            $lims_fixed_asset_data->status=FixedAsset::STATUS_APPROVED;
            $lims_fixed_asset_data->save();
    
            activity()
                ->performedOn($lims_fixed_asset_data)
                ->causedBy(Auth::user())
                ->withProperties(['old' => $lims_fixed_asset_data])
                ->tap(function ($activity) {
                    $activity->is_active = false; // Set the value of the `is_active` column
                      $activity->is_root = 1; // Set the value of the `is_active` column
                    $activity->is_deleted =1; // Set the value of the `is_active` column
       
       
                })
                ->log('FixedAsset Deleted Permanently');
    
            DB::commit();
            return redirect('fixed_asset')->with('not_permitted', 'Data Permanently deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect('fixed_asset')->with('error', $e->getMessage());
        }
    }
    



    
    public function approve($id)
    {

        try {
            DB::beginTransaction();
    
            // Get the lims_fixed_asset_data and lock the record for update
            $lims_fixed_asset_data = FixedAsset::where('id', $id)->firstOrFail();
            // Check if the lims_fixed_asset_data is a draft
            if (!$lims_fixed_asset_data->isDraft()) {
                throw new Exception('FixedAsset is not in Update Status');
            }
    
            // Restore the original data
            $logs = Activity::where('subject_type', FixedAsset::class)
            ->where('subject_id', $id)
            ->where('status', FixedAsset::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $newData = $properties['new'];
            $lims_fixed_asset_data->update($newData);
            $lims_fixed_asset_data->status = FixedAsset::STATUS_APPROVED;
            $lims_fixed_asset_data->save();
    
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => FixedAsset::STATUS_APPROVED]);

    
            // Update the related AccountTransaction records
            $lims_account_transaction_data = AccountTransaction::where('fixed_asset_id', $id)->get();
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
            ->performedOn($lims_fixed_asset_data)
            ->causedBy(Auth::user())
               ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = FixedAsset::STATUS_APPROVED; // Set the value of the `is_active` column

            })
            
            ->log('FixedAsset update approved');
        
    
            DB::commit();
    
            return redirect()->back()->with('message', 'FixedAsset update Approved');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'FixedAsset not found');
        } catch (QueryException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'FixedAsset is being updated by another user. Please try again later.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', $e->getMessage());
        }
    }

    public function reject($id)
{

    try {
        DB::beginTransaction();

        // Get the lims_fixed_asset_data and lock the record for update
        $lims_fixed_asset_data = FixedAsset::where('id', $id)->firstOrFail();
        // Check if the lims_fixed_asset_data is a draft
        if (!$lims_fixed_asset_data->isDraft()) {
            throw new Exception('FixedAsset is not a draft');
        }

        // Restore the original data
        $logs = Activity::where('subject_type', FixedAsset::class)
        ->where('subject_id', $id)
        ->where('status', FixedAsset::STATUS_DRAFT)
        ->where('is_active',true)
        ->latest()
        ->firstOrFail();
        $properties = $logs->properties;
        $oldData = $properties['old'];
        $lims_fixed_asset_data->update($oldData);
        $lims_fixed_asset_data->status = FixedAsset::STATUS_REJECTED;
        $lims_fixed_asset_data->save();

        $activity = Activity::find($logs->id);
        $activity->update(['is_active' => false]);
        $activity->update(['status' => FixedAsset::STATUS_REJECTED]);


        // Update the related AccountTransaction records
        $lims_account_transaction_data = AccountTransaction::where('fixed_asset_id', $id)->get();
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
        ->performedOn($lims_fixed_asset_data)
        ->causedBy(Auth::user())
           ->tap(function ($activity) {
            $activity->is_active = false; // Set the value of the `is_active` column
            $activity->status = FixedAsset::STATUS_REJECTED; // Set the value of the `is_active` column

        })
        
        ->log('FixedAsset update Rejected');
    

        DB::commit();

        return redirect()->back()->with('not_permitted', 'FixedAsset update rejected');
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', 'FixedAsset not found');
    } catch (QueryException $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', 'FixedAsset is being updated by another user. Please try again later.');
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







 

 public function addPayment(Request $request)
 {
         try 
         {


            DB::beginTransaction();
            $data = $request->all();

            if ($data['amount']<=0) {
                # code...
                throw new Exception('Paying Amount can not be zero');
            }
        $lims_fixed_asset_data = FixedAsset::find($data['fixed_asset_id']);
        if ($lims_fixed_asset_data->isDraft()) {
            throw new Exception('FixedAsset is waiting for Approval or rejection, you can not double update untill the Approval process is done!!
            </br>  Contact Your Admin');
        }

         $lims_fixed_asset_data->paid_amount += $data['amount'];
        $balance = $lims_fixed_asset_data->total_cost - $lims_fixed_asset_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_fixed_asset_data->payment_status = 1;
        elseif ($balance == 0)
            $lims_fixed_asset_data->payment_status = 2;
        $lims_fixed_asset_data->save();

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
        $lims_payment_data->fixed_asset_id = $lims_fixed_asset_data->id;
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->payment_reference = 'Fixed_asset_payment-' . date("Ymd") . '-'. date("his");
        $lims_payment_data->amount = $data['amount'];
        $lims_payment_data->change = $data['paying_amount'] - $data['amount'];
        $lims_payment_data->paying_method = $paying_method;
        $lims_payment_data->payment_note = $data['payment_note'];
        $lims_payment_data->save();
        $lims_payment_data = Payment::latest()->first();
        $data['payment_id'] = $lims_payment_data->id;

        $dataad['user_id'] = Auth::id();
        $dataad['created_at'] = $lims_payment_data->created_at ;
        $dataad['warehouse_id'] = $lims_fixed_asset_data->warehouse_id ;
        $dataad['reference_no'] = $lims_payment_data->reference_no;
        $dataad['reason'] = $lims_payment_data->reference_no.$data['note'] ;
        $dataad['is_adjustment'] = false ;
        $lims_AccountTransactionAdjustment_data = AccountTransactionAdjustment::create($dataad);

        $transaction = new AccountTransaction;
        $transaction->reference_no = $lims_fixed_asset_data['reference_no'] ;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id	 = Auth::id();
        $transaction->warehouse_id = $lims_fixed_asset_data->warehouse_id; 
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
        $transaction->reference_no = $lims_fixed_asset_data['reference_no'] ;
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id	 = Auth::id();
        $transaction->warehouse_id = $lims_fixed_asset_data->warehouse_id; 
        $transaction->debit = $data['amount'];
        $transaction->credit = 0;
        $accountType = ChartofAccount::where('name',"Accounts Payable")->first();
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
                    
                    ->log('New Payment data Inserted');
                
        
        DB::commit(); 
        return redirect('fixed_asset')->with('message', 'Payment created successfully');
 
    } catch (ModelNotFoundException $e) {
       DB::rollBack();
       return redirect()->back()->with('not_permitted', 'Payment not found');
   }  catch (Exception $e) {
       DB::rollBack();
       return redirect()->back()->with('not_permitted', $e->getMessage());
   }
    }

    public function getPayment($id)
    {
        $lims_payment_list = Payment::where('fixed_asset_id', $id)->get();
        $date = [];
        $payment_reference = [];
        $paid_amount = [];
        $paying_method = [];
        $payment_id = [];
        $payment_note = [];
        $cheque_no = [];
        $change = [];
        $paying_amount = [];
        $account_name = [];
        $account_id = [];
        $cheque_bank = [];

        foreach ($lims_payment_list as $payment) {
            $date[] = date(config('date_format'), strtotime($payment->created_at->toDateString())) . ' '. $payment->created_at->toTimeString();
            $payment_reference[] = $payment->payment_reference;
            $paid_amount[] = $payment->amount;
            $change[] = $payment->change;
            $paying_method[] = $payment->paying_method;
            $paying_amount[] = $payment->amount + $payment->change;
            if ($payment->paying_method == 'Gift Card') {
                $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                $gift_card_id[] = $lims_payment_gift_card_data->gift_card_id;
            } elseif ($payment->paying_method == 'Cheque') {
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                $cheque_no[] = $lims_payment_cheque_data->cheque_no;
                $cheque_bank[] = $lims_payment_cheque_data->cheque_bank;

            }elseif ($payment->paying_method == 'Mobile') {
                $lims_payment_mobile_data = PaymentWithMobile::where('payment_id', $payment->id)->first();
                $mbtn_no[] = $lims_payment_mobile_data->mbtn_no;
                $mobile_bank[] = $lims_payment_mobile_data->mobile_bank;
                // dd( $lims_payment_mobile_data);
                $cheque_no[] = $cheque_bank[]  = $gift_card_id[] = " ";

            }elseif ($payment->paying_method == 'POS ATM') {
                $lims_payment_mobile_data = PaymentWithPOSATM::where('payment_id', $payment->id)->first();
                 $mobile_bank[] = $lims_payment_mobile_data->pos_bank;
                // dd( $lims_payment_mobile_data);
                $cheque_no[] = $cheque_bank[]  = $mbtn_no[]= $gift_card_id[] = " ";

            }  else {
                $cheque_no[] = $cheque_bank[]  = $mbtn_no[]=$mobile_bank[]= $gift_card_id[] = " ";
                 
            }
            $payment_id[] = $payment->id;
            $payment_note[] = $payment->payment_note;
            $lims_account_data = Account::find($payment->account_id);
            $account_name[] = $lims_account_data->name;
            $account_id[] = $lims_account_data->id;
        }
        $payments[] = $date;
        $payments[] = $payment_reference;
        $payments[] = $paid_amount;
        $payments[] = $paying_method;
        $payments[] = $payment_id;
        $payments[] = $payment_note;
        $payments[] = $cheque_no;
        $payments[] = $change;
        $payments[] = $paying_amount;
        $payments[] = $account_name;
        $payments[] = $account_id;
        $payments[] = $cheque_bank;


        return $payments;
    }

    public function updatePayment(Request $request)
    {
        try {
            DB::beginTransaction();
        $data = $request->all();

        // if ($data['edit_amount']<=0) {
        //     # code...
        //     throw new Exception('Paying Amount can not be zero');
        // }

        $lims_payment_data = Payment::where('id', $data['payment_id'])->lockForUpdate()->firstOrFail();
        $lims_fixed_asset_data = FixedAsset::find($lims_payment_data->fixed_asset_id);
        if ($lims_fixed_asset_data->isDraft()) {
            throw new Exception('FixedAsset is waiting for Approval or rejection, you can not double update untill the Approval process is done!!
            </br>  Contact Your Admin');
        }
        // Check if the lims_payment_data is a draft or approved
        if ($lims_payment_data->isDraft()) {
            throw new Exception('Fixed Asset Payment is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
        }
 
 
        // Get the original data before making any changes
        $originalData = $lims_payment_data->getOriginal();
        

        // Get the original data before making any changes
            
            //  $lims_payment_data->update($originalData);
            $commonAttributes = array_intersect_key($data, $originalData);

                
            $commonAttributes['amount']=$data['edit_amount'];
            $commonAttributes['paid_by_id']=$data['edit_paid_by_id'];
            $commonAttributes['cheque_no']=$data['edit_cheque_no'];
            $commonAttributes['payment_note']=$data['edit_payment_note'];

             if ($commonAttributes != $originalData) {
            // There is an update
            $lims_payment_data->status = Payment::STATUS_DRAFT;
            $lims_payment_data->updated_by = Auth::user()->id;
            $lims_fixed_asset_data->status=FixedAsset::STATUS_DRAFT;
            $lims_fixed_asset_data->save();
            $lims_payment_data->save();
            // Log the status change and the old and new values
            activity()
                ->performedOn($lims_payment_data)
                ->causedBy(Auth::user())
                ->withProperties([
                    'old' => $originalData,
                    'new' => $commonAttributes,
                    'data' => $data,
              
                 ])
                 ->tap(function ($activity) {
                    $activity->is_active = true; // Set the value of the `is_active` column
                    $activity->status = Payment::STATUS_DRAFT; // Set the value of the `is_active` column
                    $activity->url = "fixed_asset/payment"; // Set the value of the `is_active` column
                    $activity->is_root = 1; // Set the value of the `is_active` column
                    $activity->is_deleted =0; // Set the value of the `is_deleted` column

                })
                
                ->log('FixedAsset Payment status updated');


                } 


        DB::commit(); 
        $undoUrl='fixed_asset/payment/reject/'.$data['payment_id'];
       return redirect()->back()->with('message', 'Payment updated successfully, Please Wait for Approval or contact Administrator ')->with('undoUrl', $undoUrl);
 
 
} catch (ModelNotFoundException $e) {
       DB::rollBack();
       return redirect()->back()->with('not_permitted', 'Payment not found');
   } catch (QueryException $e) {
       DB::rollBack();
       return redirect()->back()->with('not_permitted', 'Payment is being updated by another user. Please try again later.');
   } catch (Exception $e) {
       DB::rollBack();
       return redirect()->back()->with('not_permitted', $e->getMessage());
   }
    }



    public function approveUdatePayment($id)
    {
        try {
            DB::beginTransaction();

                       
        //updating purchase table
            $lims_payment_data = Payment::where('id', $id)->firstOrFail();
            $lims_account_data = Account::find($lims_payment_data->account_id);
            $lims_fixed_asset_data = FixedAsset::find($lims_payment_data->fixed_asset_id);

            // Check if the lims_payment_data is a draft
            if (!$lims_payment_data->isDraft()) {
                throw new Exception('This Payment does not have new data to approve');
            }
    
            // Restore the original data
            $logs = Activity::where('subject_type', Payment::class)
            ->where('subject_id', $id)
            ->where('status', Payment::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $data = $properties['data'];
     
    

        $amount_dif = $lims_payment_data->amount - $data['edit_amount'];
        $lims_fixed_asset_data->paid_amount = $lims_fixed_asset_data->paid_amount - $amount_dif;
        $balance = $lims_fixed_asset_data->total_cost - $lims_fixed_asset_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_fixed_asset_data->payment_status = 1;
        elseif ($balance == 0)
            $lims_fixed_asset_data->payment_status = 2;
        $lims_fixed_asset_data->save();

     

        //updating payment data
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->amount = $data['edit_amount'];
        $lims_payment_data->change = $data['edit_paying_amount'] - $data['edit_amount'];
        $lims_payment_data->payment_note = $data['edit_payment_note'];
        if($data['edit_paid_by_id'] == 1)
            $lims_payment_data->paying_method = 'Cash';
        elseif ($data['edit_paid_by_id'] == 2)
            $lims_payment_data->paying_method = 'Gift Card';
        elseif ($data['edit_paid_by_id'] == 3){
            $lims_pos_setting_data = PosSetting::latest()->first();
            \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            $token = $data['stripeToken'];
            $amount = $data['edit_amount'];
            if($lims_payment_data->paying_method == 'Credit Card'){
                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $lims_payment_data->id)->first();

                \Stripe\Refund::create(array(
                  "charge" => $lims_payment_with_credit_card_data->charge_id,
                ));

                $charge = \Stripe\Charge::create([
                    'amount' => $amount * 100,
                    'currency' => 'usd',
                    'source' => $token,
                ]);

                $lims_payment_with_credit_card_data->charge_id = $charge->id;
                $lims_payment_with_credit_card_data->save();
            }
            else{
                // Charge the Customer
                $charge = \Stripe\Charge::create([
                    'amount' => $amount * 100,
                    'currency' => 'usd',
                    'source' => $token,
                ]);

                $data['charge_id'] = $charge->id;
                PaymentWithCreditCard::create($data);
            }
            $lims_payment_data->paying_method = 'Credit Card';
        }         
         elseif ($data['edit_paid_by_id'] == 4) {
            if ($lims_payment_data->paying_method == 'Cheque') {
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $data['payment_id'])->first();
                $data['cheque_no'] = $$data['edit_cheque_no'];
                $data['cheque_bank'] = $lims_account_data->name;
 
                $lims_payment_cheque_data->save();
             } else {
                $lims_payment_data->paying_method = 'Cheque';
                $data['cheque_no'] = $data['edit_cheque_no'];
                $data['cheque_bank'] = $lims_account_data->name;

                PaymentWithCheque::create($data);
            }

            
           
        }elseif ($data['edit_paid_by_id'] == 11) {
            if ($lims_payment_data->paying_method == 'Mobile') {
                $lims_payment_mobile_data = PaymentWithMobile::where('payment_id', $data['payment_id'])->first();
                $lims_payment_mobile_data->mbtn_no = $data['edit_mbtn_no'];
                $lims_payment_mobile_data->mobile_bank = $lims_account_data->name;
 
                $lims_payment_mobile_data->save();
             } else {
                $lims_payment_data->paying_method = 'Mobile';
                $data['mbtn_no'] = $data['edit_mbtn_no'];
                $data['mobile_bank'] =$lims_account_data->name;

                PaymentWithMobile::create($data);
            }

            
           
        }elseif ($data['edit_paid_by_id'] == 12) {
            if ($lims_payment_data->paying_method == 'POS ATM') {
                $lims_payment_pos_data = PaymentWithPOSATM::where('payment_id', $data['payment_id'])->first();
                $data['pos_bank'] = $lims_account_data->name;
 
                $lims_payment_pos_data->save();
             } else {
                $lims_payment_data->paying_method = 'POS ATM';
                $data['pos_bank'] = $lims_account_data->name;

                 

                PaymentWithPOSATM::create($data);
            }

            
           
        } 
        $lims_payment_data->status= Payment::STATUS_APPROVED;
        $lims_fixed_asset_data->status=Payment::STATUS_APPROVED;
        $lims_fixed_asset_data->save();
        $lims_payment_data->save();
        $activity = Activity::find($logs->id);
        $activity->update(['is_active' => false]);
        $activity->update(['status' => FixedAsset::STATUS_APPROVED]);

        $transaction=AccountTransaction::where('payment_id',$lims_payment_data->id)->get();
        foreach ($transaction as $key => $tn) {
            # code...
            if ($tn->credit>0) {
                # code...
                $tn->credit = $data['edit_amount'];
                $account_name=Account::where('id',$data['account_id'])->first();
                $accountType = ChartofAccount::where('name', $account_name->cname)->first();
                $tn->chartof_accounts_id = $accountType->id;
                $tn->save();
            } else {
                # code...
                $tn->debit = $data['edit_amount'];
                $tn->save();
            }
            

        }
            // Log the rejection
            activity()
            ->performedOn($lims_payment_data)
            ->causedBy(Auth::user())
               ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = Payment::STATUS_APPROVED; // Set the value of the `is_active` column

            })
            
            ->log('FixedAsset Payment update status approved');
        
        DB::commit(); 
        return redirect()->back()->with('message', 'Payment updated has been approved successfully');

 
} catch (ModelNotFoundException $e) {
       DB::rollBack();
       return redirect('fixed_asset')->with('not_permitted', 'Payment not found');
   } catch (QueryException $e) {
       DB::rollBack();
       return redirect('fixed_asset')->with('not_permitted', 'Payment is being updated by another user. Please try again later.'. $e);
   } catch (Exception $e) {
       DB::rollBack();
       return redirect('fixed_asset')->with('not_permitted', $e->getMessage());
   }

    }


    public function rejectupdatePayment(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $lims_payment_data = Payment::where('id', $id)->firstOrFail();
            $lims_fixed_asset_data = FixedAsset::find($lims_payment_data->fixed_asset_id);
            // Check if the lims_fixed_asset_data is a draft
            if (!$lims_payment_data->isDraft()) {
                throw new Exception('This FixedAsset does not have new data to reject an update');
            }
    
            // Restore the original data
            $logs = Activity::where('subject_type', Payment::class)
            ->where('subject_id', $lims_payment_data->id)
            ->where('status', FixedAsset::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $data = $properties['old'];
            $lims_payment_data->update($data);
            $lims_payment_data->status = Payment::STATUS_REJECTED;
            $lims_fixed_asset_data->status=FixedAsset::STATUS_REJECTED;
            $lims_fixed_asset_data->save();
            $lims_payment_data->save();
            $lims_payment_data->update($data);

            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => FixedAsset::STATUS_REJECTED]);
                        // Log the rejection
                        activity()
                        ->performedOn($lims_payment_data)
                        ->causedBy(Auth::user())
                           ->tap(function ($activity) {
                            $activity->is_active = false; // Set the value of the `is_active` column
                            $activity->status = Payment::STATUS_APPROVED; // Set the value of the `is_active` column
            
                        })
                        
                        ->log('FixedAsset Payment update Rejected');
                    
    DB::commit();
    return redirect('fixed_asset')->with('not_permitted', 'FixedAsset updated approval hasbeen rejected');

 
} catch (ModelNotFoundException $e) {
       DB::rollBack();
       return redirect('fixed_asset')->with('not_permitted', 'Payment not found'.$e->getMessage());
   } catch (QueryException $e) {
       DB::rollBack();
       return redirect('fixed_asset')->with('not_permitted', 'Payment is being updated by another user. Please try again later.');
   } catch (Exception $e) {
       DB::rollBack();
       return redirect()->back()->with('not_permitted', $e->getMessage());
   }
    }




    public function deletePayment(Request $request)
    {
        DB::beginTransaction();
        try {
        $lims_payment_data = Payment::find($request['id']);
        $lims_fixed_asset_data = FixedAsset::where('id', $lims_payment_data->fixed_asset_id)->first();

        activity()
        ->performedOn($lims_payment_data)
        ->causedBy(Auth::user())
        ->withProperties(['old' => $lims_payment_data])
        ->tap(function ($activity) {
            $activity->is_active = true; // Set the value of the `is_active` column
            $activity->status = Payment::STATUS_DRAFT; // Set the value of the `is_active` column
            $activity->url = "fixed_asset/payment"; // Set the value of the `is_active` column
            $activity->is_root = 1; // Set the value of the `is_active` column
            $activity->is_deleted = 1; // Set the value of the `is_active` column
        })
        ->log('FixedAsset Payment Data Deleted');
        $lims_fixed_asset_data->status=FixedAsset::STATUS_DRAFT;
        $lims_fixed_asset_data->save();
        $lims_payment_data->delete();
        DB::commit();
         $undoUrl = 'fixed_asset/payment/restore/'.$request['id'];
        return redirect('fixed_asset')->with('message', 'Fixed Asset Payment Data deleted successfully, Please Wait for Approval or Contact System Administrator ')->with('deleteUrl', $undoUrl);
        
 
} catch (ModelNotFoundException $e) {
    DB::rollBack();
    return redirect('fixed_asset')->with('not_permitted', 'Payment not found'.$e->getMessage());
} catch (QueryException $e) {
    DB::rollBack();
    return redirect('fixed_asset')->with('not_permitted', 'Payment is being updated by another user. Please try again later.');
} catch (Exception $e) {
    DB::rollBack();
    return redirect()->back()->with('not_permitted', $e->getMessage());
}
    }



    public function approvedeletePayment(Request $request)
    {
        DB::beginTransaction();
        try {
            $lims_payment_data = Payment::withTrashed()->where('id', $request['id'])->first();
        $lims_fixed_asset_data = FixedAsset::where('id', $lims_payment_data->fixed_asset_id)->first();


        $lims_fixed_asset_data->paid_amount -= $lims_payment_data->amount;
        $balance = $lims_fixed_asset_data->total_cost - $lims_fixed_asset_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_fixed_asset_data->payment_status = 1;
        elseif ($balance == 0)
            $lims_fixed_asset_data->payment_status = 2;


         $lims_fixed_asset_data->status=FixedAsset::STATUS_APPROVED;
         $lims_fixed_asset_data->save();

        if($lims_payment_data->paying_method == 'Credit Card'){
            $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $request['id'])->first();
            $lims_pos_setting_data = PosSetting::latest()->first();
            \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            \Stripe\Refund::create(array(
              "charge" => $lims_payment_with_credit_card_data->charge_id,
            ));

            $lims_payment_with_credit_card_data->forceDelete();
        }
        elseif ($lims_payment_data->paying_method == 'Cheque') {
            $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $request['id'])->first();
            $lims_payment_cheque_data->forceDelete();
        }elseif ($lims_payment_data->paying_method == 'Mobile') {
                $lims_payment_cheque_data = PaymentWithMobile::where('payment_id', $request['id'])->first();
                $lims_payment_cheque_data->forceDelete();           
        } 
        elseif ($lims_payment_data->paying_method == 'POS ATM') {
            $lims_payment_cheque_data = PaymentWithPOSATM::where('payment_id', $request['id'])->first();
            $lims_payment_cheque_data->forceDelete();           
    } 

        $lims_payment_data->forceDelete();
             $logs = Activity::where('subject_type', Payment::class)
            ->where('subject_id', $lims_payment_data->id)
            ->where('status', FixedAsset::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
           
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => FixedAsset::STATUS_APPROVED]);
        activity()
        ->performedOn($lims_payment_data)
        ->causedBy(Auth::user())
        ->withProperties(['old' => $lims_payment_data])
        ->tap(function ($activity) {
            $activity->is_active = false; // Set the value of the `is_active` column
              $activity->is_root = 1; // Set the value of the `is_active` column
            $activity->is_deleted =1; // Set the value of the `is_active` column


        })
        ->log('FixedAsset Payment data Deleted Permanently');
 
        DB::commit();
        return redirect('fixed_asset')->with('not_permitted', 'Fixed Asset Payment Data deleted successfully');

        
 
} catch (ModelNotFoundException $e) {
    DB::rollBack();
    return redirect('fixed_asset')->with('not_permitted', 'Payment not found'.$e->getMessage());
} catch (QueryException $e) {
    DB::rollBack();
    return redirect('fixed_asset')->with('not_permitted', 'Payment is being updated by another user. Please try again later.');
} catch (Exception $e) {
    DB::rollBack();
    return redirect()->back()->with('not_permitted', $e->getMessage());
}
    }


    public function rejectdeletePayment(Request $request)
    {
        DB::beginTransaction();
        try {

            $lims_payment_data = Payment::withTrashed()->where('id', $request['id'])->first();
            $lims_fixed_asset_data = FixedAsset::where('id', $lims_payment_data->fixed_asset_id)->first();

            if (!$lims_payment_data) {
                throw new Exception('Payment Asset not found.');
            }
    
            $lims_payment_data->restore();
            
    
            
    
            $logs = Activity::where('subject_type', Payment::class)
                ->where('subject_id', $request['id'])
                ->where('status', FixedAsset::STATUS_DRAFT)
                ->where('is_active', true)
                ->latest()
                ->firstOrFail();
    
            $logs->update(['is_active' => false]);
            $logs->update(['status' => Payment::STATUS_REJECTED]);
            $lims_payment_data->status = Payment::STATUS_REJECTED;
            $lims_payment_data->save();
            $lims_fixed_asset_data->status=FixedAsset::STATUS_REJECTED;
            $lims_fixed_asset_data->save();
     
            activity()
                ->performedOn($lims_payment_data)
                ->causedBy(Auth::user())
                ->tap(function ($activity) {
                    $activity->is_active = false; // Set the value of the `is_active` column
                      $activity->is_root = 1; // Set the value of the `is_active` column
                    $activity->is_deleted =1; // Set the value of the `is_active` column
       
       
                })
                ->log('FixedAsset Payment Restored');
    
            DB::commit();
    
        DB::commit();
        return redirect('fixed_asset')->with('message', 'Fixed Asset Payment Data restored successfully');

        
 
} catch (ModelNotFoundException $e) {
    DB::rollBack();
    return redirect('fixed_asset')->with('not_permitted', 'Payment not found'.$e->getMessage());
} catch (QueryException $e) {
    DB::rollBack();
    return redirect('fixed_asset')->with('not_permitted', 'Payment is being updated by another user. Please try again later.');
} catch (Exception $e) {
    DB::rollBack();
    return redirect()->back()->with('not_permitted', $e->getMessage());
}
    }


}
