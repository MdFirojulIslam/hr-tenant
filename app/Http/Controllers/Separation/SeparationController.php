<?php

namespace App\Http\Controllers\Separation;

use App\Models\official;
use App\Models\separation_request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\separation_info;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeparationController extends Controller
{
    public function index(Request $request)
    {
        $eid = $request->id;

        $response = official::select('DOJ as date_of_joining')
            ->where('officials.EID', '=', $eid)
            ->get();

        return response()->json($response);
    }

    public function store(Request $request)
    {
        $employee_id = Auth::user()->EID;
        DB::beginTransaction();

        $request->validate([
            'date_of_separation' => 'required',
            'reason' => 'required'
        ]);

        $separation_request = separation_request::create([
            'employee_id' => $employee_id,
            'separation_date' => $request->input('date_of_separation'),
            'separation_type' => $request->input('reason'),
            'reason_for_separation' => $request->input('cause'),
        ]);

        // Here needed separations request id
        if ($separation_request) {
            $separation_info = separation_info::create([
                'employee_id' => $employee_id,
                'separation_request_id' => $separation_request->id,
                'status' => 'PENDING'
            ]);
        }

        DB::commit();

        $response = [
            'success'   =>  true,
            'message'   =>  'Separation request submitted successfully',
            'id'        =>  $separation_request->id,
        ];

        return response()->json($response);
    }


    public function separationList()
    {

        $separationRequests = DB::table('separation_requests as sr')
            ->join('separation_infos as si', 'si.separation_request_id', '=', 'sr.id')
            ->join('employees', 'employees.id', '=', 'sr.employee_id')
            ->join('officials', 'sr.employee_id', '=', 'officials.EID')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->select(
                'sr.id as separation_request_id',
                'employees.Full_Name as employee_name',
                'departments.Name as department_name',
                'sr.separation_type',
                'sr.reason_for_separation',
                'sr.separation_date',
                'si.status'
            )
            ->get();


        return response()->json($separationRequests);
    }
}
