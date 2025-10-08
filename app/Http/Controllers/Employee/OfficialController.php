<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

use App\Models\employee;
use App\Models\official;
use App\Models\town_belong;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OfficialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate input
        $request->validate([
            'departmentId' => 'required',
            'designationId' => 'required',
            'employeeGrade' => 'required',
            'employeeTypeId' => 'required',
        ], [
            'departmentId.required' => 'The Department Name is required.',
            'designationId.required' => 'The Designation is required.',
            'employeeGrade.required' => 'The Employee Grade is required.',
            'employeeTypeId.required' => 'The Employee Type is required.',
        ]);

        DB::beginTransaction();
        try {
            $userId = Auth::user()->EID;
            $store = official::create([
                'EID' => $request->input('eid'),
                'Department_Id' => $request->input('departmentId'),
                'Designation_Id' => $request->input('designationId'),
                'Employee_Grade' => $request->input('employeeGrade'),
                'Area_Id' => $request->input('areaId'),
                'Territory_Id' => $request->input('territoryId'),
                'Employee_type_Id' => $request->input('employeeTypeId'),
                'Supervisor_Id' => isset($request->supervisor['id']) ? $request->supervisor['id'] : null,
                'DOJ' => $request->input('doj'),
                'DOL' => $request->input('dol'),
                'Provation_period' => $request->input('provationPeriod'),
                'DOC' => $request->input('doc'),
                'Job_Location_Id' => $request->input('jobLocation'),
                'Shift' => $request->input('shift'),
                'Status' => $request->input('status'),
                'created_by' => $userId,
                'updated_by' => 0,
            ]);

            // Handle town IDs
            $townIds = $request->townIds;

            if (!empty($townIds)) {
                foreach ($townIds as $town) {
                    town_belong::create([
                        'EID' => $request->input('eid'),
                        'Town_Id' => $town['id'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully inserted',
            ]);
        } catch (\Exception $e) { // Catch exceptions
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error while inserting: ' . $e->getMessage(), // Add error details
            ]);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(official $official)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $official = official::with([
            'area',
            'department',
            'designation',
            'employeeType',
            'territory',
            'supervisor',
            'branch',
        ])->where('EID', $id)->first();


        if (!$official) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json($official);
    }

    public function editTown($id)
    {
        $towns = town_belong::with('town')->where('EID', $id)->get();
        return response()->json($towns);
    }



    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, $id)
    {
        // Validate the input
        $request->validate([
            'eid' => 'required',
            'departmentId' => 'required',
            'designationId' => 'required',
            'employeeGrade' => 'required',
            'employeeTypeId' => 'required',
        ], [
            'eid.required' => 'The Employee ID is required.',
            'departmentId.required' => 'The Department Name is required.',
            'designationId.required' => 'The Designation is required.',
            'employeeGrade.required' => 'The Employee Grade is required.',
            'employeeTypeId.required' => 'The Employee Type is required.',
        ]);

        DB::beginTransaction();

        try {
            // Find the official record
            $official_id = DB::table('officials')
                ->select('id')
                ->where('EID', $id)
                ->first();

            if (!$official_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Official not found.',
                ], 404);
            }

            $userId = Auth::user()->EID;

            $official = official::findOrFail($official_id->id);

            // Update the official record
            $official->update([
                'EID' => $request->input('eid'),
                'Department_Id' => $request->input('departmentId'),
                'Designation_Id' => $request->input('designationId'),
                'Employee_Grade' => $request->input('employeeGrade'),
                'Area_Id' => $request->input('areaId'),
                'Territory_Id' => $request->input('territoryId'),
                'Employee_type_Id' => $request->input('employeeTypeId'),
                'Supervisor_Id' => isset($request->supervisor['id']) ? $request->supervisor['id'] : null,
                'DOJ' => $request->input('doj'),
                'DOL' => $request->input('dol'),
                'Provation_period' => $request->input('provationPeriod'),
                'DOC' => $request->input('doc'),
                'Job_Location_Id' => $request->input('jobLocation'),
                'Shift' => $request->input('shift'),
                'Status' => $request->input('status'),
                'updated_by' => $userId,
            ]);

            $user = User::where('EID', $official->EID)->first();

            if($user && $request->status == 'Y'){
                $user->active = 0;
                $user->save();
            }

            if($user && $request->status == 'N'){
                $user->active = 1;
                $user->save();
            }

            $townIds = $request->townIds;

            if (!empty($townIds)) {
                // Remove existing town_belong entries for the employee
                town_belong::where('EID', $request->input('eid'))->delete();

                // Insert new town_belong entries
                foreach ($townIds as $town) {
                    town_belong::create([
                        'EID' => $request->input('eid'),
                        'Town_Id' => $town['id'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully updated.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error while updating: ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(official $official)
    {
        //
    }
}
