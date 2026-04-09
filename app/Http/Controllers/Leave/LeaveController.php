<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\attendence;
use App\Models\leave;
use App\Models\holiday;

use App\Models\app_attendance;

use App\Models\leave_type;
use App\Models\official;
use App\Models\department;
use App\Models\employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use DateTime;

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



    public function departmentList($id)
    {
        $role = Auth::user()->role_id;
        if ($role == 1) {
            $response = department::select('id as department_id', 'Name as department_name')->get();
        } else {
            $response = employee::select('departments.id as department_id', 'departments.Name as department_name')
                ->join('officials', 'officials.EID', '=', 'employees.id')
                ->join('departments', 'departments.id', '=', 'officials.Department_Id')
                ->where('employees.id', $id)
                ->get();
        }
        return response()->json($response);
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
        $currentYear = now()->year;
        $data = official::select('leaves.id as leave_id', 'officials.Supervisor_Id', 'employees.Full_Name', 'employees.id as EID',  'departments.Name as department', 'departments.id as dept_id', 'leaves.status', 'leaves.from_date', 'leaves.to_date', 'leaves.purpose', 'leave_types.leave_name')
            ->join('leaves', 'leaves.employee_id', '=', 'officials.EID')
            ->join('employees', 'employees.id', '=', 'officials.EID')
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('officials.Supervisor_Id', '=', $userId)
            ->where('officials.Status', '=', 'N')
            ->whereYear('leaves.from_date', '=', $currentYear)
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



    public function showUsedLeave($id)
    {
        $currentYear = now()->year;

        $official_leave_info = leave_type::all()->toArray();

        $attendanceEmployeeIds = app_attendance::where('status', 'in')
            ->distinct()
            ->pluck('EID')
            ->toArray();

        $hasAttendance = in_array($id, $attendanceEmployeeIds); //returns only true or false

        $usedLeaveRecords = Leave::where('employee_id', $id)
            ->whereYear('from_date', $currentYear)
            ->where('status', 'Approved')
            ->get()
            ->toArray();



        $usedLeave = [
            'casual' => 0,
            'sick' => 0,
            'earned' => 0,
        ];

        foreach ($usedLeaveRecords as $leave) {
            $from = new DateTime($leave['from_date']);
            $to = new DateTime($leave['to_date']);

            $diffDays = $to->diff($from)->days + 1;

            if ($leave['leave_type_id'] == 1) {
                $usedLeave['casual'] += $diffDays;
            } elseif ($leave['leave_type_id'] == 2) {
                $usedLeave['sick'] += $diffDays;
            } elseif ($leave['leave_type_id'] == 3) {
                $usedLeave['earned'] += $diffDays;
            }
        }


        if ($hasAttendance) {
            $attendanceCount = app_attendance::where('EID', $id)
                ->where('status', 'in')
                ->whereYear('created_at', $currentYear)
                ->count();
            $earnedLeave = (int) floor($attendanceCount / 18);
        } else {
            $attendanceCount = DB::table('attendences')
                ->join('employees', 'employees.id', '=', 'attendences.EID')
                ->where('employees.id', $id)
                ->whereIn('attendences.Status', ['P', 'L'])
                ->whereYear('attendences.Date', $currentYear)

                ->distinct()
                ->count(DB::raw('DATE(attendences.Date)'));

            $earnedLeave = (int) floor($attendanceCount / 18);
        }

        $entitledLeave = [
            'casual' => $official_leave_info[0]['max_leave_days'] ?? 0,
            'sick' => $official_leave_info[1]['max_leave_days'] ?? 0,
            'earned' => $earnedLeave,
        ];

        $result = [
            'entitled_leave' => $entitledLeave,
            'used_leave' => $usedLeave,
        ];

        return response()->json($result);
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
