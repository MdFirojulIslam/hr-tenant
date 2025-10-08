<?php

namespace App\Http\Controllers\Asset;
use App\Http\Controllers\Controller;

use App\Models\asset;
use Illuminate\Http\Request;
use App\Models\employee_asset;
use Illuminate\Support\Facades\Auth;

class EmployeeAssetController extends Controller
{
    public function index(){
        $employee_assets = employee_asset::select('employee_assets.id','departments.id as Dept_Id', 'departments.Name as Dept_Name', 'employees.Full_Name', 'assets.Device_Name', 'employee_assets.Assigned_Date', 'employee_assets.Return_Date', 'employee_assets.Remarks', 'assets.Serial_Number')
        ->join('employees', 'employees.id', '=', 'employee_assets.EID')
        ->join('officials', 'employees.id', '=', 'officials.EID')
        ->join('departments', 'officials.Department_Id', '=', 'departments.id')
        ->join('assets', 'employee_assets.Device_Id', '=', 'assets.id')
        ->get();
        return response()->json($employee_assets);
    }


    public function allAsset(){
        $assets = asset::all();
        return response()->json($assets);
    }

    public function getAvailableAsset(){
        $assets = asset::where('Status', 'Available')->get();
        return response()->json($assets);
    }

    
    public function getAssetId(){
        $asset_id = asset::select('Device_Code')
            ->orderBy('Device_Code', 'desc')
            ->first();
        return response()->json($asset_id);
    }

    public function store(Request $request){
        $userId = Auth::user()->EID;
        $emp_asset = employee_asset::create([
            'EID' => $request->employee_Id,
            'Device_Id' => $request->device_id,
            'Assigned_Date' => $request->assignedDate,
            'Return_Date' => $request->returnedDate,
            'Remarks' => $request->remarks,
            'created_by' => $userId,
            'updated_by' => 0,
        ]);

        $asset = asset::where('id', $request->device_id)->first();
        $asset->update([
            'Status' => $request->status,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Asset saved successfully!',
        ], 201);
    }


    public function editEmpAsset($id){
        $employee_assets = employee_asset::select('employee_assets.id', 'departments.id as Dept_Id', 'employees.id as Emp_Id', 'assets.id as Asset_Id', 'employee_assets.Assigned_Date', 'employee_assets.Return_Date', 'employee_assets.Remarks' , 'assets.Status')
        ->join('employees', 'employees.id', '=', 'employee_assets.EID')
        ->join('officials', 'employees.id', '=', 'officials.EID')
        ->join('departments', 'officials.Department_Id', '=', 'departments.id')
        ->join('assets', 'employee_assets.Device_Id', '=', 'assets.id')
        ->where('employee_assets.id', $id)
        ->first();

        return response()->json($employee_assets);
    }

    public function update(Request $request, $id){
        $userId = Auth::user()->EID;
        $employee_asset = employee_asset::find($id);

        $employee_asset->update([
            'EID' => $request->employee_Id,
            'Device_Id' => $request->device_id,
            'Assigned_Date' => $request->assignedDate,
            'Return_Date' => $request->returnedDate,
            'Remarks' => $request->remarks,
            'updated_by' => $userId,
        ]);

        $asset = asset::where('id', $request->device_id)->first();
        $asset->update([
            'Status' => $request->status,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Asset saved successfully!'
        ], 201);
    }
}
