<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\app_attendance;
use App\Models\leave;
use App\Models\holiday;
use App\Models\employee;
use App\Models\attendence;
use App\Models\AttendanceSummary;
use Illuminate\Http\Request;
use App\Models\zkt_attendence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

class AttendenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $zk;

    //Manual Attendence Save
    public function index()
    {
        $attendence = attendence::get();
        return response()->json($attendence);
    }

    public function getAttendance(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');
        $machine_attendance = attendence::whereMonth('date', $month + 1)->whereYear('date', $year)->get();
        $app_attendance = app_attendance::selectRaw(
            'app_attendances.id,
         app_attendances.EID,
         DATE(app_attendances.updated_at) as Date,
         MIN(CASE WHEN app_attendances.status = "in" THEN TIME(app_attendances.updated_at) END) as Time_In,
         MAX(CASE WHEN app_attendances.status = "out" THEN TIME(app_attendances.updated_at) END) as Time_Out'
        )->groupBy(
            'app_attendances.EID',
            'app_attendances.id',
            DB::raw('DATE(app_attendances.updated_at)'),
        )
            ->whereMonth(DB::raw('updated_at'), $month + 1)
            ->whereYear(DB::raw('updated_at'), $year)->get();

        $attendance = $app_attendance->merge($machine_attendance);

        return response()->json($attendance);
    }

    public function fetchZktAttendence()
    {
        Artisan::call('attendance:fetch');
        // FetchZKTecoAttendanceJob::dispatch();
        return response()->json([
            'message' => 'Attendance fetched successfully.',
            'success' => true,
        ]);
    }

    public function fetchZktUser()
    {
        Artisan::call('zktuser:fetch');
        return response()->json(['message' => 'User fetched successfully.']);
    }

    public function prepareSummary()
    {
        Artisan::call('app:monthly-attendance-generate');
        return response()->json([
            'message' => 'Attendance fetched successfully.',
            'success' => true,
        ]);
    }

    public function dailyAttendanceSync(Request $request)
    {
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $currentDate = $startDate;
        $monthDates = [];
        while ($currentDate <= $endDate) {
            $monthDates[] = $currentDate;
            $currentDate = Carbon::parse($currentDate)->addDay()->toDateString();
        }

        $attendanceData = [];
        foreach ($monthDates as $dateInfo) {
            $appAttendanceInfo = app_attendance::whereDate('created_at', $dateInfo)
                ->whereIn('status', ['in', 'out'])
                ->selectRaw('EID as employee_id, DATE(created_at) as date, TIME(MIN(CASE WHEN status="in" THEN created_at END)) as in_time, TIME(MAX(CASE WHEN status="out" THEN created_at END)) as out_time')
                ->groupByRaw('EID, DATE(created_at)')
                ->get();
            $machineAttendanceInfo = attendence::whereDate('Date', $dateInfo)
                ->whereIn('Status', ['P', 'L'])
                ->selectRaw('EID as employee_id, DATE as date, MIN(Time_In) as in_time, MAX(Time_Out) as out_time')
                ->groupByRaw('EID, DATE')
                ->get();
            $mergedAttendance = $appAttendanceInfo->concat($machineAttendanceInfo);
            foreach ($mergedAttendance as $record) {
                $attendanceData[$dateInfo][] = [
                    'employee_id' => $record->employee_id,
                    'in_time' => $record->in_time,
                    'out_time' => $record->out_time,
                ];
            }
        }

        $leaves = leave::whereDate('from_date', '<=', $endDate)
            ->whereDate('to_date', '>=', $startDate)
            ->where('status', 'Approved')
            ->select('from_date', 'to_date', 'employee_id')
            ->get();


        $leaveData = [];
        foreach ($leaves as $leave) {
            $start = \Carbon\Carbon::parse($leave->from_date)->max($startDate);
            $end   = \Carbon\Carbon::parse($leave->to_date)->min($endDate);
            while ($start->lte($end)) {
                $date = $start->format('Y-m-d');
                $leaveData[$date][] = [
                    'date' => $date,
                    'employee_id' => $leave->employee_id,
                ];
                $start->addDay();
            }
        }

        $fetchAttendanceSummary = AttendanceSummary::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return $item->employee_id . '_' . $item->date;
            });

        foreach ($leaveData as $data) {
            foreach ($data as $record) {
                $key = $record['employee_id'] . '_' . $record['date'];
                $existing = $fetchAttendanceSummary[$key] ?? null;
                if ($existing && is_null($existing->in_time)) {
                    $existing->update([
                        'status'   => 'On Leave'
                    ]);
                }
            }
        }

        foreach ($attendanceData as $date => $records) {
            foreach ($records as $record) {
                $key = $record['employee_id'] . '_' . $date;
                $existing = $fetchAttendanceSummary[$key] ?? null;
                if ($existing && is_null($existing->in_time)) {
                    $status = 'Present';
                    $inTime = Carbon::parse($record['in_time']);
                    $outTime = Carbon::parse($record['out_time']);

                    $rosterIn = $existing->rostered_in_time
                        ? Carbon::parse($existing->rostered_in_time)
                        : null;
                    $rosterOut = $existing->rostered_out_time
                        ? Carbon::parse($existing->rostered_out_time)
                        : null;

                    if ($rosterIn && $inTime->diffInMinutes($rosterIn, false) < -15) {
                        $status = 'Late';
                    }
                    if ($rosterOut && $outTime->diffInMinutes($rosterOut, false) > 15) {
                        $status = 'Early Leave';
                    }

                    $existing->update([
                        'in_time'  => $record['in_time'],
                        'out_time' => $record['out_time'],
                        'status'   => $status
                    ]);
                }
            }
        }
    }

    public function manualAttendance(Request $request)
    {
        $employee_id = $request->employee_id;
        $in_time = $request->in_time;
        $out_time = $request->out_time;

        $date = Carbon::parse($request->attendance_date)->toDateString();

        $remarks = !empty($request->remarks) ? $request->remarks : null;
        $status = "Home";

        $data = [
            'employee_id' => $employee_id,
            'in_time' => $in_time,
            'out_time' => $out_time,
            'date' => $date,
            'remarks' => $remarks,
            'status' => $status,
        ];

        $response = AttendanceSummary::where('employee_id', $employee_id)
            ->whereDate('date', $date)
            ->update($data);

        return response()->json($response);
    }


    public function getTsoLocation()
    {
        $date = Carbon::now()->toDateString();

        $location = attendence::select('employees.Employee_Id', 'employees.Full_Name', 'attendences.latitude', 'attendences.longitude')
            ->join('employees', 'attendences.EID', '=', 'employees.id')
            ->where('Date', $date)
            ->get();
        return response()->json($location);
    }

    public function store(Request $request)
    {
        $userId = Auth::user()->EID;
        $attendanceData = $request->input('attendanceData');
        $year = now()->year;
        $month = $request->input('month');

        foreach ($attendanceData as $employeeData) {
            $employeeId = $employeeData['id'];
            $attendance = $employeeData['attendance'];

            foreach ($attendance as $day => $status) {
                if (!empty($status)) {
                    attendence::updateOrCreate(
                        [
                            'EID' => $employeeId,
                            'Date' => Carbon::create($year, $month + 1, $day + 1)->toDateString(),
                        ],
                        [
                            'Status' => $status,
                            'created_by' => $userId,
                        ]
                    );
                }
            }
        }

        return response()->json(['success' => true]);
    }


    public function getSubordinates($userId)
    {
        $subordinates = [];
        $queue = [$userId];

        while (!empty($queue)) {
            $currentUserId = array_shift($queue);
            $subordinateEmployees = employee::select('employees.id', 'employees.Full_Name', 'employees.Employee_Id', 'designations.Name as designation', 'departments.Name as department', 'branches.Name as branch')
                ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
                ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
                ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
                ->leftjoin('branches', 'officials.Job_Location_Id', '=', 'branches.id')
                ->where('officials.Supervisor_Id', '=', $currentUserId)
                ->where('officials.Status', '=', 'N')
                ->orderby('departments.Name', 'asc')
                ->get();

            foreach ($subordinateEmployees as $subordinate) {
                $subordinates[] = $subordinate;
                $queue[] = $subordinate->id;
            }
        }

        return $subordinates;
    }


    public function attendenceEmployee()
    {
        $user = Auth::user();
        if ($user->role_id === 1 || $user->role_id === 2) {
            $employee = employee::select('employees.id', 'employees.Full_Name', 'employees.Employee_Id', 'designations.Name as designation', 'departments.Name as department', 'branches.Name as branch')
                ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
                ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
                ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
                ->leftjoin('branches', 'officials.Job_Location_Id', '=', 'branches.id')
                ->where('officials.Status', '=', 'N')
                ->orderby('departments.Name', 'asc')
                ->get();
            return response()->json($employee);
        }

        if ($user->EID) {
            $employee = $this->getSubordinates($user->EID);

            return response()->json($employee);
        }

        return response()->json(['message' => 'User not found'], 404);
    }


    public function edit(Request $request)
    {
        $EID = $request->input('Employee_Id');
        $startDate = $request->input('From_Date');
        $endDate = $request->input('To_Date');

        $attendances = attendence::select('employees.full_name', 'attendences.Date', 'attendences.Time_In', 'attendences.Time_Out', 'attendences.Status')
            ->join('employees', 'employees.id', '=', 'attendences.EID')
            ->whereBetween('attendences.Date', [$startDate, $endDate])
            ->where('attendences.EID', $EID)
            ->get();

        return response()->json($attendances);
    }


    // this is fast and accurate but donot take new records
    // public function fetchAttendence()
    // {
    //     // Fetch all holidays, leaves, and attendance records as before
    //     $holidays = holiday::all();
    //     $leaves = leave::select('leaves.EID', 'leaves.From_Date', 'leaves.To_Date', 'leave_types.short_name')
    //         ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
    //         ->where('leaves.Status', '=', 'Approved')
    //         ->get();

    //     $attendances = zkt_attendence::all()->groupBy(['User_id', 'date']);

    //     // Get the last processed date from the attendences table
    //     $lastProcessedDate = attendence::max('Date');

    //     // Set the start date to the last processed date or a default date
    //     $startDate = $lastProcessedDate
    //         ? Carbon::parse($lastProcessedDate)->startOfMonth()->addMonth()->format('Y-m-d')
    //         : '2023-11-01';  // Default start date if no previous record exists

    //     // Set the end date as the current date
    //     // $endDate = Carbon::now()->format('Y-m-d');
    //     $endDate = zkt_attendence::max('Date');

    //     // Initialize the data array
    //     $insertData = [];

    //     foreach ($attendances as $userId => $userAttendances) {
    //         $currentDate = $startDate;

    //         // Iterate through each date from start to end date
    //         while ($currentDate <= $endDate) {
    //             // Check for attendance on the current date
    //             if (isset($userAttendances[$currentDate])) {
    //                 $attendanceRecords = $userAttendances[$currentDate];

    //                 $timeIn = $attendanceRecords->sortBy('time')->first()->time;
    //                 $timeOut = $attendanceRecords->sortByDesc('time')->first()->time;
    //                 $status = ($timeIn > '11:00:00') ? 'L' : 'P'; // Determine status (Late or Present)

    //             } else {
    //                 $status = $this->determineStatus($userId, $currentDate, $holidays, $leaves);
    //                 $timeIn = $timeOut = null;
    //             }

    //             // Add record to the insert array
    //             $insertData[] = [
    //                 'EID' => $userId,
    //                 'Date' => $currentDate,
    //                 'Time_In' => $timeIn,
    //                 'Time_Out' => $timeOut,
    //                 'Status' => $status,
    //                 'created_at' => Carbon::now(),
    //                 'updated_at' => Carbon::now(),
    //             ];

    //             // If the batch size exceeds 1000, insert the data and reset the array
    //             if (count($insertData) >= 1000) {
    //                 DB::table('attendences')->insert($insertData);
    //                 $insertData = []; // Reset insertData array after each batch insert
    //             }

    //             $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    //         }
    //     }

    //     // Insert any remaining data after loop
    //     if (!empty($insertData)) {
    //         DB::table('attendences')->insert($insertData);
    //     }

    //     return response()->json(['message' => 'Attendance records processed successfully']);
    // }


    public function fetchAttendence()
    {
        // Fetch all holidays, leaves, and attendance records as before
        $holidays = holiday::all();
        $leaves = leave::select('leaves.employee_id', 'leaves.from_date', 'leaves.to_date', 'leave_types.short_leave_name')
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->where('leaves.status', '=', 'Approved')
            ->get();

        // Get the last processed date from the attendances table
        $lastProcessedDate = attendence::max('Date');

        // Set the start date to the last processed date or a default date
        $startDate = $lastProcessedDate
            ? Carbon::parse($lastProcessedDate)->format('Y-m-d')
            : '2023-10-30';  // Default start date if no previous record exists

        // Set the end date as the current date
        $endDate = zkt_attendence::max('Date');

        // Fetch only new attendance records from the zkt_attendence table
        $attendances = zkt_attendence::where('date', '>=', $startDate)
            ->get()
            ->groupBy(['User_id', 'date']);

        // Initialize the data array
        $insertData = [];

        foreach ($attendances as $userId => $userAttendances) {
            $currentDate = $startDate;

            // Iterate through each date from start to end date
            while ($currentDate <= $endDate) {
                // Check for attendance on the current date
                if (isset($userAttendances[$currentDate])) {
                    $attendanceRecords = $userAttendances[$currentDate];

                    $timeIn = $attendanceRecords->sortBy('time')->first()->time;
                    // $timeOut = $attendanceRecords->sortByDesc('time')->first()->time;
                    $timeOut = $attendanceRecords->max('time');
                    $status = ($timeIn > '11:00:00') ? 'L' : 'P'; // Determine status (Late or Present)

                } else {
                    $status = $this->determineStatus($userId, $currentDate, $holidays, $leaves);
                    $timeIn = $timeOut = null;
                }

                // Add record to the insert array
                $insertData[] = [
                    'EID' => $userId,
                    'Date' => $currentDate,
                    'Time_In' => $timeIn,
                    'Time_Out' => $timeOut,
                    'Status' => $status,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                // If the batch size exceeds 1000, insert the data and reset the array
                if (count($insertData) >= 1000) {
                    // DB::table('attendences')->insert($insertData);
                    DB::table('attendences')->upsert(
                        $insertData,
                        ['EID', 'Date'],
                        ['Time_In', 'Time_Out', 'Status', 'updated_at']
                    );
                    $insertData = []; // Reset insertData array after each batch insert
                }

                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }
        }

        // Insert any remaining data after loop
        if (!empty($insertData)) {
            // DB::table('attendences')->insert($insertData);
            DB::table('attendences')->upsert(
                $insertData,
                ['EID', 'Date'],
                ['Time_In', 'Time_Out', 'Status', 'updated_at']
            );
        }

        return response()->json(['message' => 'Attendance records processed successfully']);
    }



    /**
     * Determine the status of an employee based on leave, holiday, and weekends.
     */
    private function determineStatus($userId, $date, $holidays, $leaves)
    {
        // Check if the date falls within any leave period
        $isOnLeave = $leaves->first(function ($leave) use ($userId, $date) {
            return $leave->employee_id == $userId && $date >= $leave->from_date && $date <= $leave->to_date;
        });

        if ($isOnLeave) {
            return $isOnLeave->short_name; // Set status to leave type short name
        }

        // Check if the date falls within any holiday period
        $isHoliday = $holidays->contains(function ($holiday) use ($date) {
            return $date >= $holiday->from_date && $date <= $holiday->fo_date;
        });

        if ($isHoliday) {
            return 'H'; // Holiday
        }

        // Check if the date is a Friday (adjust according to your weekend setting)
        if (Carbon::parse($date)->isFriday()) {
            return 'F'; // Friday/Weekend
        }
        return 'A';
    }


    public function attendanceKpi(Request $request)
    {
        $month = (int) $request->query('month');
        $year  = (int) $request->query('year');

        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        if ($year < 1) {
            $year = (int) now()->year;
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $bucketExpr = "
            CASE
                WHEN attendance_summaries.status IN ('Holiday', 'Friday')
                     OR DAYOFWEEK(attendance_summaries.date) = 6 THEN 'H'
                WHEN attendance_summaries.status IN ('Present', 'Manual', 'Home') THEN 'P'
                WHEN attendance_summaries.status = 'Late' THEN 'L'
                WHEN attendance_summaries.status = 'Early Leave' THEN 'E'
                WHEN attendance_summaries.status = 'On Leave' THEN 'O'
                ELSE 'A'
            END
        ";

        $query = DB::table('attendance_summaries')
            ->join('employees', 'employees.id', '=', 'attendance_summaries.employee_id')
            ->join('departments', 'departments.id', '=', 'attendance_summaries.department_id')
            ->whereBetween('attendance_summaries.date', [$startDate, $endDate]);

        if ($request->filled('department_id')) {
            $query->where('attendance_summaries.department_id', $request->query('department_id'));
        }

        if ($request->filled('employee_id')) {
            $query->where('attendance_summaries.employee_id', $request->query('employee_id'));
        }

        $rows = $query
            ->select(
                'attendance_summaries.employee_id',
                'employees.Full_Name as employee_name',
                'employees.Employee_Id as employee_code',
                'departments.Name as department_name',
                DB::raw("SUM(CASE WHEN $bucketExpr = 'P' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN $bucketExpr = 'L' THEN 1 ELSE 0 END) as late"),
                DB::raw("SUM(CASE WHEN $bucketExpr = 'E' THEN 1 ELSE 0 END) as early_leave"),
                DB::raw("SUM(CASE WHEN $bucketExpr = 'O' THEN 1 ELSE 0 END) as on_leave"),
                DB::raw("SUM(CASE WHEN $bucketExpr = 'A' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN $bucketExpr = 'H' THEN 1 ELSE 0 END) as holidays"),
                DB::raw("SUM(CASE WHEN $bucketExpr = 'H' THEN 0 ELSE 1 END) as total_work_days"),
                DB::raw("SUM(CASE WHEN $bucketExpr = 'P' THEN 1 WHEN $bucketExpr = 'L' THEN 0.5 WHEN $bucketExpr = 'E' THEN 0.5 WHEN $bucketExpr = 'O' THEN 1 ELSE 0 END) as total_marks")
            )
            ->groupBy(
                'attendance_summaries.employee_id',
                'employees.Full_Name',
                'employees.Employee_Id',
                'departments.Name'
            )
            ->orderBy('departments.Name')
            ->orderBy('employees.Full_Name')
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'employee_id' => (int) $row->employee_id,
                'employee_name' => $row->employee_name,
                'employee_code' => $row->employee_code,
                'department_name' => $row->department_name,
                'present' => (int) $row->present,
                'late' => (int) $row->late,
                'early_leave' => (int) $row->early_leave,
                'on_leave' => (int) $row->on_leave,
                'absent' => (int) $row->absent,
                'holidays' => (int) $row->holidays,
                'total_work_days' => (int) $row->total_work_days,
                'total_marks' => (float) $row->total_marks,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'period' => [
                'month' => $month,
                'year' => $year,
            ],
            'data' => $data,
        ]);
    }

    // public function fetchAttendence()
    // {
    //     /* -----------------------------------------------------------
    //  | 1.  Reference data                                          |
    //  ----------------------------------------------------------- */
    //     $holidays = holiday::all();

    //     $leaves   = leave::select(
    //         'leaves.EID',
    //         'leaves.From_Date',
    //         'leaves.To_Date',
    //         'leave_types.short_name'
    //     )
    //         ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
    //         ->where('leaves.Status', 'Approved')
    //         ->get();

    //     /* -----------------------------------------------------------
    //  | 2.  What date range do we need to process?                  |
    //  ----------------------------------------------------------- */
    //     $lastProcessedDate = attendence::max('Date');          // most-recent day we have already stored

    //     $startDate = $lastProcessedDate
    //         ? Carbon::parse($lastProcessedDate)->addDay()->toDateString()   // **start the NEXT day**
    //         : '2023-10-30';                                                // first-ever run fallback

    //     $endDate   = zkt_attendence::max('Date');              // most-recent day in the raw table

    //     if (!$endDate || $startDate > $endDate) {
    //         return response()->json(['message' => 'Nothing new to import.']);
    //     }

    //     /* -----------------------------------------------------------
    //  | 3.  Pull raw swipes just for the window we still need       |
    //  ----------------------------------------------------------- */
    //     $attendances = zkt_attendence::whereBetween('date', [$startDate, $endDate])
    //         ->get()
    //         ->groupBy(['User_id', 'date']);

    //     /* -----------------------------------------------------------
    //  | 4.  Build rows, batch-upsert 1 000 at a time                |
    //  ----------------------------------------------------------- */
    //     $batch = [];

    //     foreach ($attendances as $userId => $days) {

    //         // walk forward day-by-day so we also create the Absent/Holiday/Leave rows
    //         for ($date = $startDate; $date <= $endDate; $date = Carbon::parse($date)->addDay()->toDateString()) {

    //             if (isset($days[$date])) {
    //                 $records  = $days[$date];
    //                 $timeIn   = $records->sortBy('time')->first()->time;
    //                 $timeOut  = $records->max('time');
    //                 $status   = $timeIn > '11:00:00' ? 'L' : 'P';
    //             } else {
    //                 $status   = $this->determineStatus($userId, $date, $holidays, $leaves);
    //                 $timeIn   = $timeOut = null;
    //             }

    //             $batch[] = [
    //                 'EID'        => $userId,
    //                 'Date'       => $date,
    //                 'Time_In'    => $timeIn,
    //                 'Time_Out'   => $timeOut,
    //                 'Status'     => $status,
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ];

    //             // write every 1 000 rows
    //             if (count($batch) === 1000) {
    //                 $this->upsertAttendance($batch);
    //                 $batch = [];
    //             }
    //         }
    //     }

    //     // write any tail rows
    //     if ($batch) {
    //         $this->upsertAttendance($batch);
    //     }

    //     return response()->json(['message' => 'Attendance records processed without duplicates.']);
    // }

    // /**
    //  * Upsert helper – one place to change if you tweak columns later.
    //  */
    // private function upsertAttendance(array $rows): void
    // {
    //     DB::table('attendences')->upsert(
    //         $rows,                          // what to write
    //         ['EID', 'Date'],                // natural key
    //         ['Time_In', 'Time_Out', 'Status', 'updated_at']
    //     );
    // }

    // /**
    //  * Determine status (unchanged from your version).
    //  */
    // private function determineStatus($userId, $date, $holidays, $leaves)
    // {
    //     $onLeave = $leaves->first(
    //         fn($l) =>
    //         $l->EID == $userId && $date >= $l->From_Date && $date <= $l->To_Date
    //     );
    //     if ($onLeave) {
    //         return $onLeave->short_name;
    //     }

    //     $isHoliday = $holidays->contains(
    //         fn($h) =>
    //         $date >= $h->From_Date && $date <= $h->To_Date
    //     );
    //     if ($isHoliday) {
    //         return 'H';
    //     }

    //     if (Carbon::parse($date)->isFriday()) {
    //         return 'F';
    //     }

    //     return 'A';
    // }
}
