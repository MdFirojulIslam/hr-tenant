<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;

use App\Models\asset;
use App\Models\device_type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    // public function index(){
    //     $assets = asset::with('deviceType')->paginate(12);
    //     return response()->json($assets);
    // }

    public function index(Request $request)
    {
        $query = Asset::with('deviceType');

        // Filters
        if ($request->filled('device_code') && $request->device_code !== 'all') {
            $query->where('Device_Code', $request->device_code);
        }

        if ($request->filled('device_name') && $request->device_name !== 'all') {
            $query->where('Device_Name', 'like', '%' . $request->device_name . '%');
        }

        if ($request->filled('device_type') && $request->device_type !== 'all') {
            $query->whereHas('deviceType', function ($q) use ($request) {
                $q->where('Name', $request->device_type);
            });
        }

        if ($request->filled('purchase_date') && $request->purchase_date !== 'all') {
            $query->whereDate('Purchase_Date', $request->purchase_date);
        }

        if ($request->filled('serial_number') && $request->serial_number !== 'all') {
            $query->where('Serial_Number', 'like', '%' . $request->serial_number . '%');
        }

        if ($request->filled('warranty_expiration') && $request->warranty_expiration !== 'all') {
            $query->where('Warranty_Expiration', $request->warranty_expiration);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('Status', $request->status);
        }

        // Pagination
        $assets = $query->paginate(12);

        return response()->json($assets);
    }

    public function getColumnData(Request $request)
    {
        $query = Asset::with('deviceType');

        // Filters
        if ($request->filled('device_code') && $request->device_code !== 'all') {
            $query->where('Device_Code', $request->device_code);
        }

        if ($request->filled('device_name') && $request->device_name !== 'all') {
            $query->where('Device_Name', 'like', '%' . $request->device_name . '%');
        }

        if ($request->filled('device_type') && $request->device_type !== 'all') {
            $query->whereHas('deviceType', function ($q) use ($request) {
                $q->where('Name', $request->device_type);
            });
        }

        if ($request->filled('purchase_date') && $request->purchase_date !== 'all') {
            $query->whereDate('Purchase_Date', $request->purchase_date);
        }

        if ($request->filled('serial_number') && $request->serial_number !== 'all') {
            $query->where('Serial_Number', 'like', '%' . $request->serial_number . '%');
        }

        if ($request->filled('warranty_expiration') && $request->warranty_expiration !== 'all') {
            $query->where('Warranty_Expiration', $request->warranty_expiration);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('Status', $request->status);
        }

        // Retrieve filtered data
        $filteredData = $query->get();
    
        // Extract unique values for each field
        $uniqueFields = [
            'device_code' => $filteredData->pluck('Device_Code')->unique()->values(),
            'device_name' => $filteredData->pluck('Device_Name')->unique()->values(),
            'device_type' => $filteredData->pluck('deviceType.Name')->unique()->values(),
            'purchase_date' => $filteredData->pluck('Purchase_Date')->unique()->values(),
            'serial_number' => $filteredData->pluck('Serial_Number')->unique()->values(),
            'warranty_expiration' => $filteredData->pluck('Warranty_Expiration')->unique()->values(),
            'status' => $filteredData->pluck('Status')->unique()->values()
        ];

        //for remove empty strings
        $uniqueFields = array_filter($uniqueFields, function($values) {
            return !empty($values) || count($values) > 0;
        });
    
        return response()->json($uniqueFields);
    }
    
    


    public function searchAsset(Request $request)
    {
        $query = $request->input('query');
        $results = asset::with('deviceType')->where('Device_Name', 'LIKE', "%{$query}%")
            ->orWhere('Serial_Number', 'LIKE', "%{$query}%")
            ->paginate(50);
        return response()->json($results);
    }

    public function getAsset()
    {
        $assets = asset::all();
        return response()->json($assets);
    }

    public  function getDeviceTypes()
    {
        $deviceTypes = device_type::all();
        return response()->json($deviceTypes);
    }


    public function editAsset($id)
    {
        $asset = asset::findOrFail($id);
        return response()->json($asset);
    }

    public function store(Request $request)
    {
        if ($request->filled('device_type_id')) {
            $deviceTypeId = $request->device_type_id;
        } else {
            $deviceType = device_type::firstOrCreate(['Name' => $request->device_type]);
            $deviceTypeId = $deviceType->id;
        }

        $asset = asset::create([
            'Device_Name' => $request->device_name,
            'Device_Code' => $request->device_code,
            'Device_Type_Id' => $deviceTypeId,
            'Serial_Number' => $request->serial_number,
            'Description' => $request->description,
            'Purchase_Date' => $request->purchase_date,
            'Warranty_Expiration' => $request->warranty_expiration_date,
            'Price' => $request->price,
            'Status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset saved successfully!',
            'data' => $asset,
        ], 201);
    }



    public function update(Request $request, $id)
    {
        $asset = asset::findOrFail($id);

        if ($request->filled('device_type_id')) {
            $deviceTypeId = $request->device_type_id;
        } else {
            $deviceType = device_type::firstOrCreate(['Name' => $request->device_type]);
            $deviceTypeId = $deviceType->id;
        }

        $asset->update([
            'Device_Name' => $request->device_name,
            'Device_Code' => $request->device_code,
            'Device_Type_Id' => $deviceTypeId,
            'Specification' => $request->specification,
            'Description' => $request->description,
            'Serial_Number' => $request->serial_number,
            'Description' => $request->description,
            'Purchase_Date' => $request->purchase_date,
            'Warranty_Expiration' => $request->warranty_expiration_date,
            'Price' => $request->price,
            'Status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset saved successfully!',
            'data' => $asset,
        ], 201);
    }



    public function deleteAsset($id)
    {
        $asset = asset::find($id);

        if ($asset) {
            $asset->delete();
            $response = [
                'success'   =>  true,
                'message'   =>  'Asset deleted successfully',
            ];
            return response()->json($response);
        }
    }
}
