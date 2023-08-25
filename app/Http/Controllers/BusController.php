<?php

namespace App\Http\Controllers;

 
use App\Models\Bus;
use App\Models\Queue;
use App\Models\Route;
 use App\User;
use App\Warehouse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Account; 
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use DB;
use Validator;

class BusController extends Controller
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

                if (Auth::user()->role_id >= 2) {
                    $lims_buses_all = Bus::with(['route.arrivalCity','route.departureCity'])->where('warehouse_id',Auth::user()->warehouse_id)->get();
         
                }else {
                    # code...
                    $lims_buses_all = Bus::with(['route.arrivalCity','route.departureCity'])->get();

                   
                }
                $lims_account_list = Account::where('is_active', true)->get();
    
            return view('bus.index', compact('lims_buses_all', 'all_permission', 'lims_account_list'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

     
    public function create($routeId)
    {
        //
        $buses = Bus::where('RouteID', $routeId)->get();

        // Remove buses that are already in the queue and have not departed
        $filteredBuses = $buses->reject(function ($bus) use ($routeId) {
            return Queue::where('BusID', $bus->id)
                ->where('RouteID', $routeId)
                ->whereDate('created_at','=', date('Y-m-d'))
                ->where('isDeparted', 0)
                ->exists();
        });
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();

        return view('bus.create', compact('filteredBuses','routeId','lims_warehouse_list'));
    }
  
    public function assign($routeId)
    {
        try {
            $route = Route::findOrFail($routeId);
            
            // Retrieve users based on the warehouse_id of the route

            if (Auth::user()->role_id >= 2) {
                $lims_user_list = User::where('is_deleted', false)
                ->where('warehouse_id', Auth::user()->warehouse_id)
                ->get();     
            }else {
                # code...
                $lims_user_list = User::where('is_deleted', false)
                ->where('warehouse_id',  $route->warehouse_id)
                 ->get();  
               
            }
 
    
            // Rest of your logic
    
            return view('bus.assignroute', compact('lims_user_list', 'routeId'));
        } catch (ModelNotFoundException $e) {
            return back()->with('error', 'Route not found.');
        }
    }
    
     
    
    public function store(Request $request)
{
  

   

    try {
        DB::beginTransaction();
        $data = $request->all();

    
        if (!isset($data['warehouse_id'])) {
            $data['warehouse_id'] = Auth::user()->warehouse_id;
        }

        $validator = Validator::make($data, [
            'BusNumber' => 'required|unique:buses,BusNumber,NULL,id,warehouse_id,' . $data['warehouse_id'],
            // Other validation rules for your fields
        ]);

        if ($validator->fails()) {
            return redirect('buses')->with('not_permitted', "In this Bus Station this Plate Number ".$validator->errors());

        }

        if (isset($data['created_at'])) {
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        } else {
            $data['created_at'] = date("Y-m-d H:i:s");
        }

        $lims_bus_data = Bus::create($data);
        DB::commit();

        return redirect('buses')->with('message', 'Data inserted successfully');
    } catch (Exception $e) {
        DB::rollback();
        return redirect('buses')->with('not_permitted', $e->getMessage());
    }
}


    


    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('buses-edit')) {
            $lims_bus_data = Bus::find($id);
            $lims_bus_data->date = date('d-m-Y', strtotime($lims_bus_data->created_at->toDateString()));
 
            return $lims_bus_data;
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
            $lims_bus_data = Bus ::where('id', $data['BusID'])->lockForUpdate()->firstOrFail();
             // Check if the lims_bus_data is a draft or approved
            if ($lims_bus_data->isDraft()) {
                throw new Exception('bus is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
            }
    
            // Get the original data before making any changes
            $originalData = $lims_bus_data->getOriginal();
    
            // // Update the lims_bus_data
            $data['updated_by'] = Auth::user()->id;
            $lims_bus_data->update($data);
    
           
            
            // Get the original data before making any changes
                
                $newData = $lims_bus_data->getAttributes();
                $lims_bus_data->update($originalData);
                // Get the attributes that exist in both $request and $originalData             
    
                if ($newData != $originalData) {
                // There is an update
                $lims_bus_data->status = Bus::STATUS_DRAFT;
                $lims_bus_data->updated_by = Auth::user()->id;
                $lims_bus_data->save();
    
                // Log the status change and the old and new values
                activity()
                    ->performedOn($lims_bus_data)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'old' => $originalData,
                        'new' => $newData,
                        'warehouse_id' => $lims_bus_data->warehouse_id,

                  
                     ])
                     ->tap(function ($activity) {
                        $activity->is_active = true; // Set the value of the `is_active` column
                        $activity->status = Bus::STATUS_DRAFT; // Set the value of the `is_active` column
                        $activity->url = "buses"; // Set the value of the `is_active` column
                        $activity->is_root = 1; // Set the value of the `is_active` column

                    })
                    
                    ->log('bus status updated');
                    }

    
            DB::commit();
    
            return redirect('buses')->with('message', 'Data updated successfully, Please wait for approval');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'An error occurred while updating bus data. Please try again later.'.$e->getMessage().$e->getLine());
        }
    } 

    public function deleteBySelection(Request $request)
    {
        $bus_id = $request['busIdArray'];
        foreach ($bus_id as $id) {
            $lims_bus_data = bus::find($id);
            $lims_bus_data->delete();
        }
        return 'bus deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_bus_data = bus::find($id);
        $lims_bus_data->delete();
        return redirect('buses')->with('not_permitted', 'Data deleted successfully');
    }

    public function approve(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
 
            $lims_bus_data = Bus ::where('id', $id)->lockForUpdate()->firstOrFail();
    
            // Check if the lims_bus_data is a draft or approved
            if (!$lims_bus_data->isDraft()) {
                throw new Exception('bus is not in pending status for Aproval!!');
            }

            $logs = Activity::where('subject_type', Bus::class)
            ->where('subject_id', $id)
            ->where('status', Bus::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $newData = $properties['new'];
            $lims_bus_data->update($newData);
            $lims_bus_data->status = Bus::STATUS_APPROVED;
            $lims_bus_data->save();
    
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => Bus::STATUS_APPROVED]);
 
             
    
            // Log the rejection
            activity()
            ->performedOn($lims_bus_data)
            ->causedBy(Auth::user())
            ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = Bus::STATUS_APPROVED; // Set the value of the `is_active` column

            })

            ->log('bus update approved');
            DB::commit();
    
            return redirect('buses')->with('message', 'Data updated successfully, Please wait for approval');
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
 
            $lims_bus_data = Bus ::where('id', $data['bus_id'])->lockForUpdate()->firstOrFail();
    
            // Check if the lims_bus_data is a draft or approved
            if ($lims_bus_data->isDraft()) {
                throw new Exception('Bus is waiting for Approval or rejection, you can not double update untill the Approval process is done!!');
            }

            $logs = Activity::where('subject_type', FixedAsset::class)
            ->where('subject_id', $id)
            ->where('status', Bus::STATUS_DRAFT)
            ->where('is_active',true)
            ->latest()
            ->firstOrFail();
            $properties = $logs->properties;
            $oldData = $properties['old'];
            $lims_bus_data->update($oldData);
            $lims_bus_data->status = Bus::STATUS_APPROVED;
            $lims_bus_data->save();
            $activity = Activity::find($logs->id);
            $activity->update(['is_active' => false]);
            $activity->update(['status' => Bus::STATUS_APPROVED]);
            // Log the rejection
            activity()
            ->performedOn($lims_bus_data)
            ->causedBy(Auth::user())
            ->tap(function ($activity) {
                $activity->is_active = false; // Set the value of the `is_active` column
                $activity->status = Bus::STATUS_APPROVED; // Set the value of the `is_active` column
            })
            ->log('bus update Rejected');
            DB::commit();
            return redirect('buses')->with('message', 'Data updated successfully, Please wait for approval');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'An error occurred while updating data. Please try again later.'.$e->getMessage());
        }
    }











}
