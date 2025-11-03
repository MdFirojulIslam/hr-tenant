<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\attendence;
use App\Models\leave;
use App\Models\holiday;
use App\Models\official;
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

        $subordinates = leave::select('leaves.id as leave_id', 'employees.id as EID', 'employees.Full_Name', 'employees.Employee_Id', 'leaves.from_date', 'leaves.to_date', 'leaves.status', 'leaves.attachment_url', 'leaves.purpose', 'leaves.notification', 'leave_types.leave_name', 'leave_types.id as leave_type_id', 'designations.Name as designation', 'departments.Name as department', 'departments.id as dept_id', 'officials.Supervisor_Id')
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
        $data = official::select('leaves.id as leave_id', 'officials.Supervisor_Id', 'employees.Full_Name', 'employees.id as EID',  'departments.Name as department', 'departments.id as dept_id', 'leaves.status', 'leaves.from_date', 'leaves.to_date', 'leaves.purpose', 'leave_types.leave_name')
            ->join('leaves', 'leaves.employee_id', '=', 'officials.EID')
            ->join('employees', 'employees.id', '=', 'officials.EID')
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('officials.Supervisor_Id', '=', $userId)
            ->get();
        return $data;
    }




    // public function getSubordinates($userId)
    // {
    //     $subordinates = [];
    //     $allSubordinateIds = [];
    //     $queue = [$userId];

    //     // Step 1: Fetch the hierarchy of subordinates
    //     while (!empty($queue)) {
    //         $currentUserId = array_shift($queue);

    //         // Get the subordinates of the current user
    //         $subordinateUsers = DB::table('officials')
    //             ->join('employees', 'officials.EID', '=', 'employees.id')
    //             ->select('employees.id as EID', 'employees.Full_Name')
    //             ->where('officials.Supervisor_Id', '=', $currentUserId)
    //             ->where('officials.Status', '=', 'N')
    //             ->get();

    //         foreach ($subordinateUsers as $subordinate) {
    //             if (!in_array($subordinate->EID, $allSubordinateIds)) {
    //                 $allSubordinateIds[] = $subordinate->EID;
    //                 $queue[] = $subordinate->EID;
    //             }
    //         }
    //     }

    //     $currentYear = now()->year;

    //     // Step 2: Fetch leave records for all subordinates
    //     if (!empty($allSubordinateIds)) {
    //         $subordinates = leave::select(
    //             'leaves.id',
    //             'employees.id as EID',
    //             'employees.Full_Name',
    //             'employees.Employee_Id',
    //             'leaves.from_date',
    //             'leaves.to_date',
    //             'leaves.status',
    //             'leaves.attachment_url',
    //             'leaves.purpose',
    //             'leaves.notification',
    //             'leave_types.leave_name',
    //             'leave_types.id as leave_id',
    //             'designations.Name as designation',
    //             'departments.Name as department',
    //             'departments.id as dept_id',
    //             'officials.Supervisor_Id'
    //         )
    //             ->leftjoin('employees', 'leaves.EID', '=', 'employees.id')
    //             ->leftjoin('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
    //             ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
    //             ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
    //             ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
    //             ->whereIn('employees.id', $allSubordinateIds)
    //             ->whereYear('leaves.from_date', '=', $currentYear)
    //             ->orderby('leaves.id', 'desc')
    //             ->get();
    //     }
    //     return $subordinates;
    // }


    // public function getSubordinates($userId)
    // {
    //     $subordinates = [];
    //     $allSubordinateIds = [];
    //     $queue = [$userId];

    //     dd($queue);

    //     // Step 1: Fetch the hierarchy of subordinates
    //     while (!empty($queue)) {
    //         $currentUserId = array_shift($queue);

    //         // Get the subordinates of the current user
    //         $subordinateUsers = DB::table('officials')
    //             ->join('employees', 'officials.EID', '=', 'employees.id')
    //             ->select('employees.id as EID', 'employees.Full_Name')
    //             ->where('officials.Supervisor_Id', '=', $currentUserId)
    //             ->where('officials.Status', '=', 'N')
    //             ->get();

    //         foreach ($subordinateUsers as $subordinate) {
    //             if (!in_array($subordinate->EID, $allSubordinateIds)) {
    //                 $allSubordinateIds[] = $subordinate->EID;
    //                 $queue[] = $subordinate->EID;
    //             }
    //         }
    //     }

    //     $currentYear = now()->year;

    //     dd($allSubordinateIds);

    //     // Step 2: Fetch leave records for all subordinates
    //     if (!empty($allSubordinateIds)) {
    //         $subordinates = leave::select(
    //             'leaves.id as leave_id',
    //             'employees.id as EID',
    //             'employees.Full_Name',
    //             'employees.Employee_Id',
    //             'leaves.from_date',
    //             'leaves.to_date',
    //             'leaves.status',
    //             'leaves.attachment_url',
    //             'leaves.purpose',
    //             'leaves.notification',
    //             'leave_types.leave_name',
    //             'leave_types.id as leaves_type_id',
    //             'designations.Name as designation',
    //             'departments.Name as department',
    //             'departments.id as dept_id',
    //             'officials.Supervisor_Id'
    //         )
    //             ->leftjoin('employees', 'leaves.EID', '=', 'employees.id')
    //             ->leftjoin('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
    //             ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
    //             ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
    //             ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
    //             ->whereIn('employees.id', $allSubordinateIds)
    //             ->whereYear('leaves.from_date', '=', $currentYear)
    //             ->orderby('leaves.id', 'desc')
    //             ->get();
    //     }
    //     return $subordinates;
    // }




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
            'employee_id' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
            'leave_type_id' => 'required',
        ], [
            'employee_id.required' => 'The Employee ID is required.',
            'leave_type_id.required' => 'The Leave Type is required.',
            'from_date.unique' => 'The From Date is required.',
            'to_date.required' => 'The To Date is required.',
        ]);

        $file_path = null;

        DB::beginTransaction();

        $userId = Auth::user()->EID;

        $leaveExists = leave::where('employee_id', $request->employee_id)
            ->where('from_date', $request->from_date)
            ->where('to_date', $request->to_date)
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
                'employee_id' => $request->input('employee_id'),
                'leave_type_id' => $request->input('leave_type_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'purpose' => $request->input('purpose'),
                'status' => $request->input('status'),
                'attachment_url' => '/storage/' . $file_path,
                'notification' => 0,
                'created_by' => $userId,
                'updated_by' => 0,
            ]);
        } else {
            $leave = leave::create([
                'employee_id' => $request->input('employee_id'),
                'leave_type_id' => $request->input('leave_type_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'purpose' => $request->input('purpose'),
                'status' => $request->input('status'),
                'notification' => 0,
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
        $currentYear = date('Y');

        $leavesWithEarned = DB::table('leaves as l')
            ->select(
                'l.employee_id',
                'e.Full_Name',
                'lt.leave_name',
                DB::raw('CASE WHEN lt.leave_name LIKE "%Earn%" THEN IFNULL(a.total_earn,0) ELSE lt.max_leave_days END as total_max_days'),
                DB::raw('SUM(DATEDIFF(l.to_date, l.from_date) + 1) as used_leave'),
                'd.Name as department_name',
                'des.Name as designation_name',
                'ei.img_url'
            )
            ->join('leave_types as lt', 'lt.id', '=', 'l.leave_type_id')
            ->join('employees as e', 'e.id', '=', 'l.employee_id')
            ->join('officials as o', 'o.EID', '=', 'e.id')
            ->join('departments as d', 'd.id', '=', 'o.Department_Id')
            ->join('designations as des', 'des.id', '=', 'o.Designation_Id')
            ->leftJoin('emp_imgs as ei', function ($join) {
                $join->on('ei.EID', '=', 'e.id')
                    ->whereRaw('ei.id = (SELECT MAX(id) FROM emp_imgs WHERE EID = e.id)');
            })
            ->leftJoin(DB::raw("(
            SELECT
                EID,
                FLOOR(COUNT(CASE WHEN STATUS IN ('P', 'L', 'in') THEN 1 END) / 18) AS total_earn
            FROM (
                SELECT EID, STATUS, DATE
                FROM attendences
                WHERE YEAR(DATE) = {$currentYear}

                UNION ALL

                SELECT EID, STATUS, DATE(created_at) AS DATE
                FROM app_attendances
                WHERE YEAR(created_at) = {$currentYear}
            ) all_att
            GROUP BY EID
        ) a"), 'a.EID', '=', 'l.employee_id')
            ->where('l.employee_id', $id)
            ->whereYear('l.from_date', $currentYear)
            ->where('l.status', 'Approved')
            ->whereIn('l.leave_type_id', [1, 2, 3])
            ->groupBy(
                'l.employee_id',
                'e.Full_Name',
                'lt.leave_name',
                'lt.max_leave_days',
                'd.Name',
                'des.Name',
                'ei.img_url',
                'a.total_earn'
            )
            ->orderBy('lt.leave_name', 'ASC')
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
        $leave = Leave::find($id);
        if (!$leave) {
            return response()->json(['success' => false, 'message' => 'Leave not found'], 404);
        }

        $userId = Auth::user()->EID ?? null;

        $leave->update([
            'leave_type_id' => $request->input('leave_type'),
            'status' => $request->input('status'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'purpose' => $request->input('purpose'),
            'updated_by' => $userId,
            'Notification' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully Updated',
        ]);
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


    //added by firoj
    public function supervisedApproval(Request $request)
    {
        $user_id = Auth::user()->EID;
        $id = $request->input('id');
        $status = $request->input('status');

        DB::beginTransaction();
        try {
            $existLeaveID = Leave::find($id);

            if (!$existLeaveID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave not found',
                ], 404);
            }

            $existLeaveID->update([
                'status' => $status,
                'updated_by' => $user_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave status updated successfully',
                'leave' => $existLeaveID,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error while updating: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function hrApproval(Request $request)
    {
        $user_id = Auth::user()->EID;
        $id = $request->input('id');
        $status = $request->input('status');

        DB::beginTransaction();
        try {
            $existLeaveID = Leave::find($id);

            if (!$existLeaveID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave not found',
                ], 404);
            }

            $existLeaveID->update([
                'status' => $status,
                'updated_by' => $user_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave status updated successfully',
                'leave' => $existLeaveID,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error while updating: ' . $e->getMessage(),
            ], 500);
        }
    }
}
