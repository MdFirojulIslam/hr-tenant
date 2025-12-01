<?php

namespace App\Http\Controllers\Itinerary;

use App\Models\itinerary_logs;
use App\Models\itineraries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItineraryController extends Controller
{
    public function store(Request $request)
    {
        $user_id = Auth::user()->EID;

        DB::beginTransaction();

        try {
            $log = itinerary_logs::create([
                'created_date'   => now(),
                'status'         => 'save',
                'created_by'     => $user_id,
            ]);
            foreach ($request->rows as $row) {
                itineraries::create([
                    'itinerary_logs_id' => $log->id,
                    'visit_date'        => $row['date'],
                    'town_id'           => $row['town'],
                    'visit_with'        => $row['visitWith'],
                    'location'          => $row['location'],
                    'visit_objective'   => $row['objective'],
                    'meeting_with'      => $row['meetingWith'],
                    'visit_start_time'  => $row['startTime'],
                    'visit_end_time'    => $row['endTime'],
                    'created_by'        => $user_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Itinerary saved successfully!',
            ]);
        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getOptions()
    {
        $user_id = Auth::user()->EID;
        $options = DB::table('towns')
            ->join('town_belongs', 'town_belongs.Town_Id', '=', 'towns.id')
            ->select(
                'towns.id as town_id',
                'towns.Name as town_name'
            )
            ->where('town_belongs.EID', $user_id)
            ->get();

        return response()->json($options);
    }


    public function getItems(Request $request)
    {
        // Get year and month from query string, default to current if not provided
        $year = $request->query('year', date('Y'));
        $month = $request->query('month', date('m'));

        $response = DB::table('itinerary_logs')
            ->join('employees', 'employees.id', '=', 'itinerary_logs.created_by')
            ->join('officials', 'officials.EID', '=', 'itinerary_logs.created_by')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->select(
                'employees.id as employee_id',
                'officials.Supervisor_id as supervisor_id',
                'officials.Department_id as department_id',
                'employees.Full_Name as employee_name',
                'designations.Name as designation_name',
                'itinerary_logs.created_date as submitted_date',
                'itinerary_logs.status',
                'itinerary_logs.id as itinerary_logs_id',
            )
            ->whereYear('itinerary_logs.created_date', $year)
            ->whereMonth('itinerary_logs.created_date', $month)
            ->get();

        return response()->json($response);
    }

    public function getItemById($id)
    {
        $item = DB::table('itinerary_logs')
            ->join('employees', 'employees.id', '=', 'itinerary_logs.created_by')
            ->join('officials', 'officials.EID', '=', 'itinerary_logs.created_by')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->join('itineraries', 'itineraries.itinerary_logs_id', '=', 'itinerary_logs.id')
            ->select(
                'officials.Supervisor_Id as supervisor_id',
                'employees.Full_Name as employee_name',
                'designations.Name as designation_name',
                'itineraries.id as itinerary_id',
                'itinerary_logs.status as itinerary_status',
                'itineraries.itinerary_logs_id as itinerary_logs_id',
                'itineraries.*'
            )
            ->where('itinerary_logs.id', $id)
            ->get();

        return response()->json($item);
    }


    public function submit($id)
    {
        $user_id = Auth::user()->id;
        $itinerary = DB::table('itinerary_logs')->where('id', $id)->first();

        if (!$itinerary) {
            return response()->json(['message' => 'Not found'], 404);
        }

        DB::table('itinerary_logs')
            ->where('id', $id)
            ->update([
                'status' => 'pending',
                'updated_by' => $user_id,
                'creator_update_date' => now(),
                'common_updated_date' => now()
            ]);

        return response()->json(['message' => 'Itinerary submitted successfully']);
    }



    public function accept($id)
    {
        $user_id = Auth::user()->id;
        $itinerary = DB::table('itinerary_logs')->where('id', $id)->first();

        if (!$itinerary) {
            return response()->json(['message' => 'Not found'], 404);
        }

        DB::table('itinerary_logs')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'updated_by' => $user_id,
                'sup_update_date' => now(),
                'common_updated_date' => now()
            ]);

        return response()->json(['message' => 'Itinerary accepted']);
    }



    public function reject(Request $request, $id)
    {
        $user_id = Auth::user()->id;
        $itinerary = DB::table('itinerary_logs')->where('id', $id)->first();

        if (!$itinerary) {
            return response()->json(['message' => 'Not found'], 404);
        }

        DB::table('itinerary_logs')
            ->where('id', $id)
            ->update([
                'status' => 'rejected',
                'supervisor_remarks' => $request->remarks ?? null,
                'updated_by' => $user_id,
                'sup_update_date' => now(),
                'common_updated_date' => now()
            ]);

        return response()->json(['message' => 'Itinerary rejected']);
    }

    public function update(Request $request, $id)
    {
        $user_id = Auth::user()->EID;
        $rows = $request->input('rows');

        foreach ($rows as $row) {

            // Condition for rejected/save/pending AND marked
            $status = $row['itinerary_status'] ?? '';
            $marked = !empty($row['marked']) && $row['marked'] == true;

            if (($status === 'rejected' || $status === 'save' || $status === 'pending') && $marked) {
                DB::table('itineraries')
                    ->where('id', $row['itinerary_id']) // condition
                    ->update([
                        'visit_date' => $row['date'] ?? null,
                        'town_id' => $row['town'] ?? null,
                        'location' => $row['location'] ?? null,
                        'visit_with' => $row['visitWith'] ?? null,
                        'visit_objective' => $row['objective'] ?? null,
                        'meeting_with' => $row['meetingWith'] ?? null,
                        'visit_start_time' => $row['startTime'] ?? null,
                        'visit_end_time' => $row['endTime'] ?? null,
                        'created_by' => $user_id,
                        'updated_at' => now(),
                    ]);
            }


            // Condition for approved AND marked → update itineraries_update
            else if ($status === 'approved' && $marked) {
                DB::table('itineraries_update')->updateOrInsert(
                    ['itineraries_id' => $row['itinerary_id'] ?? null],
                    [
                        'itineraries_id' => $row['itinerary_id'] ?? null,
                        'visit_date' => $row['date'] ?? null,
                        'town_id' => $row['town'] ?? null,
                        'location' => $row['location'] ?? null,
                        'visit_with' => $row['visitWith'] ?? null,
                        'visit_objective' => $row['objective'] ?? null,
                        'meeting_with' => $row['meetingWith'] ?? null,
                        'visit_start_time' => $row['startTime'] ?? null,
                        'visit_end_time' => $row['endTime'] ?? null,
                        'created_by' => $user_id,
                        'updated_at' => now(),
                    ]
                );
            }
            // Otherwise, insert new row into main table
            else {
                DB::table('itineraries')->insert([
                    'visit_date' => $row['date'],
                    'itinerary_logs_id' => $row['itinerary_logs_id'],
                    'town_id' => $row['town'],
                    'location' => $row['location'],
                    'visit_with' => $row['visitWith'],
                    'visit_objective' => $row['objective'],
                    'meeting_with' => $row['meetingWith'],
                    'visit_start_time' => $row['startTime'],
                    'visit_end_time' => $row['endTime'],
                    'created_by' => $user_id,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
