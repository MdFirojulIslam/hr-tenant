<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use App\Mail\FFAttendanceEmail;
use App\Mail\AttendanceEmail;
use App\Mail\HrApproval;
use App\Mail\LeaveEmail;
use App\Mail\LeaveStatusEmail;
use App\Mail\MonthlyAttendence;
use App\Mail\UserCreated;
use App\Models\app_attendance;
use App\Models\attendence;
use App\Models\Email_log;
use App\Models\employee;
use App\Models\leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


class EmailController extends Controller
{
    // added by firoj



    public function hrApprovalEmail($id)
    {
        $leave = leave::select('employees.Full_Name', 'users.email', 'leaves.from_date', 'leaves.to_date', 'leaves.status', 'leave_types.leave_name')
            ->join('employees', 'employees.id', '=', 'leaves.employee_id')
            ->join('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
            ->join('users', 'employees.id', '=', 'users.EID')
            ->where('leaves.id', $id)
            ->first();

        $toEmail = $leave->email;
        $name = $leave->Full_Name;
        $from_date = $leave->from_date;
        $to_date = $leave->to_date;
        $status = $leave->status;
        $leave_type = $leave->leave_name;
        if ($leave->status == 'Approved (SV)') {
            $status = 'Approved';
        } else {
            $status = 'Rejected';
        }

        Mail::to([$toEmail, 'tahmina@artisanbn.com'])
            ->send(new HrApproval($from_date, $to_date, $status, $leave_type, $name));

        return response()->json(['message' => 'Email sent successfully']);
    }


    public function leaveRequestEmail($id)
    {
        $leave = leave::select('employees.Full_Name', 'employees.Employee_Id', 'leaves.from_date', 'leaves.to_date', 'leaves.purpose', 'leave_types.leave_name', 'officials.Supervisor_Id', 'designations.Name as designation')
            ->join('employees', 'employees.id', '=', 'leaves.employee_id')
            ->join('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->where('leaves.id', $id)
            ->first();

        $supervisor = employee::where('id', $leave->Supervisor_Id)->first();
        $toEmail = $supervisor->Official_Email;
        $name = $leave->Full_Name;
        $eid = $leave->Employee_Id;
        $designation = $leave->designation;
        $from_date = $leave->from_date;
        $to_date = $leave->to_date;
        $purpose = $leave->purpose;
        $leave_type = $leave->leave_name;
        Mail::to($toEmail)->send(new LeaveEmail($from_date, $to_date, $purpose, $leave_type, $name, $eid, $designation));
        return response()->json(['message' => 'Email sent successfully']);
    }

    public function leaveStatusEmail($id)
    {
        $leave = leave::select('employees.Full_Name', 'users.email', 'leaves.from_date', 'leaves.to_date', 'leaves.status', 'leave_types.leave_name')
            ->join('employees', 'employees.id', '=', 'leaves.employee_id')
            ->join('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
            ->join('users', 'employees.id', '=', 'users.EID')
            ->where('leaves.id', $id)
            ->first();

        $toEmail = $leave->email;
        $name = $leave->Full_Name;
        $from_date = $leave->from_date;
        $to_date = $leave->to_date;
        $status = $leave->status;
        $leave_type = $leave->leave_name;
        Mail::to($toEmail)->send(new LeaveStatusEmail($from_date, $to_date, $status, $leave_type, $name));
        return response()->json(['message' => 'Email sent successfully']);
    }






    public function sendUserEmail(Request $request)
    {
        $user = employee::where('id', $request->employee_Id)->first();

        $toEmail = $request->email;
        $name = $user->Full_Name;
        $username = $request->username;
        $password = $request->password;

        Mail::to($toEmail)->send(new UserCreated($name, $username, $password));


        $emailBody = 'Welcome to Artisan HRIS.
        Your Name is: ' . $name . '
        Your username is: ' . $username . '
        Your password is: ' . $password;

        Email_log::create([
            'Particular' => 'User Created',
            'Recipient_Email' => $toEmail,
            'Subject' => 'Welcome to ARTISAN HRIS',
            'Body' => $emailBody,
        ]);

        return response()->json(['message' => 'Email sent successfully']);
    }


    // public function attendanceEmail(Request $request)
    // {
    //     $date = $request->date;

    //     $attendances = attendence::select(
    //         'employees.Employee_Id',
    //         'employees.Full_Name',
    //         'attendences.EID',
    //         'attendences.Time_In',
    //         'designations.Name'
    //     )
    //         ->join('employees', 'employees.id', '=', 'attendences.EID')
    //         ->join('officials', 'employees.id', '=', 'officials.EID')
    //         ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
    //         ->where('attendences.Date', '=', $date)
    //         ->where('officials.Job_Location_Id', '=', 1)
    //         ->distinct()
    //         ->orderby('attendences.Time_In', 'asc')
    //         ->get();


    //     $leaveData = leave::select(
    //         'leaves.EID',
    //         'leaves.From_Date',
    //         'leaves.To_Date',
    //         'leave_types.Name as leave_type'
    //     )
    //         ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
    //         ->where('leaves.Status', '=', 'Approved')
    //         ->where(function ($query) use ($date) {
    //             $query->where('leaves.From_Date', '<=', $date)
    //                 ->where('leaves.To_Date', '>=', $date);
    //         })
    //         ->get();


    //     $attendence = $attendances->map(function ($item) use ($leaveData) {
    //         $leaveOnDate = $leaveData->firstWhere('EID', $item->EID);

    //         if ($leaveOnDate) {
    //             $item->Time_In = $leaveOnDate->leave_type;
    //         }

    //         return $item;
    //     });

    //     Mail::to(['saad@artisanbn.com', 'tahmina@artisanbn.com'])
    //         ->cc('emu@artisanbn.com')
    //         ->bcc('sabbir@artisanbn.com')
    //         ->send(new AttendanceEmail($attendence, $date));

    //     $this->ffAttendance($date);

    //     $previousDate = date('Y-m-d', strtotime($date . ' -1 day'));

    //     $this->ffAttendancePrev($previousDate);


    //     return response()->json(['message' => 'Email sent successfully', 'success' => true]);
    // }

    public function attendanceEmail(Request $request)
    {
        $date = $request->date;

        // Fetch leave data once
        $leaveData = leave::select(
            'leaves.EID',
            'leaves.From_Date',
            'leaves.To_Date',
            'leave_types.Name as leave_type'
        )
            ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
            ->where('leaves.Status', '=', 'Approved')
            ->where(function ($query) use ($date) {
                $query->where('leaves.From_Date', '<=', $date)
                    ->where('leaves.To_Date', '>=', $date);
            })
            ->get();

        // Function to fetch and map attendance by location
        $getAttendanceData = function ($locationId) use ($date, $leaveData) {
            $attendances = employee::select(
                'employees.Employee_Id',
                'employees.Full_Name',
                'attendences.EID',
                'attendences.Time_In',
                'designations.Name'
            )
                ->leftJoin('attendences', 'employees.id', '=', 'attendences.EID')
                ->join('officials', 'employees.id', '=', 'officials.EID')
                ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
                ->where('attendences.Date', '=', $date)
                ->where('officials.Job_Location_Id', '=', $locationId)
                ->where('officials.Status', '=', 'N')
                ->distinct()
                ->orderby('attendences.Time_In', 'asc')
                ->get();

            // Replace Time_In with leave type if on leave
            return $attendances->map(function ($item) use ($leaveData) {
                $leaveOnDate = $leaveData->firstWhere('EID', $item->EID);
                if ($leaveOnDate) {
                    $item->Time_In = $leaveOnDate->leave_type;
                }
                return $item;
            });
        };


        // $attendanceLoc1 = $getAttendanceData(1);
        // $title = 'Attendance of Gulshan CO';
        // Mail::to(['saad@artisanbn.com', 'tahmina@artisanbn.com'])
        //     ->cc('emu@artisanbn.com')
        //     ->bcc('sabbir@artisanbn.com')
        //     ->send(new AttendanceEmail($attendanceLoc1, $date, $title));

        $attendanceLoc1 = $getAttendanceData(1);
        $title = 'Attendance of Gulshan CO';
        Mail::to(['tahmina@artisanbn.com'])
            ->cc('sudiptya@artisanbn.com')
            ->bcc('ferojul@artisanbn.com')
            ->send(new AttendanceEmail($attendanceLoc1, $date, $title));


        // $attendanceLoc2 = $getAttendanceData(2);
        // $title = 'Attendance of Central Warehouse';
        // Mail::to(['saad@artisanbn.com', 'tahmina@artisanbn.com','monjurul@artisanbn.com'])
        //     ->cc('emu@artisanbn.com')
        //     ->bcc('sabbir@artisanbn.com')
        //     ->send(new AttendanceEmail($attendanceLoc2, $date, $title));

        $attendanceLoc2 = $getAttendanceData(2);
        $title = 'Attendance of Central Warehouse';
        Mail::to(['tahmina@artisanbn.com'])
            ->cc('sudiptya@artisanbn.com')
            ->bcc('ferojul@artisanbn.com')
            ->send(new AttendanceEmail($attendanceLoc2, $date, $title));

        $this->ffAttendance($date);

        $previousDate = date('Y-m-d', strtotime($date . ' -1 day'));

        $this->ffAttendancePrev($previousDate);

        return response()->json(['message' => 'Emails sent successfully', 'success' => true]);
    }


    public function ffAttendance($date)
    {
        $attendenceData = employee::selectRaw(
            'employees.id as EID,
             employees.Full_Name,
             towns.Name as town_name,
             employees.Employee_Id,
             app_attendances.latitude,
             app_attendances.longitude,
             departments.Name as department,
             designations.Name as designation,
             officials.Supervisor_Id,
             DATE(app_attendances.updated_at) as date,
             DAYNAME(app_attendances.updated_at) as weekday,
             MIN(CASE WHEN app_attendances.status = "in" THEN TIME(app_attendances.updated_at) END) as time_in,
             MIN(CASE WHEN app_attendances.status = "in" THEN app_attendances.village END) as in_location,
             MIN(CASE WHEN app_attendances.status = "in" THEN app_attendances.image_path END) as in_image'
        )
            ->leftJoin('app_attendances', function ($join) use ($date) {
                $join->on('app_attendances.EID', '=', 'employees.id')
                    ->whereRaw('DATE(app_attendances.updated_at) = ?', [$date])
                    ->where('app_attendances.status', '=', 'in');
            })
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->leftJoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftJoin('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->leftJoin('towns', 'app_attendances.town_id', '=', 'towns.id')
            ->where('officials.Job_Location_Id', '=', 3)
            ->whereNotNull('officials.Job_Location_Id')
            ->where('officials.Status', '=', 'N')
            ->groupBy(
                'employees.id',
                'employees.Full_Name',
                'employees.Employee_Id',
                'departments.Name',
                'designations.Name',
                'towns.Name',
                'officials.Supervisor_Id',
                'app_attendances.latitude',
                'app_attendances.longitude',
                DB::raw('DATE(app_attendances.updated_at)'),
                DB::raw('TIME(app_attendances.updated_at)'),
                DB::raw('DAYNAME(app_attendances.updated_at)')
            )
            ->orderBy('designations.Name', 'asc')
            ->get();

        $leaveData = leave::select(
            'leaves.EID',
            'leaves.From_Date',
            'leaves.To_Date',
            'leave_types.Name as leave_type'
        )
            ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
            ->where('leaves.Status', '=', 'Approved')
            ->where(function ($query) use ($date) {
                $query->where('leaves.From_Date', '<=', $date)
                    ->where('leaves.To_Date', '>=', $date);
            })
            ->get();

        // Adjust attendance data with leave info
        $attendences = $attendenceData->map(function ($item) use ($leaveData) {
            $leaveOnDate = $leaveData->firstWhere('EID', $item->EID);

            if ($leaveOnDate) {
                $item->in_location = $leaveOnDate->leave_type . ' leave';
            }
            return $item;
        });

        $title = 'Field Force Attendance Report';

        // Mail::to('tahmina@artisanbn.com')
        //     ->cc(['emu@artisanbn.com', 'saad@artisanbn.com'])
        //     ->bcc('sabbir@artisanbn.com')
        //     ->send(new FFAttendanceEmail($attendences, $date, $title));

        // $supervisor = [
        //     14 => 'kashem@artisanbn.com',
        //     7 => 'nuralam@artisanbn.com',
        //     141 => 'maruf@artisanbn.com',
        //     18 => 'tushar@artisanbn.com',
        //     19 => 'sohag@artisanbn.com'
        // ];
        Mail::to('tahmina@artisanbn.com')
            ->cc(['sudiptya@artisanbn.com'])
            ->bcc('ferojul@artisanbn.com')
            ->send(new FFAttendanceEmail($attendences, $date, $title));

        $supervisor = [
            254 => 'ferojul@artisanbn.com'
        ];

        foreach ($supervisor as $sup_id => $email) {
            // Check if there are any employees under this supervisor
            $attendence = $attendences->filter(function ($item) use ($sup_id) {
                return $item->Supervisor_Id == $sup_id;
            });

            if ($attendence->isNotEmpty()) {
                if (in_array($sup_id, [7, 18, 19, 141])) {
                    Mail::to($email)
                        ->send(new FFAttendanceEmail($attendence, $date, $title));
                } else {
                    Mail::to($email)
                        ->cc(['Fahim.Hara@unilever.com'])
                        ->send(new FFAttendanceEmail($attendence, $date, $title));
                }
            }
        }

        return response()->json(['message' => 'Field force attendance sent successfully', 'success' => true]);
    }

    public function ffAttendancePrev($date)
    {
        $attendenceData = employee::selectRaw(
            'employees.id as EID,
             employees.Full_Name,
             towns.Name as town_name,
             employees.Employee_Id,
             app_attendances.latitude,
             app_attendances.longitude,
             departments.Name as department,
             designations.Name as designation,
             officials.Supervisor_Id,
             DATE(app_attendances.updated_at) as date,
             DAYNAME(app_attendances.updated_at) as weekday,
             MIN(CASE WHEN app_attendances.status = "out" THEN TIME(app_attendances.updated_at) END) as time_in,
             MIN(CASE WHEN app_attendances.status = "out" THEN app_attendances.village END) as in_location,
             MIN(CASE WHEN app_attendances.status = "out" THEN app_attendances.image_path END) as in_image'
        )
            ->leftJoin('app_attendances', function ($join) use ($date) {
                $join->on('app_attendances.EID', '=', 'employees.id')
                    ->whereRaw('DATE(app_attendances.updated_at) = ?', [$date])
                    ->where('app_attendances.status', '=', 'out');
            })
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->leftJoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftJoin('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->leftJoin('towns', 'app_attendances.town_id', '=', 'towns.id')
            ->where('officials.Job_Location_Id', '=', 3)
            ->whereNotNull('officials.Job_Location_Id')
            ->where('officials.Status', '=', 'N')
            ->groupBy(
                'employees.id',
                'employees.Full_Name',
                'employees.Employee_Id',
                'departments.Name',
                'designations.Name',
                'towns.Name',
                'officials.Supervisor_Id',
                'app_attendances.latitude',
                'app_attendances.longitude',
                DB::raw('DATE(app_attendances.updated_at)'),
                DB::raw('TIME(app_attendances.updated_at)'),
                DB::raw('DAYNAME(app_attendances.updated_at)')
            )
            ->orderBy('designations.Name', 'asc')
            ->get();

        $leaveData = leave::select(
            'leaves.EID',
            'leaves.From_Date',
            'leaves.To_Date',
            'leave_types.Name as leave_type'
        )
            ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
            ->where('leaves.Status', '=', 'Approved')
            ->where(function ($query) use ($date) {
                $query->where('leaves.From_Date', '<=', $date)
                    ->where('leaves.To_Date', '>=', $date);
            })
            ->get();

        // Adjust attendance data with leave info
        $attendences = $attendenceData->map(function ($item) use ($leaveData) {
            $leaveOnDate = $leaveData->firstWhere('EID', $item->EID);

            if ($leaveOnDate) {
                $item->in_location = $leaveOnDate->leave_type . ' leave';
            }
            return $item;
        });

        $title = 'Day End/Out Report';

        // Mail::to('tahmina@artisanbn.com')
        //     ->cc(['emu@artisanbn.com', 'saad@artisanbn.com'])
        //     ->bcc('sabbir@artisanbn.com')
        //     ->send(new FFAttendanceEmail($attendences, $date, $title));

        // $supervisor = [
        //     14 => 'kashem@artisanbn.com',
        //     7 => 'nuralam@artisanbn.com',
        //     141 => 'maruf@artisanbn.com',
        //     18 => 'tushar@artisanbn.com',
        //     19 => 'sohag@artisanbn.com'

        // ];

        Mail::to('tahmina@artisanbn.com')
            ->cc(['sudiptya@artisanbn.com'])
            ->bcc('ferojul@artisanbn.com')
            ->send(new FFAttendanceEmail($attendences, $date, $title));

        $supervisor = [
            254 => 'ferojul@artisanbn.com'

        ];

        foreach ($supervisor as $sup_id => $email) {
            // Check if there are any employees under this supervisor
            $attendence = $attendences->filter(function ($item) use ($sup_id) {
                return $item->Supervisor_Id == $sup_id;
            });

            // if ($attendence->isNotEmpty()) {
            //     if (in_array($sup_id, [7, 18, 19, 141])) {
            //         Mail::to($email)
            //             ->send(new FFAttendanceEmail($attendence, $date, $title));
            //     } else {
            //         Mail::to($email)
            //             ->cc(['Fahim.Hara@unilever.com'])
            //             ->send(new FFAttendanceEmail($attendence, $date, $title));
            //     }
            // }
            if ($attendence->isNotEmpty()) {
                if (in_array($sup_id, [7, 18, 19, 141])) {
                    Mail::to($email)
                        ->send(new FFAttendanceEmail($attendence, $date, $title));
                } else {
                    Mail::to($email)
                        ->cc(['ferojul@artisanbn.com'])
                        ->send(new FFAttendanceEmail($attendence, $date, $title));
                }
            }
        }

        return response()->json(['message' => 'Field force attendance sent successfully', 'success' => true]);
    }


    public function monthlyAttendance(Request $request)
    {
        $month = $request->month + 1;
        $year = $request->year;
        $monthName = Carbon::createFromDate($year, $month, 1)->format('F');


        // $attendances = attendence::select('employees.id', 'employees.Employee_Id', 'employees.Full_Name', 'employees.Official_Email', 'attendences.Date', 'attendences.Time_In', 'attendences.Time_Out', 'designations.Name')
        //     ->join('employees', 'employees.id', '=', 'attendences.EID')
        //     ->join('officials', 'employees.id', '=', 'officials.EID')
        //     ->leftjoin('branches', 'branches.id', '=', 'officials.Job_Location_Id')
        //     ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
        //     ->where('officials.Status', '=', 'N')
        //     ->whereMonth('attendences.Date', '=', $month)
        //     ->whereYear('attendences.Date', '=', $year)
        //     ->whereNotNull('employees.Official_Email')
        //     ->orderby('Employee_Id', 'asc')
        //     ->orderby('Date', 'asc')
        //     ->distinct()
        //     ->get()
        //     ->groupBy('Employee_Id');


        $attendances = DB::table('attendences')
            ->select(
                'employees.id',
                'employees.Employee_Id',
                'employees.Full_Name',
                'employees.Official_Email',
                DB::raw('designations.Name AS Designation_Name'),
                'attendences.Date',
                DB::raw('MIN(attendences.Time_In) AS First_In'),
                DB::raw('MAX(attendences.Time_Out) AS Last_Out')
            )
            ->join('employees', 'employees.id', '=', 'attendences.EID')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->leftJoin('branches', 'branches.id', '=', 'officials.Job_location_Id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->where('officials.Status', 'N')
            ->where('attendences.EID', 13)
            ->whereMonth('attendences.Date', 5)
            ->whereYear('attendences.Date', 2025)
            ->whereNotNull('employees.Official_Email')
            ->groupBy(
                'employees.id',
                'employees.Employee_Id',
                'employees.Full_Name',
                'employees.Official_Email',
                'designations.Name',
                'attendences.Date'
            )
            ->orderBy('employees.id', 'asc')
            ->orderBy('attendences.Date', 'asc')
            ->get();



        $leaveData = leave::select('leaves.EID', 'leaves.From_Date', 'leaves.To_Date', 'leave_types.Name as leave_type')
            ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
            ->where('leaves.Status', '=', 'Approved')
            ->whereMonth('leaves.From_Date', '=', $month)
            ->whereYear('leaves.From_Date', '=', $year)
            ->get();


        foreach ($attendances as $employeeId => $records) {

            $employeeEmail = $records->Official_Email;

            foreach ($records as $item) {
                $item->weekday = Carbon::parse($item->Date)->format('l');
            }

            $employeeLeaves = $leaveData->where('EID', $records->id);

            dd($employeeLeaves);

            $employeeAttendance = $records->map(function ($attendance) use ($employeeLeaves) {
                $attendanceDate = $attendance->Date;

                $leaveOnDate = $employeeLeaves->first(function ($leave) use ($attendanceDate) {
                    return $attendanceDate >= $leave->From_Date && $attendanceDate <= $leave->To_Date;
                });

                if ($leaveOnDate) {
                    $attendance->Time_In = $leaveOnDate->leave_type;
                    $attendance->Time_Out = $leaveOnDate->leave_type;
                }

                return $attendance;
            });

            dd($employeeEmail);
            Mail::to($employeeEmail)
                ->send(new MonthlyAttendence($employeeAttendance, $monthName, $year));
        }


        // foreach ($attendances as $employeeId => $records) {

        //     $employeeEmail = $records->Official_Email;

        //     foreach ($records as $item) {
        //         $item->weekday = Carbon::parse($item->Date)->format('l');
        //     }

        //     $employeeLeaves = $leaveData->where('EID', $records->first()->id);

        //     $employeeAttendance = $records->map(function ($attendance) use ($employeeLeaves) {
        //         $attendanceDate = $attendance->Date;

        //         $leaveOnDate = $employeeLeaves->first(function ($leave) use ($attendanceDate) {
        //             return $attendanceDate >= $leave->From_Date && $attendanceDate <= $leave->To_Date;
        //         });

        //         if ($leaveOnDate) {
        //             $attendance->Time_In = $leaveOnDate->leave_type;
        //             $attendance->Time_Out = $leaveOnDate->leave_type;
        //         }

        //         return $attendance;

        //     });

        //     dd($employeeEmail);
        //     Mail::to($employeeEmail)
        //         ->send(new MonthlyAttendence($employeeAttendance, $monthName, $year));
        // }


        return response()->json(['message' => 'Emails sent successfully', 'success' => true]);
    }
}
