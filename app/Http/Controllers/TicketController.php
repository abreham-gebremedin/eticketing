<?php

namespace App\Http\Controllers;

use App\GeneralSetting;
use App\Models\Bus;
use App\Models\Queue;
use App\Models\Route;
use App\Models\Seat;
use App\Models\Ticket;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
     
    public function create()
    {
        //
        $routes = Route::with(['departureCity', 'arrivalCity'])
        ->where('TicketOfficerID', Auth::user()->id)->get();
        return view('bus.ticketing', compact('routes'));
    }
  

    
    public function getBusDetails($routeId)
{
    // Replace with your logic to fetch bus details and seat numbers based on $routeId
    $busDetails = $this->fetchBusDetailsFromDatabase($routeId);

    return response()->json($busDetails);
}

private function fetchBusDetailsFromDatabase($routeId)
{
    // Find the bus with the smallest Position in the queue that is not departed and created today
    $bus = Queue::where('RouteID', $routeId)
        ->where('IsDeparted', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->orderBy('Position')
        ->first();

    if (!$bus) {
        return null;
    }

    $busInfo = Bus::where('id', $bus->BusID)->first();

    if (!$busInfo) {
        return null;
    }

    $queueId = $bus->id;


// Check if seat numbers for the specified queue already exist in the database
$existingSeats = Seat::where('QueueID', $queueId)->pluck('SeatNumber')->toArray();

// If no seats exist, insert seat numbers based on bus capacity
if (empty($existingSeats)) {
    $newSeats = [];
    for ($i = 1; $i <= $busInfo->Capacity; $i++) {
        $newSeats[] = [
            'QueueID' => $queueId,
            'SeatNumber' => $i,
            'IsActive' => 1, // Assuming all seats are initially active
        ];
    }
    Seat::insert($newSeats);
 }
    // Fetch the seat information for the specified queue
    $seats = Seat::where('QueueID', $queueId)->get();

    $seatData = [];
    foreach ($seats as $seat) {
        $seatData[] = [
            'seatNumber' => $seat->SeatNumber,
            'isActive' => $seat->IsActive,
        ];
    }

    return [
        'busInfo' => 'Plate Number ' . $busInfo->BusNumber . ' (Capacity: ' . $busInfo->Capacity . ')',
        'seats' => $seatData, 'queueId' => $queueId,'Capacity' => $busInfo->Capacity,
    ];
}



private function getSeatNumbers($capacity, $queueId)
{
    // Check if seat numbers for the specified queue already exist in the database
    $existingSeats = Seat::where('QueueID', $queueId)->pluck('SeatNumber')->toArray();

    // If no seats exist, insert seat numbers based on bus capacity
    if (empty($existingSeats)) {
        $newSeats = [];
        for ($i = 1; $i <= $capacity; $i++) {
            $newSeats[] = [
                'QueueID' => $queueId,
                'SeatNumber' => $i,
                'IsActive' => 1, // Assuming all seats are initially active
            ];
        }
        Seat::insert($newSeats);
        return range(1, $capacity);
    }

    // Return existing seat numbers if available
    return $existingSeats;
}


 
public function generateTicket(Request $request)
{
    $queueId = $request->input('queueId');
    $seatNumber = $request->input('seatNumber');

    try {
        DB::beginTransaction();
         if (Auth::user()->role_id >= 2) {
            $generalSetting = GeneralSetting::where('warehouse_id',Auth::user()->warehouse_id)->first();

       }else {
           # code...
           $generalSetting = GeneralSetting::findorfail(1);

          
       }

        // Fetch the necessary data based on QueueID and SeatNumber
        $queue = Queue::find($queueId);
        $bus = Bus::find($queue->BusID);
        $route = Route::with(['departureCity', 'arrivalCity'])->where('id', $queue->RouteID)->first();

        // Calculate commission fee (3% of TicketPrice)
        $commissionFee = $route->TicketPrice * ($generalSetting->one_share_value/100);
        // Create a new ticket and invoice
        $ticket = Ticket::create([
            'BusID' => $queue->BusID,
            'RouteID' => $queue->RouteID,
            'TicketOfficerID' => 1,
            'SeatNumber' => $seatNumber,
            'TicketPrice' => $route->TicketPrice,
            'CommissionFee' => $commissionFee,
            'warehouse_id' => $queue->warehouse_id, 
            'QueueID' => $queue->id,
            'Total' => $route->TicketPrice + $commissionFee,
        ]);

        // Update seat to isActive false
        $seat = Seat::where('QueueID', $queueId)
            ->where('SeatNumber', $seatNumber)
            ->first();
        if ($seat) {
            $seat->IsActive = 0;
            $seat->save();
        } else {
            Log::info('Seat not found.');
        }
        $checkseat = Seat::where('QueueID', $queueId)->get();
        $seatflag=1;

        foreach ($checkseat as $key => $seat) {
            # code...
            if ($seat->IsActive==1) {
                # code...
                $seatflag=0;
            }
        }

        if ($seatflag==1) {
            # code...
            $queue->isDeparted = 1;
            $queue->save();
            }
    

        DB::commit();

        // Generate invoice content
        $invoice = "BusNumber: {$bus->BusNumber}\n";
        $invoice .= "Route: {$route->departureCity->name} to {$route->arrivalCity->name}\n";
        $invoice .= "Price: {$route->TicketPrice}\n";
        $invoice .= "CommissionFee: {$commissionFee}\n";
        $invoice .= "TicketOfficer:".Auth::user()->name;


        // Create an array with invoice data
        $invoiceData = [
            'success' => true,
            'site_logo' => $generalSetting->site_logo,
            'company_name' => $generalSetting->company_name,
            'date' => $ticket->created_at->format('Y-m-d'),
            'busNumber' => $bus->BusNumber,
            'seatNumber' => $seatNumber,
            'routeInfo' => "{$route->departureCity->name} to {$route->arrivalCity->name}",
            'price' => $route->TicketPrice,
            'commissionFee' => $commissionFee,
            'total' => $route->TicketPrice + $commissionFee,
            'ticketOfficerEmail' => Auth::user()->email,
        ];

        return response()->json($invoiceData);
    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Error generating ticket: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Error generating ticket']);
    }
}





}
