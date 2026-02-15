<?php

namespace App\Http\Controllers\Itinerary;

use App\Models\itinerary_logs;
use App\Models\itineraries;
use App\Models\itineraries_updates;
use App\Http\Controllers\Controller;
use App\Models\employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ItineraryController extends Controller
{
    public function store(Request $request)
    {

        $user_id = Auth::user()->EID;
        $monthString = $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01';
        DB::beginTransaction();

        try {
            $log = itinerary_logs::create([
                'created_date'   => $monthString,
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
        $employees = $request->query('employee_id', null);

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
                'itinerary_logs.supervisor_remarks',
                'itinerary_logs.id as itinerary_logs_id',
            )
            ->whereYear('itinerary_logs.created_date', $year)
            ->whereMonth('itinerary_logs.created_date', $month)
            ->where(function ($query) use ($employees) {
                if (!is_null($employees)) {
                    $query->where('itinerary_logs.created_by', $employees);
                }
            })
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
            ->leftJoin('itineraries_updates', 'itineraries_updates.itineraries_id', '=', 'itineraries.id')
            ->select(
                'officials.Supervisor_Id as supervisor_id',
                'employees.Full_Name as employee_name',
                'designations.Name as designation_name',
                'itineraries.id as itinerary_id',
                'itinerary_logs.status as itinerary_status',
                'itineraries.itinerary_logs_id as itinerary_logs_id',
                'itineraries_updates.remarks as supervisor_remarks',
                'itineraries_updates.id as itinerary_update_id',
                'itineraries_updates.visit_date as updated_visit_date',
                'itineraries_updates.town_id as updated_town_id',
                'itineraries_updates.location as updated_location',
                'itineraries_updates.visit_with as updated_visit_with',
                'itineraries_updates.visit_objective as updated_visit_objective',
                'itineraries_updates.meeting_with as updated_meeting_with',
                'itineraries_updates.visit_start_time as updated_visit_start_time',
                'itineraries_updates.visit_end_time as updated_visit_end_time',
                'itineraries.*'
            )
            ->where('itinerary_logs.id', $id)
            ->orderBy('itineraries.visit_date', 'ASC')
            ->orderBy('itineraries_updates.visit_date', 'ASC')
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

    public function supervisorRemarksSet(Request $request, $id)
    {
        $user_id = Auth::user()->EID;
        $rows = $request->input('rows');
        DB::table('itinerary_logs')
            ->where('id', $id)
            ->update([
                'status' => 'RECHECK',
                'updated_by' => $user_id,
                'sup_update_date' => now(),
                'common_updated_date' => now(),
                'updated_at' => now(),
            ]);

        foreach ($rows as $row) {
            $marked = !empty($row['marked']) && $row['marked'] == true;
            if (!empty($row['remarks']) && $marked) {

                DB::table('itineraries_updates')->updateOrInsert(
                    [
                        'itineraries_id' => $row['itinerary_id'] ?? null,
                        'status'         => 'PLAN',
                    ],
                    [
                        'employee_id' => $row['employee_id'] ?? null,
                        'visit_date' => $row['date'] ?? null,
                        'town_id' => $row['town'] ?? null,
                        'location' => $row['location'] ?? null,
                        'visit_with' => $row['visitWith'] ?? null,
                        'visit_objective' => $row['objective'] ?? null,
                        'meeting_with' => $row['meetingWith'] ?? null,
                        'visit_start_time' => $row['startTime'] ?? null,
                        'visit_end_time' => $row['endTime'] ?? null,
                        'remarks' => $row['remarks'] ?? null,
                        'supervisor_id' => $user_id,
                        'updated_by' => $user_id,
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }


    public function update(Request $request, $id)
    {
        $user_id = Auth::user()->EID;
        $rows = $request->input('rows');

        foreach ($rows as $row) {

            // Condition for rejected/save/pending AND marked
            $status = $row['itinerary_status'] ?? '';
            $marked = !empty($row['marked']) && $row['marked'] == true;

            if (($status === 'REJECTED' || $status === 'SAVE' || $status === 'PENDING') && $marked) {
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
                DB::table('itinerary_logs')
                    ->where('id', $row['itinerary_logs_id']) // use the correct logs ID
                    ->update([
                        'status' => 'SAVE',  // or 'PENDING' if that’s what you want
                        'updated_by' => $user_id,
                        'common_updated_date' => now(),
                        'updated_at' => now(),
                    ]);
            }


            // Condition for approved AND marked → update itineraries_update
            else if ($status === 'RECHECK' && $marked) {

                $current = DB::table('itineraries_updates')
                    ->where('itineraries_id', $row['itinerary_id'])

                    ->latest('id')
                    ->first();

                $data = [
                    'employee_id'      => $user_id,
                    'visit_date'       => $row['date'] ?? null,
                    'town_id'          => $row['town'] ?? null,
                    'location'         => $row['location'] ?? null,
                    'visit_with'       => $row['visitWith'] ?? null,
                    'visit_objective'  => $row['objective'] ?? null,
                    'meeting_with'     => $row['meetingWith'] ?? null,
                    'visit_start_time' => $row['startTime'] ?? null,
                    'visit_end_time'   => $row['endTime'] ?? null,
                    'updated_by'       => $user_id,
                    'updated_at'       => now(),
                ];

                if ($current && $current->status === 'PLAN') {
                    // Update PLAN
                    DB::table('itineraries_updates')->where('id', $current->id)->update($data);
                } else {
                    // Create new PLAN
                    DB::table('itineraries_updates')->insert(
                        $data + [
                            'itineraries_id' => $row['itinerary_id'],
                            'status'         => 'PLAN',
                            'updated_by'     => $user_id,
                            'created_at'     => now(),
                        ]
                    );
                }

                DB::table('itinerary_logs')
                    ->where('id', $row['itinerary_logs_id'])
                    ->update([
                        'status' => 'PENDING',
                        'updated_by' => $user_id,
                        'common_updated_date' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
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
    public function getSupervisorId(Request $request, $id)
    {
        $result = DB::table('itineraries_logs')
            ->where('itineraries_logs.id', '=', $id)
            ->get();
        $supervisor = DB::table('officials')
            ->join('itinerary_logs', 'itinerary_logs.created_by', '=', 'officials.EID')
            ->select('officials.Supervisor_Id')
            ->where('officials.EID', '=', $result->created_by)
            ->get();
        return response()->json($supervisor);
    }

    public function getAllEmployees()
    {
        $user_id = Auth::user()->EID;
        $employees = DB::table('officials')
            ->join('employees', 'employees.id', '=', 'officials.EID')
            ->join('itinerary_logs', 'itinerary_logs.created_by', '=', 'officials.EID')
            ->select(
                'employees.id as employee_id',
                'employees.Full_Name as employee_name'
            )
            ->where('officials.Supervisor_id', '=', $user_id)
            ->where('itinerary_logs.status', '!=', 'SAVE')
            ->get();

        return response()->json($employees);
    }

    public function getAllSupervisors()
    {
        $user_id = Auth::user()->EID;
        $supervisors = DB::table('officials')
            ->join('itinerary_logs', 'itinerary_logs.created_by', '=', 'officials.EID')
            ->select(
                'officials.Supervisor_Id as supervisor_id'
            )
            ->where('officials.Supervisor_Id', '=', $user_id)
            ->where('itinerary_logs.status', '!=', 'SAVE')
            ->get();

        return response()->json($supervisors);
    }

    public function getDailyItems()
    {
        $user_id = Auth::user()->EID;
        $date = Carbon::today()->toDateString();

        $itineraires_actual = itineraries_updates::where('itineraries_updates.employee_id', $user_id)
            ->join('employees', 'employees.id', '=', 'itineraries_updates.employee_id')
            ->join('towns', 'towns.id', '=', 'itineraries_updates.town_id')
            ->select(
                'itineraries_updates.*',
                'itineraries_updates.itineraries_id as itinerary_id',
                'employees.Full_Name as employee_name',
                'towns.Name as town_name'
            )
            ->where('itineraries_updates.status', 'ACTUAL')
            ->whereDate('itineraries_updates.visit_date', $date)
            ->get();

        if ($itineraires_actual->isNotEmpty()) {
            return response()->json($itineraires_actual);
        }

        $itineraires_plan = itineraries_updates::where('itineraries_updates.employee_id', $user_id)
            ->join('employees', 'employees.id', '=', 'itineraries_updates.employee_id')
            ->join('towns', 'towns.id', '=', 'itineraries_updates.town_id')
            ->select(
                'itineraries_updates.*',
                'itineraries_updates.itineraries_id as itinerary_id',
                'employees.Full_Name as employee_name',
                'towns.Name as town_name'
            )
            ->where('itineraries_updates.status', 'PLAN')
            ->whereDate('itineraries_updates.visit_date', $date)
            ->get();

        if ($itineraires_plan->isNotEmpty()) {
            return response()->json($itineraires_plan);
        }

        $itineraires_response = itineraries::where('itineraries.created_by', $user_id)
            ->join('itinerary_logs', 'itineraries.itinerary_logs_id', '=', 'itinerary_logs.id')
            ->join('employees', 'itineraries.created_by', '=', 'employees.id')
            ->join('towns', 'itineraries.town_id', '=', 'towns.id')
            ->select('itineraries.*', 'itineraries.id as itinerary_id', 'itinerary_logs.status as log_status', 'employees.Full_Name as employee_name', 'towns.Name as town_name')
            ->whereDate('itineraries.visit_date', $date)
            ->get();
        if ($itineraires_response->isNotEmpty()) {
            return response()->json($itineraires_response);
        }
    }

    public function dailyItineraryUpdate(Request $request)
    {

        $user_id = Auth::user()->EID;
        $status = $request->input('updateStatus') ?? '';


        if ($status === 'ACTUAL') {
            DB::table('itineraries_updates')
                ->where('status', 'ACTUAL')
                ->where('itineraries_id', $request->input('itinerary_id'))
                ->update([
                    'town_id'           => $request->input('town') ?? null,
                    'location'          => $request->input('location') ?? null,
                    'visit_with'        => $request->input('visitWith') ?? null,
                    'visit_objective'   => $request->input('objective') ?? null,
                    'meeting_with'      => $request->input('meetingWith') ?? null,
                    'visit_start_time'  => $request->input('startTime') ?? null,
                    'visit_end_time'    => $request->input('endTime') ?? null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
        } else if ($status === 'PLAN' || empty($status)) {
            DB::table('itineraries_updates')->insert([
                'itineraries_id'   => $request->input('itinerary_id') ?? null,
                'employee_id'      => $user_id,
                'status'           => 'ACTUAL',
                'visit_date'        => $request->input('date') ?? null,
                'town_id'           => $request->input('town') ?? null,
                'location'          => $request->input('location') ?? null,
                'visit_with'        => $request->input('visitWith') ?? null,
                'visit_objective'   => $request->input('objective') ?? null,
                'meeting_with'      => $request->input('meetingWith') ?? null,
                'visit_start_time'  => $request->input('startTime') ?? null,
                'visit_end_time'    => $request->input('endTime') ?? null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function monthlyReport($employee_id, Request $request)
    {
        // XLSX only
        $format = 'xlsx';

        // Get the single date from query string
        $date = $request->query('date', now()->toDateString()); // fallback today

        // Jasper file path
        $jasperFile = storage_path('jasper/monthly_itinerary_reports.jasper');

        if (!file_exists($jasperFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Jasper report file not found.'
            ], 404);
        }

        try {
            // Generate Jasper report in memory
            $output = app('jasper')
                ->process(
                    $jasperFile,
                    false,        // output in memory
                    [$format],    // XLSX only
                    [
                        'emp'  => $employee_id, // your query expects $P{emp}
                        'date' => $date         // your query expects $P{date}
                    ],
                    config('database.connections.mysql')
                )
                ->execute();

            $fileName = "Monthly_Report_{$employee_id}_{$date}.xlsx";

            // Stream XLSX correctly
            return response()->streamDownload(function () use ($output) {
                echo $output; // raw binary
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
            ]);
        } catch (\Exception $e) {
            Log::error("Jasper report error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate XLSX report.'
            ], 500);
        }
    }






    public function getDailyAllItems(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $employeeId = $request->query('employee_id');

        $items = DB::table('itineraries as i')

            ->leftJoin('itineraries_updates as ua', function ($join) use ($date) {
                $join->on('ua.itineraries_id', '=', 'i.id')
                    ->where('ua.status', 'ACTUAL')
                    ->whereDate('ua.visit_date', $date);
            })

            ->leftJoin('itineraries_updates as up', function ($join) use ($date) {
                $join->on('up.itineraries_id', '=', 'i.id')
                    ->where('up.status', 'PLAN')
                    ->whereDate('up.visit_date', $date);
            })

            ->join('employees', 'employees.id', '=', 'i.created_by')
            ->join('towns', 'towns.id', '=', 'i.town_id')

            ->select(
                'i.id as itinerary_id',

                // date priority
                DB::raw('COALESCE(ua.visit_date, up.visit_date, i.visit_date) as visit_date'),

                'employees.Full_Name as employee_name',
                'towns.Name as town_name',

                // field priority
                DB::raw('COALESCE(ua.location, up.location, i.location) as location'),
                DB::raw('COALESCE(ua.visit_with, up.visit_with, i.visit_with) as visit_with'),
                DB::raw('COALESCE(ua.visit_objective, up.visit_objective, i.visit_objective) as visit_objective'),
                DB::raw('COALESCE(ua.meeting_with, up.meeting_with, i.meeting_with) as meeting_with'),
                DB::raw('COALESCE(ua.visit_start_time, up.visit_start_time, i.visit_start_time) as visit_start_time'),
                DB::raw('COALESCE(ua.visit_end_time, up.visit_end_time, i.visit_end_time) as visit_end_time'),

                // which source was used
                DB::raw("
            CASE
                WHEN ua.id IS NOT NULL THEN 'ACTUAL'
                WHEN up.id IS NOT NULL THEN 'PLAN'
                ELSE 'ORIGINAL'
            END as source
        ")
            )

            ->whereDate('i.visit_date', $date)

            ->when($employeeId, function ($q) use ($employeeId) {
                $q->where('employees.id', $employeeId);
            })
            ->get();
        return response()->json($items);
    }
}
