<?php

namespace App\Http\Controllers;


use App\Models\Route; 
use Exception;
use Illuminate\Http\Request;
use App\Account; 
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use DB;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('route-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';

                if (Auth::user()->role_id >= 2) {
                   
                   $lims_routes_all = Route::with(['departureCity', 'arrivalCity', 'user'])
                   ->where('warehouse_id',Auth::user()->warehouse_id)
                   ->get();

               }else {
                   # code...
                   $lims_routes_all = Route::with(['departureCity', 'arrivalCity', 'user'])->get();
       
                  
               }
            $lims_account_list = Account::where('is_active', true)->get();
    
            return view('bus.routes', compact('lims_routes_all', 'all_permission', 'lims_account_list'));
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

        $data = $request->all();
        if (isset($data['created_at'])) {
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        } else {
            $data['created_at'] = date("Y-m-d H:i:s");
        }
        if (!isset($data['warehouse_id'])) {
            $data['warehouse_id'] = Auth::user()->warehouse_id;
        }

        // Validate unique ArrivalCity before creating the record
        $validator = Validator::make($data, [
            'DepartureCity' => 'required',
            'ArrivalCity' => 'required|unique:routes,ArrivalCity,NULL,id,warehouse_id,' . $data['warehouse_id'],
            'DistanceKM' => 'required',
            'TicketPrice' => 'required',
        ]);
        
        if ($validator->fails()) {
            return redirect('routes1')->with('not_permitted', 'Validation error '.$validator->errors() );
        }

        $lims_route_data = Route::create($data);
        DB::commit();

        return redirect('routes1')->with('message', 'Data inserted successfully');

    } catch (Exception $e) {
        DB::rollback();
        return redirect('routes1')->with('not_permitted', $e->getMessage());
    }
}

    

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('routes-edit')) {
            $lims_route_data = Route::find($id);
            $lims_route_data->date = date('d-m-Y', strtotime($lims_route_data->created_at->toDateString()));
            return $lims_route_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            if (!isset($data['warehouse_id'])) {
                $data['warehouse_id'] = Auth::user()->warehouse_id;
            }
            $lims_route_data = Route::where('id', $data['id'])->lockForUpdate()->firstOrFail();
             // Check if the lims_route_data is a draft or approved
            if ($lims_route_data->isDraft()) {
                throw new Exception('route is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
            }
    
            // Get the original data before making any changes
            $originalData = $lims_route_data->getOriginal();
    
            // // Update the lims_route_data
            $data['updated_by'] = Auth::user()->id;
            $lims_route_data->update($data);
    
           
            
            // Get the original data before making any changes
                
                $newData = $lims_route_data->getAttributes();
                $lims_route_data->update($originalData);
                // Get the attributes that exist in both $request and $originalData             
    
                if ($newData != $originalData) {
                // There is an update
                $lims_route_data->status = Route::STATUS_DRAFT;
                $lims_route_data->updated_by = Auth::user()->id;
                $lims_route_data->save();
    
                // Log the status change and the old and new values
                activity()
                    ->performedOn($lims_route_data)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'old' => $originalData,
                        'new' => $newData,
                        'warehouse_id' => $lims_route_data->warehouse_id,
                  
                     ])
                     ->tap(function ($activity,$lims_route_data) {
                         $activity->is_active = true; // Set the value of the `is_active` column
                        $activity->status = Route::STATUS_DRAFT; // Set the value of the `is_active` column
                        $activity->url = "routes1"; // Set the value of the `is_active` column
                        $activity->is_root = 1; // Set the value of the `is_active` column
 
                    })
                    
                    ->log('route status updated');
                    }

    
            DB::commit();
    
            return redirect('routes1')->with('message', 'Data updated successfully, Please wait for approval');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'An error occurred while updating route data. Please try again later.'.$e->getMessage().$e->getLine());
        }
    } 

    public function deleteBySelection(Request $request)
    {
        $route_id = $request['routeIdArray'];
        foreach ($route_id as $id) {
            $lims_route_data = Route::find($id);
            $lims_route_data->delete();
        }
        return 'route deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_route_data = Route::find($id);
        $lims_route_data->delete();
        return redirect('routes1')->with('not_permitted', 'Data deleted successfully');
    }

    public function approve(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
 
            $lims_route_data = Route::where('id', $id)->lockForUpdate()->firstOrFail();
    
            // Check if the lims_route_data is a draft or approved
            if (!$lims_route_data->isDraft()) {
                throw new Exception('route is not in pending status for Aproval!!');
            }

            $logs = Activity::where('subject_type', Route::class)
            ->where('subject_id', $id)
            ->where('status', Route::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $newData = $properties['new'];
            $lims_route_data->update($newData);
            $lims_route_data->status = Route::STATUS_APPROVED;
            $lims_route_data->save();
    
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => Route::STATUS_APPROVED]);
 
             
    
            // Log the rejection
            activity()
            ->performedOn($lims_route_data)
            ->causedBy(Auth::user())
            ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = Route::STATUS_APPROVED; // Set the value of the `is_active` column

            })

            ->log('route update approved');
            DB::commit();
    
            return redirect('routes1')->with('message', 'Data updated successfully, Please wait for approval');
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
 
            $lims_route_data = Route::where('id', $data['route_id'])->lockForUpdate()->firstOrFail();
    
            // Check if the lims_route_data is a draft or approved
            if ($lims_route_data->isDraft()) {
                throw new Exception('route is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
            }

            $logs = Activity::where('subject_type', FixedAsset::class)
            ->where('subject_id', $id)
            ->where('status', Route::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $oldData = $properties['old'];
            $lims_route_data->update($oldData);
            $lims_route_data->status = Route::STATUS_APPROVED;
            $lims_route_data->save();
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => Route::STATUS_APPROVED]);
            // Log the rejection
            activity()
            ->performedOn($lims_route_data)
            ->causedBy(Auth::user())
            ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = Route::STATUS_APPROVED; // Set the value of the `is_active` column
            })
            ->log('route update Rejected');
            DB::commit();
            return redirect('routes1')->with('message', 'Data updated successfully, Please wait for approval');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'An error occurred while updating data. Please try again later.'.$e->getMessage());
        }
    }


    
}
