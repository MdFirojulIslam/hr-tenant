<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\vehicle;
use App\Models\vehicle_requisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{

    public function index()
    {
        $vehicles = vehicle::with('driver', 'supervisor')->get();
        return response()->json($vehicles);
    }


    public function store(Request $request)
    {
        $lastId = vehicle::latest('Vehicle_Id')->value('Vehicle_Id');
        
        $vehicle = new vehicle();
        $vehicle->Vehicle_Id = $lastId + 1;
        $vehicle->Name = $request->vehicle_name;
        $vehicle->Model = $request->model_name;
        $vehicle->Reg_No = $request->reg_no;
        $vehicle->Engine_No = $request->engine_no;
        $vehicle->Chassis_No = $request->chassis_no;
        $vehicle->Tracking_No = $request->tracking_no;
        $vehicle->Driver_Id = $request->driver_id;
        $vehicle->Supervisor_Id = $request->supervisor_id;
        $vehicle->Tax_Expire_Date = $request->tax_expire_date;
        $vehicle->Fitness_Expire_Date = $request->fitness_expire_date;

        $file_path = null;

        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'required|mimes:jpg,jpeg,png,csv,txt,xlx,xls,xlsx,pdf|max:512'
            ]);

            $file_name = time() . '_' . $request->image->getClientOriginalName();
            $file_path = $request->file('image')->storeAs('uploads/vehicle', $file_name, 'public');
            $vehicle->image = '/storage/' . $file_path;
        }

        $vehicle->save();

        return response()->json(['success' => true,]);
    }


    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);
        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Vehicle not found'], 404);
        }

        $vehicle->Vehicle_Id = $request->vehicle_id;
        $vehicle->Name = $request->vehicle_name;
        $vehicle->Model = $request->model_name;
        $vehicle->Reg_No = $request->reg_no;
        $vehicle->Engine_No = $request->engine_no;
        $vehicle->Chassis_No = $request->chassis_no;
        $vehicle->Tracking_No = $request->tracking_no;
        $vehicle->Driver_Id = $request->driver_id;
        $vehicle->Supervisor_Id = $request->supervisor_id;
        $vehicle->Tax_Expire_Date = $request->tax_expire_date;
        $vehicle->Fitness_Expire_Date = $request->fitness_expire_date;

        if ($request->hasFile('image')) {
            if ($vehicle->image && file_exists(public_path($vehicle->image))) {
                unlink(public_path($vehicle->image));
            }
            $request->validate([
                'image' => 'required|mimes:jpg,jpeg,png,csv,txt,xlx,xls,xlsx,pdf|max:512'
            ]);

            $file_name = time() . '_' . $request->image->getClientOriginalName();
            $file_path = $request->file('image')->storeAs('uploads/vehicle', $file_name, 'public');
            $vehicle->image = '/storage/' . $file_path;
        }

        $vehicle->save();

        return response()->json(['success' => true, 'message' => 'Vehicle updated successfully']);
    }



    public function getVehicle($id)
    {
        $vehicle_info = vehicle::with('driver')->where('id', $id)->first();
        return response()->json($vehicle_info);
    }

    // public function getVehicleRequisition()
    // {
    //     $requisition_info = vehicle_requisition::with('vehicle', 'vehicle.supervisor', 'vehicle.driver')
    //         ->orderBy('From_Date', 'desc')->get();
    //     return response()->json($requisition_info);
    // }


    public function addVehicleRequisition(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'vehicle_id' => 'required|integer',
            'from_date' => 'required',
            'to_date' => 'required',
            'pickup_time' => 'required',
            'drop_time' => 'required',
            'pickup_location' => 'required|string',
            'drop_location' => 'required|string',
            'purpose' => 'required|string',
        ]);

        $existingBooking = vehicle_requisition::where('Vehicle_Id', $request->vehicle_id)
            ->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('From_Date', '<=', $request->to_date)
                        ->where('To_Date', '>=', $request->from_date);
                })->where(function ($q) use ($request) {
                    $q->where('Pickup_Time', '<', $request->drop_time)
                        ->where('Drop_Time', '>', $request->pickup_time);
                });
            })
            ->exists();


        if ($existingBooking) {
            return response()->json(['error' => 'This vehicle is already booked for the selected time!']);
        }

        vehicle_requisition::create([
            'EID'  => $request->user_id,
            'Vehicle_Id'  => $request->vehicle_id,
            'From_Date'  => $request->from_date,
            'To_Date'  => $request->to_date,
            'Pickup_Time'  => $request->pickup_time,
            'Drop_Time'  => $request->drop_time,
            'Pickup_Location'  => $request->pickup_location,
            'Drop_Location'  => $request->drop_location,
            'Purpose'  => $request->purpose,
            'Description'  => $request->description,
        ]);

        return response()->json(['success' => true,]);
    }


    public function getVehicleBookings($vehicle_id)
    {
        $bookings = vehicle_requisition::where('Vehicle_Id', $vehicle_id)
            ->select('From_Date', 'To_Date', 'Pickup_Time', 'Drop_Time')
            ->where('Status', 'complete')
            ->get();

        return response()->json($bookings);
    }



    public function getAllVehicleRequisition()
    {
        $subordinates = vehicle_requisition::with('vehicle', 'vehicle.supervisor', 'vehicle.driver', 'employee', 'employee.official')
            ->orderBy('From_Date', 'desc')->get();
        return $subordinates;
    }

    public function getSubordinates($userId)
    {
        $subordinates = [];
        $allSubordinateIds = [];
        $queue = [$userId];

        // Step 1: Fetch the hierarchy of subordinates
        while (!empty($queue)) {
            $currentUserId = array_shift($queue);

            // Get the subordinates of the current user
            $subordinateUsers = DB::table('officials')
                ->join('employees', 'officials.EID', '=', 'employees.id')
                ->select('employees.id as EID', 'employees.Full_Name')
                ->where('officials.Supervisor_Id', '=', $currentUserId)
                ->where('officials.Status', '=', 'N')
                ->get();

            foreach ($subordinateUsers as $subordinate) {
                if (!in_array($subordinate->EID, $allSubordinateIds)) {
                    $allSubordinateIds[] = $subordinate->EID;
                    $queue[] = $subordinate->EID;
                }
            }
        }

        // Step 2: Fetch leave records for all subordinates
        if (!empty($allSubordinateIds)) {
            $subordinates = vehicle_requisition::with('vehicle', 'vehicle.supervisor', 'vehicle.driver')
                ->whereIn('vehicle_requisitions.EID', $allSubordinateIds)
                ->orderBy('From_Date', 'desc')->get();
        }
        return $subordinates;
    }



    public function getVehicleRequisition()
    {
        $user = Auth::user();

        if ($user) {
            if ($user->role_id == 1 || $user->role_id == 2) {
                $vehicle_requisition = $this->getAllVehicleRequisition();
            } else {
                $vehicle_requisition = $this->getSubordinates($user->EID);
            }

            return response()->json($vehicle_requisition);
        } else {
            return response()->json('Vehicle Requisition not found');
        }
    }


    public function vehicleRequisitionApproval(Request $request)
    {
        $requisition_info = vehicle_requisition::findorfail($request->id);

        $requisition_info->update([
            'Status' => $request->status,
        ]);

        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully inserted',
        ];

        return response()->json($response);
    }


    public function getUserRequisition()
    {
        $userId = Auth::user()->EID;

        $requisition_info = vehicle_requisition::with('vehicle', 'vehicle.driver')->where('EID', $userId)->get();

        return response()->json($requisition_info);
    }
}
