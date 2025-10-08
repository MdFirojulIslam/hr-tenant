<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\app_attendance;
use App\Models\emplooyee_expense;
use App\Models\employee;
use App\Models\employee_expense_permission;
use App\Models\leave;
use App\Models\town_belong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmplooyeeExpenseController extends Controller
{
    public function index(Request $request)
    {

        $userId = Auth::user()->EID;
        $month = $request->query('month');
        $year = $request->query('year');

        if ($request->query('id')) {
            $userId = $request->query('id');
        }

        $allowance = emplooyee_expense::whereNot('Tr_Type', 'ta')
            ->whereMonth('date', $month + 1)
            ->whereYear('date', $year)
            ->where('EID', $userId)
            ->get()
            ->map(function ($expense) {
                $expense->weekday = \Carbon\Carbon::parse($expense->Date)->format('l');
                return $expense;
            });

        $travel = emplooyee_expense::where('Tr_Type', 'ta')
            ->whereMonth('date', $month + 1)
            ->whereYear('date', $year)
            ->where('EID', $userId)
            ->get()
            ->map(function ($expense) {
                $expense->weekday = \Carbon\Carbon::parse($expense->Date)->format('l');
                return $expense;
            });

        $response = [
            'allowance' => $allowance,
            'travel' => $travel,
        ];
        return response()->json($response);
    }



    public function fetchUserTown($id)
    {
        $towns = town_belong::with('town')->where('EID', $id)->get();
        return response()->json($towns);
    }

    // public function store(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $userId = Auth::user()->EID;

    //         $data = $request->all();
    //         $allowanceDate = $data['allowance'][0]['date'] ?? null;
    //         $attendance_lock = employee_expense_permission::where('EID', $userId)->first();

    //         if ($allowanceDate) {
    //             if ($attendance_lock->Attendance_Lock == 1) {
    //                 $leaveData = leave::where('leaves.Status', 'Approved')
    //                     ->where('leaves.EID', $userId)
    //                     ->where(function ($query) use ($allowanceDate) {
    //                         $query->where('leaves.From_Date', '<=', $allowanceDate)
    //                             ->where('leaves.To_Date', '>=', $allowanceDate);
    //                     })
    //                     ->exists();

    //                 $hasAttendance = app_attendance::where('EID', $userId)
    //                     ->whereDate('updated_at', $allowanceDate)
    //                     ->exists();

    //                 if ($leaveData) {
    //                     return response()->json([
    //                         'success' => false,
    //                         'message' => 'Employee is Leave on ' . $allowanceDate . ', cannot submit expense.',
    //                     ], 400);
    //                 }

    //                 if (!$hasAttendance) {
    //                     return response()->json([
    //                         'success' => false,
    //                         'message' => 'Employee is Absent on ' . $allowanceDate . ', cannot submit expense.',
    //                     ], 400);
    //                 }
    //             } else {
    //                 $month = date('m', strtotime($allowanceDate));
    //                 $year = date('Y', strtotime($allowanceDate));

    //                 $check_date = emplooyee_expense::whereMonth('Date', $month)
    //                     ->whereYear('Date', $year)
    //                     ->where('EID', $userId)
    //                     ->where('Status', 1)
    //                     ->first();

    //                 if ($check_date) {
    //                     return response()->json([
    //                         'success' => false,
    //                         'message' => 'Your Expense for this month is Approved.',
    //                     ], 400);
    //                 }
    //             }
    //         }

    //         // Loop through the allowance array
    //         if (!empty($request->allowance) && is_array($request->allowance)) {
    //             foreach ($request->allowance as $index => $item) {
    //                 $filePath = null;

    //                 // Check if the current item has a file
    //                 if (isset($item['attachment'])) {
    //                     $file = $item['attachment'];
    //                     $fileName = time() . '_' . $file->getClientOriginalName();
    //                     $filePath = $file->storeAs('expense', $fileName, 'public');
    //                     $allowance = emplooyee_expense::create([
    //                         'EID' => $userId,
    //                         'Date' => $item['date'],
    //                         'Town_Id' => $item['town_id'],
    //                         'Tr_Type' => $item['particular'],
    //                         'Description' => $item['description'] ?? null,
    //                         'File_Path' => '/storage/' . $filePath, // Store file path for this record
    //                         'Amount' => $item['amount'],
    //                         'created_by' => $userId,
    //                         'updated_by' => 0,
    //                     ]);
    //                 } else {

    //                     // Insert Daily Allowance (DA) record
    //                     $allowance = emplooyee_expense::create([
    //                         'EID' => $userId,
    //                         'Date' => $item['date'],
    //                         'Town_Id' => $item['town_id'],
    //                         'Tr_Type' => $item['particular'],
    //                         'Description' => $item['description'] ?? null,
    //                         'Amount' => $item['amount'],
    //                         'created_by' => $userId,
    //                         'updated_by' => 0,
    //                     ]);
    //                 }
    //             }
    //         }

    //         // Loop through the travels array
    //         if (!empty($request->travels) && is_array($request->travels)) {
    //             foreach ($request->travels as $index => $item) {
    //                 $filePath = null;

    //                 // Check if the current item has a file
    //                 if (isset($item['attachment'])) {
    //                     $file = $item['attachment'];
    //                     $fileName = time() . '_' . $file->getClientOriginalName();
    //                     $filePath = $file->storeAs('uploads', $fileName, 'public');
    //                     $travel = emplooyee_expense::create([
    //                         'EID' => $userId,
    //                         'Date' => $item['date'],
    //                         'Town_Id' => $item['town_id'],
    //                         'Tr_Type' => 'ta',
    //                         'From' => $item['from'] ?? null,
    //                         'To' => $item['to'] ?? null,
    //                         'Vehicle' => $item['vehicle'] ?? null,
    //                         'File_Path' => '/storage/' . $filePath,
    //                         'Amount' => $item['amount'],
    //                         'created_by' => $userId,
    //                         'updated_by' => 0,
    //                     ]);
    //                 } else {

    //                     // Insert Travel Allowance (TA) record
    //                     $travel = emplooyee_expense::create([
    //                         'EID' => $userId,
    //                         'Date' => $item['date'],
    //                         'Town_Id' => $item['town_id'],
    //                         'Tr_Type' => 'ta',
    //                         'From' => $item['from'] ?? null,
    //                         'To' => $item['to'] ?? null,
    //                         'Vehicle' => $item['vehicle'] ?? null,
    //                         'Amount' => $item['amount'],
    //                         'created_by' => $userId,
    //                         'updated_by' => 0,
    //                     ]);
    //                 }
    //             }
    //         }

    //         // Commit the transaction
    //         DB::commit();

    //         // Return success response
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Data successfully inserted',
    //         ], 201);
    //     } catch (\Exception $e) {
    //         // Rollback the transaction in case of error
    //         DB::rollback();

    //         // Return error response
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error while inserting data',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }



    //Store expense data
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $userId = Auth::user()->EID;
            $data = $request->all();
            $allowanceDate = $data['allowance'][0]['date'] ?? null;
            $attendanceLock = employee_expense_permission::where('EID', $userId)->value('Attendance_Lock');

            if ($allowanceDate) {
                if ($attendanceLock == 1) {
                    $leaveExists = leave::where('Status', 'Approved')
                        ->where('EID', $userId)
                        ->where('From_Date', '<=', $allowanceDate)
                        ->where('To_Date', '>=', $allowanceDate)
                        ->exists();

                    $attendanceExists = app_attendance::where('EID', $userId)
                        ->whereDate('updated_at', $allowanceDate)
                        ->exists();

                    if ($leaveExists) {
                        return response()->json([
                            'success' => false,
                            'message' => "Employee is on leave on $allowanceDate, cannot submit expense."
                        ], 400);
                    }

                    if (!$attendanceExists) {
                        return response()->json([
                            'success' => false,
                            'message' => "Employee was absent on $allowanceDate, cannot submit expense."
                        ], 400);
                    }
                }
                $month = date('m', strtotime($allowanceDate));
                $year = date('Y', strtotime($allowanceDate));

                $approvedExpense = emplooyee_expense::whereMonth('Date', $month)
                    ->whereYear('Date', $year)
                    ->where('EID', $userId)
                    ->where('Status', 1)
                    ->exists();

                if ($approvedExpense) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your expense for this month is already approved.'
                    ], 400);
                }
            }

            // Insert Allowance and Travel Data
            $this->insertExpenseRecords($request->allowance, $userId);
            $this->insertExpenseRecords($request->travels, $userId, 'ta');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data successfully inserted'
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error while inserting data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper function to insert allowance and travel records.
     */
    private function insertExpenseRecords($records, $userId, $type = null)
    {
        if (!empty($records) && is_array($records)) {
            foreach ($records as $item) {
                $filePath = $this->uploadFile($item['attachment'] ?? null);

                emplooyee_expense::create([
                    'EID' => $userId,
                    'Date' => $item['date'],
                    'Town_Id' => $item['town_id'],
                    'Tr_Type' => $type ?? $item['particular'],
                    'Description' => $item['description'] ?? null,
                    'File_Path' => $filePath,
                    'Amount' => $item['amount'],
                    'From' => $item['from'] ?? null,
                    'To' => $item['to'] ?? null,
                    'Vehicle' => $item['vehicle'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => 0,
                ]);
            }
        }
    }

    /**
     * Helper function to handle file upload.
     */
    private function uploadFile($file)
    {
        if ($file) {
            $fileName = time() . '_' . $file->getClientOriginalName();
            return '/storage/' . $file->storeAs('uploads', $fileName, 'public');
        }
        return null;
    }


    public function otherExpenseUpdate(Request $request, $id)
    {
        DB::beginTransaction();

        $expense = emplooyee_expense::findOrFail($id);
        try {

            $file_path = null;

            if (isset($request->attachment)) {
                $file_name = time() . '_' . $request->attachment->getClientOriginalName();
                $file_path = $request->attachment->storeAs('uploads', $file_name, 'public');

                $existingFile = emplooyee_expense::where('id', $id)->first();

                if ($existingFile) {
                    $existingFile = str_replace('/storage/', '', $expense->File_Path);
                    Storage::disk('public')->delete($existingFile);
                }
                $expense->update([
                    'File_Path' => '/storage/' . $file_path,
                ]);
            }

            $expense->update([
                'Tr_Type' => $request->particular,
                'Description' => $request->description,
                'Amount' => $request->amount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data successfully inserted',
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error while inserting data',
            ], 500);
        }
    }


    public function travelUpdate(Request $request, $id)
    {
        DB::beginTransaction();
        $expense = emplooyee_expense::findOrFail($id);
        try {

            $file_path = null;

            if (isset($request->attachment)) {
                $file_name = time() . '_' . $request->attachment->getClientOriginalName();
                $file_path = $request->attachment->storeAs('uploads', $file_name, 'public');

                $existingFile = emplooyee_expense::where('id', $id)->first();

                if ($existingFile) {
                    $existingFile = str_replace('/storage/', '', $expense->File_Path);
                    Storage::disk('public')->delete($existingFile);
                }
                $expense->update([
                    'File_Path' => '/storage/' . $file_path,
                ]);
            }

            $expense->update([
                'From' => $request->from,
                'To' => $request->to,
                'Vehicle' => $request->vehicle,
                'Amount' => $request->amount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data successfully inserted',
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error while inserting data',
            ], 500);
        }
    }


    public function approveExpense(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');
        $userId = $request->query('id');
        $authUser = auth()->user();

        DB::beginTransaction();
        try {
            $employee = employee::find($userId);
            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
            }

            // Define approval hierarchy
            $approvers = [
                'employee_approval' => $employee->id,
                'supervisor_approval' => $employee->official->Supervisor_Id ?? null,
                'hod_approval' => $employee->official->supervisor->official->Supervisor_Id ?? null,
                'ceo_approval' => $employee->official->supervisor->official->supervisor->official->Supervisor_Id ?? null,
            ];

            // Ensure we get a single record, not a collection
            $expense = emplooyee_expense::where('EID', $userId)
                ->whereMonth('Date', $month + 1)
                ->whereYear('Date', $year)
                ->first();

            if (!$expense) {
                return response()->json(['success' => false, 'message' => 'Expense record not found'], 404);
            }

            // Approval logic with correct sequential checking
            $previousApproval = null;
            $i = 0;

            foreach ($approvers as $column => $approverId) {
                $i++;
                if ($authUser->EID === $approverId) {
                    emplooyee_expense::where('EID', $userId)
                        ->whereMonth('Date', $month + 1)
                        ->whereYear('Date', $year)
                        ->update([
                            'Status' => $i,
                        ]);

                    DB::commit();
                    return response()->json(['success' => true, 'message' => 'Approval successful'], 201);
                }
            }

            return response()->json(['success' => false, 'message' => 'Unauthorized approval attempt'], 403);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Error while updating data', 'error' => $e->getMessage()], 500);
        }
    }



    public function deleteExpense($id)
    {
        $expense = emplooyee_expense::find($id);

        if ($expense) {
            $expense->delete();
            $response = [
                'success'   =>  true,
                'message'   =>  'Expense deleted successfully',
            ];
            return response()->json($response);
        }
    }


    public function trashedExpense(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');
        $userId = $request->query('userId');

        $expense = emplooyee_expense::onlyTrashed()
            ->whereMonth('date', $month + 1)
            ->whereYear('date', $year)
            ->where('EID', $userId)
            ->get()
            ->map(function ($expense) {
                $expense->weekday = \Carbon\Carbon::parse($expense->Date)->format('l');
                return $expense;
            });

        return response()->json($expense);
    }



    public function getUsersExpense(Request $request)
    {
        DB::statement("SET SQL_MODE=''");

        $month = $request->query('month');
        $year = $request->query('year');
        $user = Auth::user();

        // Get all subordinates recursively
        $allSubordinateIds = $this->getAllSubordinateIds($user->EID);

        $query = DB::table('emplooyee_expenses')
            ->join('employees', 'emplooyee_expenses.EID', '=', 'employees.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->leftJoin('employees as supervisor', 'supervisor.id', '=', 'officials.Supervisor_Id')
            ->leftJoin('officials as sup_official', 'supervisor.id', '=', 'sup_official.EID')
            ->leftJoin('employees as hod', 'hod.id', '=', 'sup_official.Supervisor_Id')
            ->leftJoin('officials as hod_official', 'hod.id', '=', 'hod_official.EID')
            ->leftJoin('employees as ceo', 'ceo.id', '=', 'hod_official.Supervisor_Id')
            ->select(
                'employees.id',
                'employees.Full_Name',
                'departments.Name as Dept_Name',
                'designations.Name as Des_Name',
                DB::raw('SUM(emplooyee_expenses.Amount) as total_expense'),
                DB::raw('MIN(emplooyee_expenses.Status) as Status'),
                'supervisor.id as sup_id',
                'hod.id as hod_id',
                'ceo.id as ceo_id'
            )
            ->whereNull('emplooyee_expenses.deleted_at')
            ->whereMonth('emplooyee_expenses.date', $month + 1)
            ->whereYear('emplooyee_expenses.date', $year)
            ->groupBy(
                'employees.id',
                'employees.Full_Name',
                'departments.Name',
                'designations.Name',
                'supervisor.id',
                'hod.id',
                'ceo.id'
            );

        // Special case for specific user IDs
        if ($user->role_id == 1 || $user->role_id == 2) {
            $expenses = $query->get();
        } else {
            $expenses = $query->whereIn('emplooyee_expenses.EID', $allSubordinateIds)->get();
        }

        $grandTotal = number_format($expenses->sum('total_expense'), 2, '.', '');

        return response()->json([
            'expenses' => $expenses,
            'grand_total' => $grandTotal
        ]);
    }



    private function getAllSubordinateIds($userId)
    {
        $allSubordinateIds = [];
        $queue = [$userId];

        while (!empty($queue)) {
            $currentUserId = array_shift($queue);

            $subordinateUsers = DB::table('officials')
                ->where('Supervisor_Id', $currentUserId)
                ->pluck('EID')
                ->toArray();

            foreach ($subordinateUsers as $subordinateId) {
                if (!in_array($subordinateId, $allSubordinateIds)) {
                    $allSubordinateIds[] = $subordinateId;
                    $queue[] = $subordinateId;
                }
            }
        }

        return $allSubordinateIds;
    }


    public function getLockPermission($id)
    {
        $data = employee_expense_permission::select('Date_Lock')->where('EID', $id)->first();

        return response()->json($data);
    }

    public function getExpensePermission()
    {
        $data = employee_expense_permission::select('employee_expense_permissions.id', 'employees.Employee_Id', 'employee_expense_permissions.EID', 'employees.Full_Name', 'employee_expense_permissions.Date_Lock', 'employee_expense_permissions.Attendance_Lock')
            ->join('employees', 'employee_expense_permissions.EID', '=', 'employees.id')
            ->orderBy('employees.Full_Name', 'asc')
            ->get();

        return response()->json($data);
    }

    public function updateExpensePermission(Request $request)
    {
        try {
            // Find the record
            $permission = employee_expense_permission::findOrFail($request->id);

            // Update based on type
            if ($request->type === 'date') {
                $permission->Date_Lock = $request->value;
            } elseif ($request->type === 'att') {
                $permission->Attendance_Lock = $request->value;
            }

            $permission->save();

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating permission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
