<?php

namespace App\Http\Controllers;

use App\ChartofAccount;
use App\FixedAssetCategory;
use Auth;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\ConcurrentUpdateException; 
use Spatie\Activitylog\Models\Activity;// add this line


class FixedAssetCategoryController extends Controller
{


    public function index()
    {
        $lims_fixed_asset_category_all = FixedAssetCategory::where('is_active', true)->get();
        return view('fixed_asset_category.index', compact('lims_fixed_asset_category_all'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $this->validate($request, [
            'code' => [
                'max:255',
                Rule::unique('fixed_asset_categories')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ]
        ]);

        if ($data['name'] == "" || ctype_space($data['name']) || $data['name'] == null) {
            redirect('fixed_asset')->with('not_permitted', 'Name can not be empty ');

        }

        $fixed_asset_category = FixedAssetCategory::create($data);
        $ca = new ChartofAccount;
        $ca->name = $data['name'];
         $ca->fixed_asset_category_id = $fixed_asset_category->id;
        $ca->chartof_account_categories_id=1;
        $ca->is_current_asset = 0;
        $ca->default_side = "debit";

        $ca->save();

        $ca = new ChartofAccount;
        $ca->name = "Acc.Dep Of " . $data['name'];
         $ca->fixed_asset_category_id = $fixed_asset_category->id;
        $ca->is_current_asset = 0;
        $ca->chartof_account_categories_id=1;
        $ca->default_side = "credit";
        $ca->save();

        $ca = new ChartofAccount;
        $ca->name = "Dep Expense Of " . $data['name'];
         $ca->fixed_asset_category_id = $fixed_asset_category->id;
        $ca->chartof_account_categories_id=5;
         $ca->default_side = "debit";
        $ca->save();



        return redirect('fixed_asset_categories')->with('message', 'Data inserted successfully');


    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $lims_fixed_asset_category_data = FixedAssetCategory::find($id);
        return $lims_fixed_asset_category_data;
    }


    public function update(Request $request, $id)
    {
        $data = $request->all();
        $id = $data['fixed_asset_category_id'];
    
        if ($data['name'] == "" || ctype_space($data['name']) || $data['name'] == null) {
            return redirect('fixed_asset')->with('not_permitted', 'Name can not be empty ');
        }
    
        try {
            DB::beginTransaction();
    
            // Get the lims_fixed_asset_category_data and lock the record for update
            $lims_fixed_asset_category_data = FixedAssetCategory::where('id', $id)->lockForUpdate()->firstOrFail();
    
            // Check if the lims_fixed_asset_category_data is a draft or approved
            if ($lims_fixed_asset_category_data->isDraft()) {
                throw new Exception('FixedAssetCategory is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
            }
    
            // Get the original data before making any changes
            $originalData = $lims_fixed_asset_category_data->getOriginal();
    
            // // Update the lims_fixed_asset_category_data
            // $lims_fixed_asset_category_data->update($data);
    
           
            
            // Get the original data before making any changes
                $originalData = $lims_fixed_asset_category_data->getOriginal();

                // Get the attributes that exist in both $request and $originalData
                $commonAttributes = array_intersect_key($data, $originalData);

                
    
                if ($commonAttributes != $originalData) {
                // There is an update
                $lims_fixed_asset_category_data->status = FixedAssetCategory::STATUS_DRAFT;
                $lims_fixed_asset_category_data->updated_by = Auth::user()->id;
                $lims_fixed_asset_category_data->save();
    
                // Log the status change and the old and new values
                activity()
                    ->performedOn($lims_fixed_asset_category_data)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'old' => $originalData,
                        'new' => $commonAttributes,
                  
                     ])
                     ->tap(function ($activity) {
                        $activity->is_active = true; // Set the value of the `is_active` column
                        $activity->status = FixedAssetCategory::STATUS_DRAFT; // Set the value of the `is_active` column
                        $activity->url = "fixed_asset_categories"; // Set the value of the `is_active` column
                        $activity->is_root = 1; // Set the value of the `is_active` column

                    })
                    
                    ->log('FixedAssetCategory status updated');

 

    
                $lims_chart_of_account_data = ChartofAccount::where('fixed_asset_category_id', $lims_fixed_asset_category_data->id)->lockForUpdate()->get();
    
                if ($lims_chart_of_account_data->count() > 0) {
                    foreach ($lims_chart_of_account_data as $chartOfAccount) {
                        // Get the original data before making any changes
                        $originalChartOfAccountData = $chartOfAccount->getOriginal();
                        if ($chartOfAccount->default_side == 'debit') {
                            $chartOfAccount->status = ChartofAccount::STATUS_DRAFT;
                            $chartOfAccount->updated_by = Auth::user()->id;
                            $chartOfAccount->name = $data['name'];
                         } else {
                            $chartOfAccount->status = ChartofAccount::STATUS_DRAFT;
                            $chartOfAccount->updated_by = Auth::user()->id;
                            $chartOfAccount->name = $data['name'];
                         }
                        $chartOfAccount->save();
                        $newData = $chartOfAccount->getAttributes();

                        $chartOfAccount->update($originalChartOfAccountData);

                         // Log the status change and the old and new values
                        activity()
                        ->performedOn($chartOfAccount)
                        ->causedBy(Auth::user())
                        ->withProperties(['old' => $originalChartOfAccountData, 'new' => $newData
                        
                        ])
                        ->tap(function ($activity) {
                            $activity->is_active = true; // Set the value of the `is_active` column
                            $activity->status = ChartofAccount::STATUS_DRAFT; // Set the value of the `is_active` column
                            $activity->url = "chart_of_accounts"; // Set the value of the `is_active` column
                            $activity->is_root = 0; // Set the value of the `is_active` column
    
                        })
                        
 
                        ->log('ChartofAccount status updated due to FixedAssetCategory update');

                    }
                }
            }
    
             DB::commit(); 
             $undoUrl='fixed_asset_categories/reject/'.$id;
            return redirect()->back()->with('message', 'FixedAssetCategory updated successfully, Please Wait for Approval or contact Administrator ')->with('undoUrl', $undoUrl);
    
      
    } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'FixedAssetCategory not found');
        } catch (QueryException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'FixedAssetCategory is being updated by another user. Please try again later.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', $e->getMessage());
        }
    }

   


    public function deleteBySelection(Request $request)
    {
        $fixed_asset_category_id = $request['fixed_asset_categoryIdArray'];
        foreach ($fixed_asset_category_id as $id) {
            $lims_fixed_asset_category_data = FixedAssetCategory::find($id);
            $lims_fixed_asset_category_data->is_active = false;
            $lims_fixed_asset_category_data->save();
        }
        return 'FixedAsset Category deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_fixed_asset_category_data = FixedAssetCategory::find($id);
        $lims_fixed_asset_category_data->is_active = false;
        $lims_fixed_asset_category_data->save();

        $lims_chart_of_account_data = ChartofAccount::where('fixed_asset_category_id', $lims_fixed_asset_category_data->id);
        $lims_chart_of_account_data->delete();


        return redirect('fixed_asset_categories')->with('not_permitted', 'Data deleted successfully');
    }




    public function approve($id)
    {
        try {
            DB::beginTransaction();
    
            // Get the lims_fixed_asset_category_data and lock the record for update
            $lims_fixed_asset_category_data = FixedAssetCategory::where('id', $id)->firstOrFail();
            // Check if the lims_fixed_asset_category_data is a draft
            if (!$lims_fixed_asset_category_data->isDraft()) {
                throw new Exception('FixedAssetCategory is not a draft');
            }
    
            // Restore the original data
            $logs = Activity::where('subject_type', FixedAssetCategory::class)
            ->where('subject_id', $lims_fixed_asset_category_data->id)
            ->where('status', FixedAssetCategory::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
                ->firstOrFail();
            $properties = $logs->properties;
            $newData = $properties['new'];
            $oldData = $properties['old'];

            $lims_fixed_asset_category_data->update($newData);
            $lims_fixed_asset_category_data->status = FixedAssetCategory::STATUS_APPROVED;
            $lims_fixed_asset_category_data->save();
    
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => FixedAssetCategory::STATUS_APPROVED]);
    
            // Update the related ChartofAccount records
            $lims_chart_of_account_data = ChartofAccount::where('fixed_asset_category_id', $lims_fixed_asset_category_data->id)->get();
            if ($lims_chart_of_account_data->count() > 0) {
                foreach ($lims_chart_of_account_data as $chartOfAccount) {


                    if ($chartOfAccount->name ==  $oldData['name']) {
                        $chartOfAccount->status = $chartOfAccount::STATUS_APPROVED;
                        $chartOfAccount->name = $newData['name'];

                        
                     } 
                     if ($chartOfAccount->name == "Dep Expense Of " . $oldData['name']) {
                        $chartOfAccount->status = $chartOfAccount::STATUS_APPROVED;
                        $chartOfAccount->name = "Dep Expense Of " . $newData['name'];

                        
                     } 
                     
                     if ($chartOfAccount->name == "Acc.Dep Of " . $oldData['name']) {
                        $chartOfAccount->status = $chartOfAccount::STATUS_APPROVED;
                        $chartOfAccount->name = "Acc.Dep Of " . $newData['name'];

                        
                     } 

                     $chartOfAccount->save();
                     
                     
                    
                }

                // $ca = new ChartofAccount;
                // $ca->name = "Dep Expense Of " . $newData['name'];
                // $ca->chartof_account_categories_id = 1;
                // $ca->fixed_asset_category_id = $lims_fixed_asset_category_data->id;
                // $ca->is_current_asset = 0;
                // $ca->default_side = "credit";
                // $ca->save();
            }
    
            // Log the rejection
            activity()
            ->performedOn($lims_fixed_asset_category_data)
            ->causedBy(Auth::user())
               ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = FixedAssetCategory::STATUS_APPROVED; // Set the value of the `is_active` column

            })
            
            ->log('FixedAssetCategory update approved');
        
    
            DB::commit();
    
            return redirect()->back()->with('message', 'FixedAssetCategory update approved');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'FixedAssetCategory not found');
        } catch (QueryException $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'FixedAssetCategory is being updated by another user. Please try again later.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', $e->getMessage());
        }
    }

    public function reject($id)
{
    try {
        DB::beginTransaction();

        // Get the lims_fixed_asset_category_data and lock the record for update
        $lims_fixed_asset_category_data = FixedAssetCategory::where('id', $id)->firstOrFail();
        // Check if the lims_fixed_asset_category_data is a draft
        if (!$lims_fixed_asset_category_data->isDraft()) {
            throw new Exception('FixedAssetCategory is not a draft');
        }

        // Restore the original data
        $logs = Activity::where('subject_type', FixedAssetCategory::class)
            ->where('subject_id', $lims_fixed_asset_category_data->id)
            ->latest()
            ->firstOrFail();
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => FixedAssetCategory::STATUS_REJECTED]);
        $properties = $logs->properties;
        $oldData = $properties['old'];
        $lims_fixed_asset_category_data->update($oldData);
        $lims_fixed_asset_category_data->status = FixedAssetCategory::STATUS_REJECTED;
        $lims_fixed_asset_category_data->save();



        // Update the related ChartofAccount records
        $lims_chart_of_account_data = ChartofAccount::where('fixed_asset_category_id', $lims_fixed_asset_category_data->id)->get();
        if ($lims_chart_of_account_data->count() > 0) {
            foreach ($lims_chart_of_account_data as $chartOfAccount) {
                if ($chartOfAccount->default_side == 'debit') {
                    $chartOfAccount->status = ChartofAccount::STATUS_REJECTED;
                     $chartOfAccount->name = $oldData['name'];
                     $chartOfAccount->updated_by = null;

                } else {
                    $chartOfAccount->status = ChartofAccount::STATUS_REJECTED;
                     $chartOfAccount->name = "Acc.Dep Of " . $oldData['name'];
                     $chartOfAccount->updated_by = null;
                }
                $chartOfAccount->save();
            }
        }

        // Log the rejection
        activity()
            ->performedOn($lims_fixed_asset_category_data)
            ->causedBy(Auth::user())
            ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = FixedAssetCategory::STATUS_REJECTED; // Set the value of the `is_active` column
                $activity->url = "fixed_asset"; // Set the value of the `is_active` column
                $activity->is_root = 1; // Set the value of the `is_active` column

            })
            
            ->log('FixedAssetCategory update rejected');

        DB::commit();

        return redirect()->back()->with('not_permitted', 'FixedAssetCategory update rejected');
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', 'FixedAssetCategory not found');
    } catch (QueryException $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', 'FixedAssetCategory is being updated by another user. Please try again later.');
    } catch (Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', $e->getMessage());
    }
}

}