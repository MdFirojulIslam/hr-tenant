<?php


namespace App\Http\Controllers\Roster;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\roster_schedule;
use App\Exports\AttendanceReport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\CarbonPeriod;


class RosterScheduleController extends Controller
{
    public function index(Request $request)
    {


        $query = DB::table('roster_schedules')
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
                'roster_schedules.day_name',
                'roster_schedules.effective_from',
                'roster_schedules.effective_to',
                'roster_schedules.time_in',
                'roster_schedules.time_out',
                'roster_schedules.work_from',
                'roster_schedules.weekend'
            );

        // -------------------------
        // FILTERS
        // -------------------------
        if ($request->filled('department_id')) {
            $query->where('departments.id', $request->department_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employees.id', $request->employee_id);
        }

        if ($request->filled('day_name')) {
            $query->where('roster_schedules.day_name', $request->day_name);
        }

        // -------------------------
        // ORDERING
        // -------------------------
        $query->orderBy('employees.id')
            ->orderByRaw("
            FIELD(roster_schedules.day_name,
                'Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'
            )
        ");

        // -------------------------
        // PAGINATION
        // -------------------------
        $perPage = $request->get('per_page', 40);
        $data = $query->paginate($perPage);


        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }


    public function show($id)
    {

        $roster = roster_schedule::where('id', $id)->first();


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


                // ✅ IMPORTANT ADDITIONS
                'roster_schedules.day_name',
                'roster_schedules.effective_from',
                'roster_schedules.effective_to',


                'roster_schedules.time_in',
                'roster_schedules.time_out',
                'roster_schedules.work_from',
                'roster_schedules.weekend'
            )
            ->where('roster_schedules.employee_id', $roster->employee_id)
            ->get();



        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Roster setup not found'
            ], 404);
        }


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
                    'message' => 'Unauthorized'
                ], 401);
            }


            // -------------------------
            // VALIDATION
            // -------------------------
            $validator = Validator::make($request->all(), [
                'eid'            => 'required|integer',
                'effective_from' => 'required|date',
                'effective_to'   => 'required|date|after_or_equal:effective_from',
                'roster_days'    => 'required|array|min:1',


                'roster_days.*.day_name'  => 'required|string',
                'roster_days.*.work_from' => 'nullable|in:office,home',
                'roster_days.*.is_weekend' => 'required|boolean',
                'roster_days.*.time_in'   => 'nullable',
                'roster_days.*.time_out'  => 'nullable',
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }


            // -------------------------
            // OVERLAP CHECK
            // -------------------------
            $exists = DB::table('roster_schedules')
                ->where('employee_id', $request->eid)
                ->where(function ($q) use ($request) {
                    $q->whereBetween('effective_from', [$request->effective_from, $request->effective_to])
                        ->orWhereBetween('effective_to', [$request->effective_from, $request->effective_to])
                        ->orWhere(function ($q2) use ($request) {
                            $q2->where('effective_from', '<=', $request->effective_from)
                                ->where('effective_to', '>=', $request->effective_to);
                        });
                })
                ->exists();


            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Roster already exists for this date range'
                ], 422);
            }


            // -------------------------
            // TIME VALIDATION
            // -------------------------
            foreach ($request->roster_days as $day) {


                $isWeekend = $day['is_weekend'] ?? false;


                if (!$isWeekend) {


                    if (empty($day['time_in']) || empty($day['time_out'])) {
                        return response()->json([
                            'success' => false,
                            'message' => $day['day_name'] . ' requires Time In & Time Out'
                        ], 422);
                    }


                    if (strtotime($day['time_out']) <= strtotime($day['time_in'])) {
                        return response()->json([
                            'success' => false,
                            'message' => $day['day_name'] . ' Time Out must be greater than Time In'
                        ], 422);
                    }
                }
            }


            // -------------------------
            // GENERATE ROSTER ID 🔥
            // -------------------------
            $rosterId = DB::table('roster_schedules')->max('roster_id') + 1;


            // -------------------------
            // PREPARE DATA
            // -------------------------
            $data = [];


            foreach ($request->roster_days as $day) {


                $isWeekend = $day['is_weekend'] ?? false;


                $data[] = [
                    'roster_id'      => $rosterId, // ✅ important


                    'employee_id'    => $request->eid,
                    'day_name'       => $day['day_name'],


                    'time_in'        => $isWeekend ? null : $day['time_in'],
                    'time_out'       => $isWeekend ? null : $day['time_out'],
                    'work_from'      => $isWeekend ? null : $day['work_from'],


                    'weekend'        => $isWeekend ? 1 : 0,


                    'effective_from' => $request->effective_from,
                    'effective_to'   => $request->effective_to,


                    'created_by'     => $user_id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }


            roster_schedule::insert($data);


            return response()->json([
                'success' => true,
                'message' => 'Roster setup created successfully',
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
                    'message' => 'Unauthorized'
                ], 401);
            }


            // -------------------------
            // VALIDATION
            // -------------------------
            $validator = Validator::make($request->all(), [
                'eid'            => 'required|integer',
                'effective_from' => 'required|date',
                'effective_to'   => 'required|date|after_or_equal:effective_from',
                'roster_days'    => 'required|array|min:1',


                'roster_days.*.day_name'  => 'required|string',
                'roster_days.*.work_from' => 'nullable|in:office,home',
                'roster_days.*.is_weekend' => 'required|boolean',
                'roster_days.*.time_in'   => 'nullable',
                'roster_days.*.time_out'  => 'nullable',
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }


            // -------------------------
            // GET CURRENT ROSTER (GROUP)
            // -------------------------
            $currentRoster = DB::table('roster_schedules')
                ->where('id', $id)
                ->first();


            if (!$currentRoster) {
                return response()->json([
                    'success' => false,
                    'message' => 'Roster not found'
                ], 404);
            }


            $rosterId = $currentRoster->roster_id;


            // -------------------------
            // OVERLAP CHECK (FIXED 🔥)
            // -------------------------
            $exists = DB::table('roster_schedules')
                ->where('employee_id', $request->eid)


                // ❗ exclude same roster group
                ->where('roster_id', '!=', $rosterId)


                ->where(function ($q) use ($request) {
                    $q->whereBetween('effective_from', [$request->effective_from, $request->effective_to])
                        ->orWhereBetween('effective_to', [$request->effective_from, $request->effective_to])
                        ->orWhere(function ($q2) use ($request) {
                            $q2->where('effective_from', '<=', $request->effective_from)
                                ->where('effective_to', '>=', $request->effective_to);
                        });
                })
                ->exists();


            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Another roster exists in this date range'
                ], 422);
            }


            // -------------------------
            // TIME VALIDATION
            // -------------------------
            foreach ($request->roster_days as $day) {


                $isWeekend = $day['is_weekend'] ?? false;


                if (!$isWeekend) {


                    if (empty($day['time_in']) || empty($day['time_out'])) {
                        return response()->json([
                            'success' => false,
                            'message' => $day['day_name'] . ' requires Time In & Time Out'
                        ], 422);
                    }


                    if (strtotime($day['time_out']) <= strtotime($day['time_in'])) {
                        return response()->json([
                            'success' => false,
                            'message' => $day['day_name'] . ' Time Out must be greater than Time In'
                        ], 422);
                    }
                }
            }


            // -------------------------
            // UPDATE (GROUP BASED 🔥)
            // -------------------------
            foreach ($request->roster_days as $day) {


                $isWeekend = $day['is_weekend'] ?? false;


                roster_schedule::where('roster_id', $rosterId)
                    ->where('day_name', $day['day_name'])
                    ->update([
                        'time_in'        => $isWeekend ? null : $day['time_in'],
                        'time_out'       => $isWeekend ? null : $day['time_out'],
                        'work_from'      => $isWeekend ? null : $day['work_from'],
                        'weekend'        => $isWeekend ? 1 : 0,


                        'effective_from' => $request->effective_from,
                        'effective_to'   => $request->effective_to,


                        'updated_by'     => $user_id,
                        'updated_at'     => now(),
                    ]);
            }


            return response()->json([
                'success' => true,
                'message' => 'Roster updated successfully'
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
        $date = $request->searchDate;


        $dayName = \Carbon\Carbon::parse($date)->format('l');


        if ($role_id == 1) {


            // -------------------------
            // MACHINE ATTENDANCE
            // -------------------------
            $machine_attendance_sub = DB::table('attendences')
                ->select(
                    'EID',
                    DB::raw('MIN(Time_In) as in_time'),
                    DB::raw('MAX(Time_Out) as out_time')
                )
                ->whereDate('Date', $date)
                ->groupBy('EID');


            $app_attendance_sub = DB::table('app_attendances')
                ->select(
                    'EID',
                    DB::raw("TIME(MIN(CASE WHEN LOWER(status)='in' THEN created_at END)) as in_time"),
                    DB::raw("TIME(MAX(CASE WHEN LOWER(status)='out' THEN created_at END)) as out_time")
                )
                ->whereDate('created_at', $date)
                ->groupBy('EID');


            // -------------------------
            // MAIN QUERY
            // -------------------------
            $query = DB::table('employees')


                ->join('roster_schedules', function ($join) use ($date, $dayName) {
                    $join->on('employees.id', '=', 'roster_schedules.employee_id')
                        ->whereDate('roster_schedules.effective_from', '<=', $date)
                        ->where(function ($q) use ($date) {
                            $q->whereNull('roster_schedules.effective_to')
                                ->orWhereDate('roster_schedules.effective_to', '>=', $date);
                        })
                        ->where('roster_schedules.day_name', $dayName);
                })


                ->join('officials', 'officials.EID', '=', 'employees.id')
                ->join('departments', 'departments.id', '=', 'officials.Department_Id')


                ->leftJoin('holidays', function ($join) use ($date) {
                    $join->whereDate('holidays.from_date', '<=', $date)
                        ->whereDate('holidays.to_date', '>=', $date);
                })


                ->leftJoin('leaves', function ($join) use ($date) {
                    $join->on('leaves.employee_id', '=', 'employees.id')
                        ->where('leaves.status', 'Approved')
                        ->whereDate('leaves.from_date', '<=', $date)
                        ->whereDate('leaves.to_date', '>=', $date);
                })


                ->leftJoinSub($machine_attendance_sub, 'machine_att', function ($join) {
                    $join->on('machine_att.EID', '=', 'employees.id');
                })


                ->leftJoinSub($app_attendance_sub, 'app_att', function ($join) {
                    $join->on('app_att.EID', '=', 'employees.id');
                })


                ->select(
                    DB::raw("'$date' as report_date"),


                    'employees.Full_Name as employee_name',
                    'employees.id as employee_id',
                    'employees.Employee_Id as official_id',
                    'departments.Name as department_name',


                    'roster_schedules.day_name',
                    'roster_schedules.time_in',
                    'roster_schedules.time_out',
                    'roster_schedules.weekend',


                    'machine_att.in_time as machine_in',
                    'machine_att.out_time as machine_out',
                    'app_att.in_time as app_in',
                    'app_att.out_time as app_out',


                    'holidays.holiday_name',
                    'leaves.id as leave_id',


                    DB::raw("COALESCE(app_att.in_time, machine_att.in_time) as actual_in"),
                    DB::raw("COALESCE(app_att.out_time, machine_att.out_time) as actual_out"),


                    DB::raw("
                    CASE
                        WHEN roster_schedules.weekend = 1 THEN NULL
                        ELSE TIMESTAMPDIFF(
                            MINUTE,
                            CONCAT('$date',' ', roster_schedules.time_in),
                            CONCAT('$date',' ', COALESCE(app_att.in_time, machine_att.in_time))
                        )
                    END as in_diff_minutes
                "),


                    DB::raw("
                    CASE
                        WHEN roster_schedules.weekend = 1 THEN NULL
                        ELSE TIMESTAMPDIFF(
                            MINUTE,
                            CONCAT('$date',' ', roster_schedules.time_out),
                            CONCAT('$date',' ', COALESCE(app_att.out_time, machine_att.out_time))
                        )
                    END as out_diff_minutes
                "),


                    DB::raw("
                    CASE
                        WHEN roster_schedules.weekend = 1 THEN NULL
                        ELSE TIMESTAMPDIFF(
                            MINUTE,
                            CONCAT('$date',' ', COALESCE(app_att.in_time, machine_att.in_time)),
                            CONCAT('$date',' ', COALESCE(app_att.out_time, machine_att.out_time))
                        )
                    END as working_minutes
                "),


                    DB::raw("
                    CASE
                        WHEN holidays.id IS NOT NULL THEN holidays.holiday_name
                        WHEN leaves.id IS NOT NULL THEN 'On Leave'
                        WHEN roster_schedules.weekend = 1 THEN 'Weekend'
                        WHEN COALESCE(app_att.in_time, machine_att.in_time) IS NULL THEN 'Absent'
                        ELSE 'Present'
                    END as attendance_status
                ")
                )


                // ✅ FILTERS (IMPORTANT FIX)
                ->when($request->department_id, function ($q) use ($request) {
                    $q->where('officials.Department_Id', $request->department_id);
                })


                ->when($request->employee_id, function ($q) use ($request) {
                    $q->where('employees.id', $request->employee_id);
                });


            // ✅ EXECUTE QUERY
            $result = $query->get();
        }



        // -------------------------
        // FORMAT
        // -------------------------
        $result = $result->map(function ($row) {


            $formatInMinutes = function ($minutes) {
                if ($minutes === null) return null;
                $sign = $minutes < 0 ? '+' : '-';
                $minutes = abs($minutes);
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                return $hours == 0 ? "$sign $mins min" : "$sign $hours h $mins min";
            };


            $formatOutMinutes = function ($minutes) {
                if ($minutes === null) return null;
                $sign = $minutes < 0 ? '-' : '+';
                $minutes = abs($minutes);
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                return $hours == 0 ? "$sign $mins min" : "$sign $hours h $mins min";
            };


            $formatWorkingHours = function ($minutes) {
                if ($minutes === null || $minutes < 0) return null;
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                if ($hours == 0) return "$mins min";
                if ($mins == 0) return "$hours h";
                return "$hours h $mins min";
            };


            $row->in_diff = $formatInMinutes($row->in_diff_minutes);
            $row->out_diff = $formatOutMinutes($row->out_diff_minutes);
            $row->working_hours = $formatWorkingHours($row->working_minutes);



            $row->working_hour_decimal = $row->working_minutes !== null
                ? round($row->working_minutes / 60, 2)
                : null;


            return $row;
        });


        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function reportDownload(Request $request)
    {
        if (!$request->from_date || !$request->to_date) {
            return response()->json([
                'success' => false,
                'message' => 'Date range required'
            ], 400);
        }

        $from = $request->from_date;
        $to = $request->to_date;

        $allData = collect();

        $period = CarbonPeriod::create($from, $to);

        foreach ($period as $dateObj) {

            $date = $dateObj->format('Y-m-d');

            $request->merge(['searchDate' => $date]);

            $response = $this->reportList($request);

            $data = collect($response->getData()->data ?? []);

            $allData = $allData->merge($data);
        }

        if ($allData->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No data found'
            ]);
        }


        $fileName = 'attendance_report_' . $from . '_to_' . $to . '.xlsx';


        return Excel::download(
            new AttendanceReport($allData),
            $fileName
        );
    }

    public function calendarDateReport(Request $request)
    {
        $employeeId = $request->employee_id ?? Auth::user()->EID;

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee ID not found'
            ]);
        }

        $month = $request->month;
        $year  = $request->year;

        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // =========================
        // MACHINE ATTENDANCE
        // =========================
        $machine_attendance_sub = DB::table('attendences')
            ->select(
                'EID',
                DB::raw('Date as report_date'),
                DB::raw('MIN(Time_In) as in_time'),
                DB::raw('MAX(Time_Out) as out_time')
            )
            ->where('EID', $employeeId)
            ->whereBetween('Date', [$startDate, $endDate])
            ->groupBy('EID', 'Date');

        // =========================
        // APP ATTENDANCE
        // =========================
        $app_attendance_sub = DB::table('app_attendances')
            ->select(
                'EID',
                DB::raw('DATE(created_at) as report_date'),
                DB::raw("TIME(MIN(CASE WHEN LOWER(status)='in' THEN created_at END)) as in_time"),
                DB::raw("TIME(MAX(CASE WHEN LOWER(status)='out' THEN created_at END)) as out_time")
            )
            ->where('EID', $employeeId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('EID', DB::raw('DATE(created_at)'));

        // =========================
        // MAIN QUERY
        // =========================
        $result = DB::table('employees')
            ->where('employees.id', $employeeId)

            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')

            // MACHINE ATTENDANCE
            ->leftJoinSub($machine_attendance_sub, 'machine_att', function ($join) {
                $join->on('machine_att.EID', '=', 'employees.id');
            })

            // APP ATTENDANCE (match by date)
            ->leftJoinSub($app_attendance_sub, 'app_att', function ($join) {
                $join->on('app_att.EID', '=', 'employees.id')
                    ->on('app_att.report_date', '=', 'machine_att.report_date');
            })

            // =========================
            // ROSTER SCHEDULE JOIN
            // =========================
            ->leftJoin('roster_schedules', function ($join) {
                $join->on('roster_schedules.employee_id', '=', 'employees.id')
                    ->whereRaw("
                    COALESCE(app_att.report_date, machine_att.report_date)
                    BETWEEN roster_schedules.effective_from AND roster_schedules.effective_to
                ")
                    ->whereRaw("
                    roster_schedules.day_name = DATE_FORMAT(
                        COALESCE(app_att.report_date, machine_att.report_date),
                        '%W'
                    )
                ");
            })

            // =========================
            // HOLIDAYS
            // =========================
            ->leftJoin('holidays', function ($join) {
                $join->whereRaw("
                COALESCE(app_att.report_date, machine_att.report_date)
                BETWEEN holidays.from_date AND holidays.to_date
            ");
            })

            // =========================
            // LEAVES
            // =========================
            ->leftJoin('leaves', function ($join) {
                $join->on('leaves.employee_id', '=', 'employees.id')
                    ->where('leaves.status', 'Approved')
                    ->whereRaw("
                    COALESCE(app_att.report_date, machine_att.report_date)
                    BETWEEN leaves.from_date AND leaves.to_date
                ");
            })

            // =========================
            // SELECT DATA
            // =========================
            ->select(
                'employees.id as employee_id',
                'employees.Full_Name as employee_name',

                DB::raw('COALESCE(app_att.report_date, machine_att.report_date) as report_date'),

                DB::raw("DATE_FORMAT(COALESCE(app_att.report_date, machine_att.report_date), '%W') as day_name"),

                DB::raw('COALESCE(app_att.in_time, machine_att.in_time) as actual_in'),
                DB::raw('COALESCE(app_att.out_time, machine_att.out_time) as actual_out'),

                // ROSTER DATA
                'roster_schedules.time_in as roster_in',
                'roster_schedules.time_out as roster_out',
                'roster_schedules.weekend as is_weekend',

                // FINAL STATUS LOGIC
                DB::raw("
                CASE
                    WHEN holidays.id IS NOT NULL THEN holidays.holiday_name
                    WHEN leaves.id IS NOT NULL THEN 'On Leave'

                    WHEN roster_schedules.weekend = 1
                         OR (roster_schedules.time_in IS NULL AND roster_schedules.time_out IS NULL)
                    THEN 'Weekend'

                    WHEN COALESCE(app_att.in_time, machine_att.in_time) IS NULL THEN 'Absent'

                    ELSE 'Present'
                END as attendance_status
            ")
            )

            ->distinct()
            ->orderBy('report_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}
