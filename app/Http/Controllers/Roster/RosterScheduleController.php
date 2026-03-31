<?php

namespace App\Http\Controllers\Roster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\roster_schedule;
use App\Models\attendence;

class RosterScheduleController extends Controller
{
    public function index()
    {
        $result = DB::table('roster_schedules')
            ->join('employees', 'employees.id', '=', 'roster_schedules.employee_id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->select(
                'roster_schedules.id as roster_id',
                'employees.Full_Name as employee_name',
                'employees.id as employee_id',
                'employees.Employee_Id as official_id',
                'departments.id as department_id',
                'departments.Name as department_name',
                'roster_schedules.effective_from',
                'roster_schedules.time_in',
                'roster_schedules.time_out',
                'roster_schedules.work_from'
            )->get();


        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    }


    public function create(Request $request)
    {
        try {
            $user_id = Auth::user()->EID;
            if (!$user_id) {
                return response()->json([
                    'success' => false,
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'department'     => 'required|integer',
                'eid'            => 'required|integer',
                'effective_from' => 'required|date',
                'time_in'        => 'required',
                'time_out'       => 'required',
                'work_from'      => 'required|in:office,home',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $roster = new roster_schedule();
            $roster->employee_id    = $request->eid;
            $roster->effective_from = $request->effective_from;
            $roster->time_in        = $request->time_in;
            $roster->time_out       = $request->time_out;
            $roster->work_from      = $request->work_from;
            $roster->created_by = $user_id;

            $roster->save();

            return response()->json([
                'success' => true,
                'message' => 'Roster setup created successfully',
                'data'    => $roster
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $user_id = Auth::user()->EID;
            if (!$user_id) {
                return response()->json([
                    'success' => false,
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'department'     => 'required|integer',
                'eid'            => 'required|integer',
                'effective_from' => 'required|date',
                'time_in'        => 'required',
                'time_out'       => 'required',
                'work_from'      => 'required|in:office,home',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $roster = roster_schedule::where('id', $id)->first();
            if (!$roster) {
                return response()->json([
                    'success' => false,
                    'message' => 'Roster setup not found'
                ], 404);
            }
            if ($request->override) {
                roster_schedule::create([
                    'employee_id' => $request->eid,
                    'effective_from' => $request->effective_from,
                    'time_in' => $request->time_in,
                    'time_out' => $request->time_out,
                    'work_from' => $request->work_from,
                    'created_by' => $user_id,
                ]);
            } else {
                $roster->update([
                    'employee_id' => $request->eid,
                    'effective_from' => $request->effective_from,
                    'time_in' => $request->time_in,
                    'time_out' => $request->time_out,
                    'work_from' => $request->work_from,
                    'updated_by' => $user_id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Roster setup updated successfully',
                'data'    => $roster
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function reportList(Request $request)
    {
        $role_id = Auth::user()->role_id;
        $user_id = Auth::user()->EID;

        $date = $request->searchDate;


        // if ($role_id == 1) {

        //     $machine_attendance_sub = DB::table('attendences')
        //         ->select(
        //             'EID',
        //             DB::raw('MIN(Time_In) as in_time'),
        //             DB::raw('MAX(Time_Out) as out_time')
        //         )
        //         ->whereDate('Date', $date)
        //         ->groupBy('EID');

        //     $app_attendance_sub = DB::table('app_attendances')
        //         ->select(
        //             'EID',
        //             DB::raw("TIME(MIN(CASE WHEN status = 'in' THEN created_at END)) as in_time"),
        //             DB::raw("TIME(MAX(CASE WHEN status = 'out' THEN created_at END)) as out_time")
        //         )
        //         ->whereDate('created_at', $date)
        //         ->groupBy('EID');

        //     $result = DB::table('roster_schedules')
        //         ->join('employees', 'employees.id', '=', 'roster_schedules.employee_id')
        //         ->join('officials', 'officials.EID', '=', 'employees.id')
        //         ->join('departments', 'departments.id', '=', 'officials.Department_Id')
        //         ->leftJoinSub($machine_attendance_sub, 'machine_att', function ($join) {
        //             $join->on('machine_att.EID', '=', 'roster_schedules.employee_id');
        //         })
        //         ->leftJoinSub($app_attendance_sub, 'app_att', function ($join) {
        //             $join->on('app_att.EID', '=', 'roster_schedules.employee_id');
        //         })
        //         ->select(
        //             'roster_schedules.id as roster_id',
        //             'employees.Full_Name as employee_name',
        //             'employees.id as employee_id',
        //             'employees.Employee_Id as official_id',
        //             'departments.id as department_id',
        //             'departments.Name as department_name',

        //             'machine_att.in_time as actual_time_in',
        //             'machine_att.out_time as actual_time_out',

        //             'app_att.in_time as app_in_time',
        //             'app_att.out_time as app_out_time',

        //             'roster_schedules.time_in',
        //             'roster_schedules.time_out',

        //             'roster_schedules.work_from'
        //         )
        //         ->get();
        // }
        if ($role_id == 1) {

            // -------------------------
            // MACHINE ATTENDANCE SUB
            // -------------------------
            $machine_attendance_sub = DB::table('attendences')
                ->select(
                    'EID',
                    DB::raw('MIN(Time_In) as in_time'),
                    DB::raw('MAX(Time_Out) as out_time')
                )
                ->whereDate('Date', $date)
                ->groupBy('EID');

            // -------------------------
            // APP ATTENDANCE SUB
            // -------------------------
            $app_attendance_sub = DB::table('app_attendances')
                ->select(
                    'EID',
                    DB::raw("TIME(MIN(CASE WHEN LOWER(status) = 'in' THEN created_at END)) as in_time"),
                    DB::raw("TIME(MAX(CASE WHEN LOWER(status) = 'out' THEN created_at END)) as out_time")
                )
                ->whereDate('created_at', $date)
                ->groupBy('EID');

            // -------------------------
            // MAIN QUERY
            // -------------------------
            $result = DB::table('roster_schedules')
                ->join('employees', 'employees.id', '=', 'roster_schedules.employee_id')
                ->join('officials', 'officials.EID', '=', 'employees.id')
                ->join('departments', 'departments.id', '=', 'officials.Department_Id')

                ->leftJoinSub($machine_attendance_sub, 'machine_att', function ($join) {
                    $join->on('machine_att.EID', '=', 'roster_schedules.employee_id');
                })

                ->leftJoinSub($app_attendance_sub, 'app_att', function ($join) {
                    $join->on('app_att.EID', '=', 'roster_schedules.employee_id');
                })

                ->select(

                    // Employee Info
                    'roster_schedules.id as roster_id',
                    'employees.Full_Name as employee_name',
                    'employees.id as employee_id',
                    'employees.Employee_Id as official_id',
                    'departments.Name as department_name',

                    // Raw attendance
                    'machine_att.in_time as machine_in_time',
                    'machine_att.out_time as machine_out_time',
                    'app_att.in_time as app_in_time',
                    'app_att.out_time as app_out_time',

                    // Roster
                    'roster_schedules.time_in',
                    'roster_schedules.time_out',
                    'roster_schedules.work_from',

                    // Final Actual Time (App > Machine)
                    DB::raw("COALESCE(app_att.in_time, machine_att.in_time) as actual_in"),
                    DB::raw("COALESCE(app_att.out_time, machine_att.out_time) as actual_out"),

                    // IN diff
                    DB::raw("
                        TIMESTAMPDIFF(
                            MINUTE,
                            CONCAT('$date',' ', roster_schedules.time_in),
                            CONCAT('$date',' ', COALESCE(app_att.in_time, machine_att.in_time))
                        ) as in_diff_minutes
                    "),

                    // OUT diff
                    DB::raw("
                        TIMESTAMPDIFF(
                            MINUTE,
                            CONCAT('$date',' ', roster_schedules.time_out),
                            CONCAT('$date',' ', COALESCE(app_att.out_time, machine_att.out_time))
                        ) as out_diff_minutes
                    "),

                    // 🔥 WORKING HOURS
                    DB::raw("
                        TIMESTAMPDIFF(
                            MINUTE,
                            CONCAT('$date',' ', COALESCE(app_att.in_time, machine_att.in_time)),
                            CONCAT('$date',' ', COALESCE(app_att.out_time, machine_att.out_time))
                        ) as working_minutes
                    ")
                )
                ->get();
        }

        function formatMinutes($minutes)
        {
            if ($minutes === null) return null;

            $sign = $minutes < 0 ? '-' : '+';
            $minutes = abs($minutes);

            $hours = floor($minutes / 60);
            $mins = $minutes % 60;

            if ($hours == 0) {
                return $sign . ' ' . $mins . ' min';
            }

            return $sign . ' ' . $hours . ' h ' . $mins . ' min';
        }
        function formatWorkingHours($minutes)
        {
            if ($minutes === null) return null;

            if ($minutes < 0) return null;

            $hours = floor($minutes / 60);
            $mins = $minutes % 60;

            if ($hours == 0) {
                return $mins . ' min';
            }

            if ($mins == 0) {
                return $hours . ' h';
            }
            return $hours . ' h ' . $mins . ' min';
        }

        $result = $result->map(function ($row) {
            $row->in_diff = formatMinutes($row->in_diff_minutes);
            $row->out_diff = formatMinutes($row->out_diff_minutes);

            $row->working_hours = formatWorkingHours($row->working_minutes);

            $row->working_hour_decimal = $row->working_minutes !== null
                ? round($row->working_minutes / 60, 2)
                : null;

            return $row;
        });

        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    }
}
