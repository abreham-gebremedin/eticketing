<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\AccountTransactionAdjustment;
use App\ChartofAccount;
use App\Journal_Entry;
use App\Models\Bus;
use App\Models\City;
use App\Shareholder;
use Exception;
use Illuminate\Http\Request;
use App\Account;
use App\Warehouse;
use App\CashRegister;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use DB;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('buses-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';
            $lims_cities_all = City::get();
            $lims_account_list = Account::where('is_active', true)->get();
    
            return view('bus.cities', compact('lims_cities_all', 'all_permission', 'lims_account_list'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

     
    public function create()
    {
        //
    }

    public function store(Request $request)
{
    try {
        DB::beginTransaction();

        $data = $request->validate([
            'name' => 'required|unique:cities',
            // Add validation rules for other fields if needed
        ]);

        if (isset($data['created_at'])) {
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        } else {
            $data['created_at'] = date("Y-m-d H:i:s");
        }

        $lims_city_data = City::create($data);
        DB::commit();

        return redirect('cities')->with('message', 'Data inserted successfully');

    } catch (Exception $e) {
        DB::rollback();
        return redirect('cities')->with('not_permitted', $e->getMessage());
    }
}

    

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('buses-edit')) {
            $lims_city_data = City::find($id);
            $lims_city_data->date = date('d-m-Y', strtotime($lims_city_data->created_at->toDateString()));
            return $lims_city_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $lims_city_data = City ::where('id', $data['id'])->lockForUpdate()->firstOrFail();
             // Check if the lims_city_data is a draft or approved
            if ($lims_city_data->isDraft()) {
                throw new Exception('City is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
            }
    
            // Get the original data before making any changes
            $originalData = $lims_city_data->getOriginal();
    
            // // Update the lims_city_data
            $data['updated_by'] = Auth::user()->id;
            $lims_city_data->update($data);
    
           
            
            // Get the original data before making any changes
                
                $newData = $lims_city_data->getAttributes();
                $lims_city_data->update($originalData);
                // Get the attributes that exist in both $request and $originalData             
    
                if ($newData != $originalData) {
                // There is an update
                $lims_city_data->status = City::STATUS_DRAFT;
                $lims_city_data->updated_by = Auth::user()->id;
                $lims_city_data->save();
    
                // Log the status change and the old and new values
                activity()
                    ->performedOn($lims_city_data)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'old' => $originalData,
                        'new' => $newData,
                  
                     ])
                     ->tap(function ($activity) {
                        $activity->is_active = true; // Set the value of the `is_active` column
                        $activity->status = City::STATUS_DRAFT; // Set the value of the `is_active` column
                        $activity->url = "cities"; // Set the value of the `is_active` column
                        $activity->is_root = 1; // Set the value of the `is_active` column

                    })
                    
                    ->log('City status updated');
                    }

    
            DB::commit();
    
            return redirect('cities')->with('message', 'City Data updated successfully, Please wait for approval');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'An error occurred while updating City data. Please try again later.'.$e->getMessage().$e->getLine());
        }
    } 

    public function deleteBySelection(Request $request)
    {
        $City_id = $request['CityIdArray'];
        foreach ($City_id as $id) {
            $lims_city_data = City::find($id);
            $lims_city_data->delete();
        }
        return 'City deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_city_data = City::find($id);
        $lims_city_data->delete();
        return redirect('cities')->with('not_permitted', 'Data deleted successfully');
    }

    public function approve(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
 
            $lims_city_data = City ::where('id', $id)->lockForUpdate()->firstOrFail();
    
            // Check if the lims_city_data is a draft or approved
            if (!$lims_city_data->isDraft()) {
                throw new Exception('City is not in pending status for Aproval!!');
            }

            $logs = Activity::where('subject_type', City::class)
            ->where('subject_id', $id)
            ->where('status', City::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $newData = $properties['new'];
            $lims_city_data->update($newData);
            $lims_city_data->status = City::STATUS_APPROVED;
            $lims_city_data->save();
    
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => City::STATUS_APPROVED]);
 
             
    
            // Log the rejection
            activity()
            ->performedOn($lims_city_data)
            ->causedBy(Auth::user())
            ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = City::STATUS_APPROVED; // Set the value of the `is_active` column

            })

            ->log('City update approved');
            DB::commit();
    
            return redirect('cities')->with('message', 'Data updated successfully, Please wait for approval');
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
 
            $lims_city_data = City::where('id', $id)->lockForUpdate()->firstOrFail();
    
            // Check if the lims_city_data is a draft or approved
            if ($lims_city_data->isDraft()) {
                throw new Exception('City is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
            }

            $logs = Activity::where('subject_type', FixedAsset::class)
            ->where('subject_id', $id)
            ->where('status', City::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $oldData = $properties['old'];
            $lims_city_data->update($oldData);
            $lims_city_data->status = City::STATUS_APPROVED;
            $lims_city_data->save();
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => City::STATUS_APPROVED]);
            // Log the rejection
            activity()
            ->performedOn($lims_city_data)
            ->causedBy(Auth::user())
            ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = City::STATUS_APPROVED; // Set the value of the `is_active` column
            })
            ->log('city update Rejected');
            DB::commit();
            return redirect('cities')->with('message', 'Data updated successfully, Please wait for approval');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'An error occurred while updating data. Please try again later.'.$e->getMessage());
        }
    }
}
