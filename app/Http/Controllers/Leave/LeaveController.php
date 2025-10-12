<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\attendence;
use App\Models\leave;
use App\Models\holiday;
use App\Models\employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{

    // function __construct()
    // {
    //     $this->middleware(['permission:create user'], ['only' => ['create', 'store']]);
    //     $this->middleware(['permission:user list'], ['only' => ['index', 'show', 'leaveSummery']]);
    // }


    public function index()
    {
        $user = Auth::user();

        if ($user) {
            if ($user->role_id == 1 || $user->role_id == 2) {
                $leaves = $this->getAllLeave();
            } else {
                $leaves = $this->getSubordinates($user->EID);
            }

            foreach ($leaves as $leave) {
                $startDate = new \DateTime($leave->from_date);
                $endDate = new \DateTime($leave->to_date);

                $interval = $startDate->diff($endDate);
                $leave->daysBetween = $interval->days + 1;
            }

            return response()->json($leaves);
        } else {
            return response()->json('User not found');
        }
    }


    public function getAllLeave()
    {
        $currentYear = now()->year;

        $subordinates = leave::select('leaves.id', 'employees.id as EID', 'employees.Full_Name', 'employees.Employee_Id', 'leaves.from_date', 'leaves.to_date', 'leaves.status', 'leaves.attachment_url', 'leaves.purpose', 'leaves.notification', 'leave_types.leave_name', 'leave_types.id as leave_id', 'designations.Name as designation', 'departments.Name as department', 'departments.id as dept_id', 'officials.Supervisor_Id')
            ->join('employees', 'leaves.employee_id', '=', 'employees.id')
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('officials.Status', '=', 'N')
            ->whereYear('leaves.from_date', '=', $currentYear)
            ->orderby('leaves.id', 'desc')
            ->get();


        return $subordinates;
    }



    public function getSubordinates($userId)
    {
        $subordinates = [];
        $allSubordinateIds = [];
        $queue = [$userId];

        // Step 1: Fetch the hierarchy of subordinates
        while (!empty($queue)) {
            $currentUserId = array_shift($queue);

            // Get the subordinates of the current user
            $subordinateUsers = DB::table('officials')
                ->join('employees', 'officials.EID', '=', 'employees.id')
                ->select('employees.id as EID', 'employees.Full_Name')
                ->where('officials.Supervisor_Id', '=', $currentUserId)
                ->where('officials.Status', '=', 'N')
                ->get();

            foreach ($subordinateUsers as $subordinate) {
                if (!in_array($subordinate->EID, $allSubordinateIds)) {
                    $allSubordinateIds[] = $subordinate->EID;
                    $queue[] = $subordinate->EID;
                }
            }
        }

        $currentYear = now()->year;

        // Step 2: Fetch leave records for all subordinates
        if (!empty($allSubordinateIds)) {
            $subordinates = leave::select(
                'leaves.id',
                'employees.id as EID',
                'employees.Full_Name',
                'employees.Employee_Id',
                'leaves.from_date',
                'leaves.to_date',
                'leaves.status',
                'leaves.attachment_url',
                'leaves.purpose',
                'leaves.notification',
                'leave_types.leave_name',
                'leave_types.id as leave_id',
                'designations.Name as designation',
                'departments.Name as department',
                'departments.id as dept_id',
                'officials.Supervisor_Id'
            )
                ->leftjoin('employees', 'leaves.EID', '=', 'employees.id')
                ->leftjoin('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
                ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
                ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
                ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
                ->whereIn('employees.id', $allSubordinateIds)
                ->whereYear('leaves.from_date', '=', $currentYear)
                ->orderby('leaves.id', 'desc')
                ->get();
        }
        return $subordinates;
    }



    public function allLeave()
    {
        $user = Auth::user();

        if ($user) {
            if ($user->role_id == 1 || $user->role_id == 2) {
                $leaves = $this->getAllLeave();
            } else {
                $leaves = $this->getSubordinates($user->EID);
            }

            foreach ($leaves as $leave) {
                $startDate = new \DateTime($leave->from_date);
                $endDate = new \DateTime($leave->to_date);

                $interval = $startDate->diff($endDate);
                $leave->daysBetween = $interval->days + 1;
            }
            return response()->json($leaves);
        } else {
            return response()->json('User not found');
        }
    }

    // public function allLeave()
    // {
    //     $user = Auth::user();

    //     if ($user) {
    //         if ($user->role_id == 1 || $user->role_id == 2) {
    //             $leaves = $this->getAllLeave();
    //         } else {
    //             $leaves = $this->getSubordinates($user->EID);
    //         }

    //         foreach ($leaves as $leave) {
    //             $startDate = new \DateTime($leave->from_date);
    //             $endDate = new \DateTime($leave->to_date);

    //             $interval = $startDate->diff($endDate);
    //             $leave->daysBetween = $interval->days + 1;
    //         }

    //         return response()->json($leaves);
    //     } else {
    //         return response()->json('User not found');
    //     }
    // }

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
        $request->validate([
            'Employee_Id' => 'required',
            'From_Date' => 'required',
            'To_Date' => 'required',
            'Leave_Type_Id' => 'required',
        ], [
            'Employee_Id.required' => 'The Employee ID is required.',
            'Leave_Type_Id.required' => 'The Leave Type is required.',
            'From_Date.unique' => 'The Form Date is required.',
            'To_Date.required' => 'The To Date is required.',
        ]);

        $file_path = null;

        DB::beginTransaction();

        $userId = Auth::user()->EID;

        $leaveExists = leave::where('EID', $request->Employee_Id)
            ->where('From_Date', $request->From_Date)
            ->where('To_Date', $request->To_Date)
            ->exists();

        $response = [
            'message'   =>  'Leave request already submitted.',
        ];

        if ($leaveExists) {
            return response()->json($response);
        }


        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'required|mimes:jpg,jpeg,png,csv,txt,xlx,xls,xlsx,pdf|max:512'
            ]);

            $file_name = time() . '_' . $request->file->getClientOriginalName();
            $file_path = $request->file('file')->storeAs('uploads', $file_name, 'public');
            $leave = leave::create([
                'EID' => $request->input('Employee_Id'),
                'Leave_Type_Id' => $request->input('Leave_Type_Id'),
                'From_Date' => $request->input('From_Date'),
                'To_Date' => $request->input('To_Date'),
                'Purpose' => $request->input('Purpose'),
                'Status' => $request->input('Status'),
                'Attachment_Url' => '/storage/' . $file_path,
                'Notification' => 0,
                'created_by' => $userId,
                'updated_by' => 0,
            ]);
        } else {
            $leave = leave::create([
                'EID' => $request->input('Employee_Id'),
                'Leave_Type_Id' => $request->input('Leave_Type_Id'),
                'From_Date' => $request->input('From_Date'),
                'To_Date' => $request->input('To_Date'),
                'Purpose' => $request->input('Purpose'),
                'Status' => $request->input('Status'),
                'Notification' => 0,
                'created_by' => $userId,
                'updated_by' => 0,
            ]);
        }

        $lastId = leave::latest('id')->value('id');
        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully inserted',
            'id'        =>  $lastId,
        ];

        DB::commit();
        return response()->json($response);

        DB::rollback();
        $response = [
            'success'   =>  false,
            'message'   =>  'Error while inserting',
        ];
        return response()->json($response);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $currentYear = now()->year;

        // $leaves = leave::select('employees.id', 'employees.Full_Name', 'emp_imgs.img_url', 'employees.Employee_Id', 'leaves.from_date', 'leaves.to_date', 'leaves.status', 'leaves.attachment_url', 'leave_types.leave_name', 'designations.Name as designation', 'departments.Name as department', 'leave_types.leave_name as leave_type', 'leave_types.max_leave_days', 'leaves.purpose')
        //     ->join('employees', 'leaves.employee_id', '=', 'employees.id')
        //     ->leftjoin('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
        //     ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
        //     ->join('officials', 'officials.EID', '=', 'employees.id')
        //     ->join('departments', 'officials.Department_Id', '=', 'departments.id')
        //     ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
        //     ->where('leaves.employee_id', '=', $id)
        //     ->whereYear('leaves.from_date', '=', $currentYear)
        //     ->orderby('leaves.id', 'desc')
        //     ->get();

        //    $leaves = DB::table(function ($query) use ($id, $currentYear) {
        //     $query->select(
        //             'leaves.leave_type_id',
        //             DB::raw('SUM(DATEDIFF(to_date, from_date) + 1) as leave_days')
        //         )
        //         ->from('leaves')
        //         ->where('leaves.employee_id', $id)
        //         ->whereYear('leaves.from_date', $currentYear)
        //         ->groupBy('leaves.id', 'leaves.leave_type_id');
        // }, 'aaa')
        // ->join('leave_types as lt', 'lt.id', '=', 'aaa.leave_type_id')
        // ->select(
        //     'aaa.leave_type_id',
        //     'lt.leave_name',
        //     'lt.max_leave_days',
        //     DB::raw('SUM(aaa.leave_days) as leave_count')
        // )
        // ->groupBy('aaa.leave_type_id', 'lt.leave_name', 'lt.max_leave_days')
        // ->get();


        //     $attendanceRecords = DB::table('attendences')
        //         ->join('employees', 'employees.id', '=', 'attendences.EID')
        //         ->where('employees.id', $id)
        //         ->whereIn('attendences.Status', ['P', 'L'])
        //         ->whereYear('attendences.Date', $currentYear)
        //         ->distinct()
        //         ->count();

        //         $earnedLeave = floor($attendanceRecords / 18);


        // foreach ($leaves as $leave) {
        //     $startDate = new \DateTime($leave->from_date);
        //     $endDate = new \DateTime($leave->to_date);

        //     $interval = $startDate->diff($endDate);
        //     $leave->daysBetween = $interval->days + 1;
        // }

        // $leavesWithEarned = DB::table('leaves as l')
        //     ->select(
        //         'lt.id as leave_type_id',
        //         'lt.leave_name',
        //         'lt.max_leave_days',
        //         DB::raw('SUM(DATEDIFF(l.to_date, l.from_date) + 1) as leave_count'),
        //         DB::raw('(SELECT FLOOR(COUNT(DISTINCT a.Date)/18)
        //             FROM attendences a
        //             WHERE a.EID = ' . $id . '
        //                 AND a.Status IN ("P","L")
        //                 AND YEAR(a.Date) = ' . $currentYear . ') as earned_leave')
        //     )
        //     ->join('leave_types as lt', 'lt.id', '=', 'l.leave_type_id')
        //     ->join('employees', 'l.employee_id', '=', 'employees.id')
        //     ->join('officials', 'officials.EID', '=', 'employees.id')
        //     ->join('departments', 'officials.Department_Id', '=', 'departments.id')
        //     ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
        //     ->where('l.employee_id', $id)
        //     ->whereYear('l.from_date', $currentYear)
        //     ->groupBy('lt.id', 'lt.leave_name', 'lt.max_leave_days')
        //     ->get();

        $leavesWithEarned = DB::table('leaves as l')
            ->select(
                'lt.id as leave_type_id',
                'lt.leave_name',
                'lt.max_leave_days',
                'departments.Name as department_name',
                'designations.Name as designation_name',
                'emp_imgs.img_url',
                'employees.Full_Name',
                DB::raw('SUM(DATEDIFF(l.to_date, l.from_date) + 1) as leave_count'),
                DB::raw('(SELECT FLOOR(COUNT(DISTINCT a.Date)/18)
            FROM attendences a
            WHERE a.EID = ' . $id . '
                AND a.Status IN ("P","L")
                AND YEAR(a.Date) = ' . $currentYear . ') as earned_leave')
            )
            ->join('leave_types as lt', 'lt.id', '=', 'l.leave_type_id')
            ->join('employees', 'l.employee_id', '=', 'employees.id')
            ->leftjoin('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('l.employee_id', $id)
            ->whereYear('l.from_date', $currentYear)
            ->groupBy(
                'lt.id',
                'lt.leave_name',
                'lt.max_leave_days',
                'departments.Name',
                'designations.Name',
                'emp_imgs.img_url',
                'employees.Full_Name'
            )
            ->get();




        return response()->json($leavesWithEarned);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(leave $id) {}

    public function leaveSummery($id)
    {
        $leaves = leave::with('leave_type')
            ->where('employee_id', $id)
            ->orderBy('from_date', 'asc')
            ->get();

        foreach ($leaves as $leave) {
            $startDate = new \DateTime($leave->from_date);
            $endDate = new \DateTime($leave->to_date);

            $interval = $startDate->diff($endDate);
            $leave->daysBetween = $interval->days;
        }

        return response()->json($leaves);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $leave = leave::find($id);
        $userId = Auth::user()->EID;
        $leave->update([
            'leave_type_id' => $request->input('Leave_Type'),
            'status' => $request->input('Status'),
            'from_date' => $request->input('From_Date'),
            'to_date' => $request->input('To_Date'),
            'purpose' => $request->input('Purpose'),
            'updated_by' => $userId,
            'Notification' => 0,
        ]);
        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully Updated',
        ];
        return response()->json($response);
    }



    public function supervisorApproval(Request $request)
    {
        $leave = leave::findorfail($request->id);

        $leave->update([
            'Status' => $request->status,
        ]);

        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully inserted',
        ];

        return response()->json($response);
    }

    public function leaveInformation()
    {
        $user_id = Auth::user()->EID;
        $employee = employee::select('employees.id', 'employees.Full_Name', 'emp_imgs.img_url', 'employees.Employee_Id', 'departments.Name as department', 'designations.name as designation')
            ->join('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('employees.id', '=', $user_id)
            ->first();

        $currentYear = now()->year;

        $leaves = leave::select('leaves.from_date', 'leaves.to_date', 'leaves.status', 'leaves.attachment_url', 'leaves.purpose', 'leave_types.leave_name as leave_type', 'leave_types.max_leave_days')
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->where('leaves.employee_id', '=', $user_id)
            ->whereYear('leaves.from_date', '=', $currentYear)
            ->orderby('leaves.id', 'desc')
            ->get();

        foreach ($leaves as $leave) {
            $startDate = new \DateTime($leave->from_date);
            $endDate = new \DateTime($leave->to_date);

            $interval = $startDate->diff($endDate);
            $leave->daysBetween = $interval->days + 1;
        }

        $attendanceRecords = attendence::where('EID', $user_id)
            ->whereIn('Status', ['P', 'L'])
            ->get();

        $totalPresentDays = $attendanceRecords->count();
        $earnedLeave = floor($totalPresentDays / 18);

        $response = [
            'employee' => $employee,
            'leaves' => $leaves,
            'earnedLeave' => $earnedLeave
        ];

        return response()->json($response);
    }


    //Holiday Functions

    public function fetchHoliday(Request $request)
    {
        $year = $request->query('year');
        $holidays = holiday::whereYear('from_date', $year)->orderBy('from_date')->get();
        return response()->json($holidays);
    }

    public function insertHoliday(Request $request)
    {
        DB::beginTransaction();

        $create = holiday::create([
            'holiday_name' => $request->input('name'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'duration' => $request->input('duration'),
        ]);

        if ($create) {
            $response = [
                'success'   =>  true,
                'message'   =>  'Successfully inserted',
            ];
        }


        DB::commit();
        return response()->json($response);

        DB::rollback();
        $response = [
            'success'   =>  false,
            'message'   =>  'Error while inserting',
        ];
        return response()->json($response);
    }


    public function updateHoliday(Request $request, $id)
    {
        DB::beginTransaction();

        dd($id);
        $holiday = holiday::findorfail($id);
        $holiday->update([
            'holiday_name' => $request->input('name'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'duration' => $request->input('duration'),
        ]);
        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully Updated',
        ];

        DB::commit();
        return response()->json($response);

        DB::rollback();
        $response = [
            'success'   =>  false,
            'message'   =>  'Error while updating',
        ];
        return response()->json($response);
    }

    public function editHoliday($id)
    {
        $holiday = holiday::find($id);
        return response()->json($holiday);
    }

    public function softDeleteLeave($id)
    {
        $leave = leave::find($id);

        if ($leave) {
            $leave->delete();
            $response = [
                'success'   =>  true,
                'message'   =>  'Holiday deleted successfully',
            ];
            return response()->json($response);
        }
    }

    public function deleteHoliday($id)
    {
        $holiday = holiday::find($id);

        if ($holiday) {
            $holiday->delete();
            $response = [
                'success'   =>  true,
                'message'   =>  'Holiday deleted successfully',
            ];
            return response()->json($response);
        }
    }
}
