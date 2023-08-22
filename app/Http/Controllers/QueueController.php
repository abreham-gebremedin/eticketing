<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;// add this line

 class QueueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Eager load the 'bus' and 'route' relationships
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('route-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';
                $queues = Queue::with(['bus', 'route'])->orderBy('created_at','DESC')
                ->get();
     
            return view('bus.queue', compact('queues', 'all_permission'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    
    public function addToQueue(Request $request)
    {
        $busIds = $request->input('selectedBuses');
        $routeId = $request->input('route_id');
        $currentDate = Carbon::now()->toDateString();
        
        try {
            DB::beginTransaction();
            
            // Find the maximum position value for the current day
            $maxPosition = Queue::whereDate('created_at', $currentDate)
                ->where('RouteID', $routeId)
                ->max('Position');
            
            // If there are no records for the current day, start position at 1
            $position = $maxPosition ? $maxPosition + 1 : 1;
            
            foreach ($busIds as $busId) {
                // Check if the bus with the same busId and RouteID is available in the queue
                $isDuplicateBus = Queue::where('BusID', $busId)
                    ->where('RouteID', $routeId)
                    ->where('isDeparted', 0)
                    ->exists();
                
                // If not a duplicate bus, add it to the queue with the calculated position
                if (!$isDuplicateBus) {
                    Queue::create([
                        'BusID' => $busId,
                        'RouteID' => $routeId,
                        'Position' => $position,
                        // Other columns...
                    ]);
                    
                    // Increment the position for the next bus
                    $position++;
                }
            }
            
            DB::commit();
            
            return redirect('routes1')->with('message', 'Buses added to the queue successfully.');
        } catch (Exception $e) {
            DB::rollback();
            
            return redirect('routes1')->with('not_permitted', 'An error occurred while adding buses to the queue: ' . $e->getMessage());
        }
    }
    
    
    public function removeFromQueue($queueId)
    {
        try {
            DB::beginTransaction();
            
            $removedBus = Queue::findOrFail($queueId);
            $removedPosition = $removedBus->Position;
            
            // Delete the bus from the queue
            $removedBus->delete();
            
            // Decrement the position of all buses with higher position values
            Queue::where('RouteID', $removedBus->RouteID)
                ->where('Position', '>', $removedPosition)
                ->decrement('Position');
            
            DB::commit();
            
            return redirect()->back()->with('message', 'Bus removed from the queue successfully.');
        } catch (Exception $e) {
            DB::rollback();
            
            return redirect()->back()->with('message', 'An error occurred while removing the bus from the queue.');
        }
    }
    

   
    public function destroy($id)
    {
         try {
            DB::beginTransaction();
    
            $queueData = Queue::find($id);
    
            if ($queueData->isDraft()) {
                throw new Exception('This Queue is on pending status, you cannot delete it.');
            }
    
            $queueData->delete();
    
            // Log the status change and the old and new values
            activity()
                ->performedOn($queueData)
                ->causedBy(Auth::user())
                ->withProperties(['old' => $queueData])
                ->tap(function ($activity) {
                    $activity->is_active = true;
                    $activity->status = Queue::STATUS_DRAFT;
                    $activity->url = "queue";
                    $activity->is_root = 1;
                    $activity->is_deleted = 1;
                })
                ->log('Queue Deleted');
    
            DB::commit();
            $undoUrl = 'queue/restore/' . $id;
            return redirect('queue')->with('message', 'Queue Data deleted successfully. Please wait for approval or contact the System Administrator.')->with('deleteUrl', $undoUrl);
        } catch (Exception $e) {
            DB::rollBack();
            return redirect('queue')->with('not_permitted', $e->getMessage());
        }
    }


    public function restore($id)
{
    try {
        DB::beginTransaction();

        $queueData = Queue::withTrashed()->where('id', $id)->first();
        

        if (!$queueData) {
            throw new Exception('Queue not found.');
        }

        $queueData->restore();

        $logs = Activity::where('subject_type', Queue::class)
            ->where('subject_id', $id)
            ->where('status', Queue::STATUS_DRAFT)
            ->where('is_active', true)
            ->latest()
            ->firstOrFail();

        $logs->update(['is_active' => false]);
        $queueData->status = Queue::STATUS_APPROVED;
        $queueData->save();

        activity()
            ->performedOn($queueData)
            ->causedBy(Auth::user())
            ->withProperties(['old' => $queueData])
            ->tap(function ($activity) {
                $activity->is_active = false;
                $activity->is_root = 1;
                $activity->is_deleted = 1;
            })
            ->log('Queue Restored');

        DB::commit();

        return redirect('queue')->with('message', 'Data restored successfully');
    } catch (Exception $e) {
        DB::rollBack();
        return redirect('queue')->with('not_permitted', 'An error occurred while restoring your data: ' . $e->getMessage());
    }
}


public function Permanentdestroy($id)
{
    try {
        DB::beginTransaction();

        $queueData = Queue::withTrashed()->where('id', $id)->first();

        if ($queueData->isDraft()) {
            throw new Exception('Queue is not marked for permanent deletion.');
        }
         $removedPosition = $queueData->Position;
        
         $queueData->forceDelete();

        // Decrement the position of all buses with higher position values
        Queue::where('RouteID', $queueData->RouteID)
            ->where('Position', '>', $removedPosition)
            ->decrement('Position');



        $logs = Activity::where('subject_type', Queue::class)
            ->where('subject_id', $id)
            ->where('status', Queue::STATUS_DRAFT)
            ->where('is_active', true)
            ->latest()
            ->firstOrFail();

        $logs->update(['is_active' => false]);
        $logs->update(['status' => Queue::STATUS_APPROVED]);
        $queueData->status = Queue::STATUS_APPROVED;
        $queueData->save();

        activity()
            ->performedOn($queueData)
            ->causedBy(Auth::user())
            ->withProperties(['old' => $queueData])
            ->tap(function ($activity) {
                $activity->is_active = false;
                $activity->is_root = 1;
                $activity->is_deleted = 1;
            })
            ->log('Queue Deleted Permanently');

        DB::commit();
        return redirect('queue')->with('not_permitted', 'Data permanently deleted successfully');
    } catch (Exception $e) {
        DB::rollBack();
        return redirect('queue')->with('not_permitted', $e->getMessage());
    }
}


public function getFirstBus($routeId)
{
    // Get the first bus in the queue for the selected route
    $firstBus = Queue::where('RouteID', $routeId)
                     ->orderBy('Position')
                     ->first();

    if ($firstBus) {
        // Customize the response data as needed
        $busData = [
            'busNumber' => $firstBus->bus->BusNumber,
            'seatNumbers' => explode(',', $firstBus->bus->SeatNumbers), // Assuming SeatNumbers is a comma-separated string
        ];

        return response()->json($busData);
    } else {
        return response()->json(null);
    }
}

}
