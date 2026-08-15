<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\vehicle;
use App\Models\vehicle_requisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileVehicleRequisitionController extends Controller
{
    /**
     * Vehicles available for requisition.
     */
    public function vehicles()
    {
        $vehicles = vehicle::with([
            'driver:id,Full_Name,Contact_No,Employee_Id',
            'supervisor:id,Full_Name,Employee_Id',
        ])->orderBy('Name')->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles->map(fn (vehicle $row) => $this->formatVehicle($row)),
        ]);
    }

    public function vehicle($id)
    {
        $row = vehicle::with([
            'driver:id,Full_Name,Contact_No,Employee_Id',
            'supervisor:id,Full_Name,Employee_Id',
        ])->find($id);

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatVehicle($row),
        ]);
    }

    public function bookings($id)
    {
        $bookings = vehicle_requisition::where('Vehicle_Id', $id)
            ->where('Status', 'complete')
            ->select('From_Date', 'To_Date', 'Pickup_Time', 'Drop_Time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Own requisitions for the authenticated employee.
     */
    public function index()
    {
        $employeeId = Auth::user()->EID;

        $rows = vehicle_requisition::with([
            'vehicle.driver:id,Full_Name,Contact_No,Employee_Id',
        ])
            ->where('EID', $employeeId)
            ->orderByDesc('From_Date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn (vehicle_requisition $row) => $this->formatRequisition($row)),
        ]);
    }

    public function store(Request $request)
    {
        $employeeId = Auth::user()->EID;

        if ($request->has('user_id') || $request->has('eid') || $request->has('EID')) {
            $request->request->remove('user_id');
            $request->request->remove('eid');
            $request->request->remove('EID');
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'pickup_time' => 'required',
            'drop_time' => 'required',
            'pickup_location' => 'required|string|max:255',
            'drop_location' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $existingBooking = vehicle_requisition::where('Vehicle_Id', $validated['vehicle_id'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('From_Date', '<=', $validated['to_date'])
                        ->where('To_Date', '>=', $validated['from_date']);
                })->where(function ($q) use ($validated) {
                    $q->where('Pickup_Time', '<', $validated['drop_time'])
                        ->where('Drop_Time', '>', $validated['pickup_time']);
                });
            })
            ->where(function ($q) {
                $q->whereNull('Status')
                    ->orWhereNotIn('Status', ['cancle', 'cancel', 'rejected']);
            })
            ->exists();

        if ($existingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'This vehicle is already booked for the selected time.',
            ], 422);
        }

        $row = vehicle_requisition::create([
            'EID' => $employeeId,
            'Vehicle_Id' => $validated['vehicle_id'],
            'From_Date' => $validated['from_date'],
            'To_Date' => $validated['to_date'],
            'Pickup_Time' => $validated['pickup_time'],
            'Drop_Time' => $validated['drop_time'],
            'Pickup_Location' => $validated['pickup_location'],
            'Drop_Location' => $validated['drop_location'],
            'Purpose' => $validated['purpose'],
            'Description' => $validated['description'] ?? null,
            'Status' => 'pending',
        ]);

        $row->load(['vehicle.driver:id,Full_Name,Contact_No,Employee_Id']);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle requisition submitted.',
            'data' => $this->formatRequisition($row),
        ]);
    }

    /**
     * Cancel own pending requisition (same action as website cancel).
     */
    public function cancel(Request $request, $id)
    {
        $employeeId = Auth::user()->EID;

        $row = vehicle_requisition::where('id', $id)->where('EID', $employeeId)->first();
        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Requisition not found.',
            ], 404);
        }

        $status = strtolower((string) $row->Status);
        if (in_array($status, ['complete', 'cancle', 'cancel'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This requisition can no longer be cancelled.',
            ], 422);
        }

        $row->Status = 'cancle';
        $row->save();
        $row->load(['vehicle.driver:id,Full_Name,Contact_No,Employee_Id']);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle requisition cancelled.',
            'data' => $this->formatRequisition($row),
        ]);
    }

    private function formatVehicle(vehicle $row): array
    {
        $imageUrl = null;
        if ($row->image) {
            $image = $row->image;
            $imageUrl = str_starts_with($image, 'http')
                ? $image
                : asset(ltrim($image, '/'));
        }

        return [
            'id' => $row->id,
            'vehicle_id' => $row->Vehicle_Id,
            'name' => $row->Name,
            'model' => $row->Model,
            'reg_no' => $row->Reg_No,
            'image' => $row->image,
            'image_url' => $imageUrl,
            'driver_id' => $row->Driver_Id,
            'driver_name' => $row->driver?->Full_Name,
            'driver_contact' => $row->driver?->Contact_No,
            'supervisor_id' => $row->Supervisor_Id,
            'supervisor_name' => $row->supervisor?->Full_Name,
        ];
    }

    private function formatRequisition(vehicle_requisition $row): array
    {
        return [
            'id' => $row->id,
            'eid' => $row->EID,
            'vehicle_id' => $row->Vehicle_Id,
            'vehicle_name' => $row->vehicle?->Name,
            'driver_name' => $row->vehicle?->driver?->Full_Name,
            'from_date' => $row->From_Date,
            'to_date' => $row->To_Date,
            'pickup_time' => $row->Pickup_Time,
            'drop_time' => $row->Drop_Time,
            'pickup_location' => $row->Pickup_Location,
            'drop_location' => $row->Drop_Location,
            'purpose' => $row->Purpose,
            'description' => $row->Description,
            'status' => $row->Status,
            'created_at' => optional($row->created_at)->toDateTimeString(),
        ];
    }
}
