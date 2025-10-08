<?php

namespace App\Http\Controllers\payroll;

use App\Http\Controllers\Controller;
use App\Models\employee;
use App\Models\salary_adjustment;
use App\Models\salary_deduction;
use App\Models\salary_earning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalaryAdjustmentController extends Controller
{
    
   public function index()
    {
        $earnings = DB::table('employees')
            ->join('officials', 'employees.id', '=', 'officials.EID')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('salary_earnings', 'employees.id', '=', 'salary_earnings.employee_id')
            ->select(
                'employees.id as eid',
                'employees.Full_Name as emp_name',
                'departments.id as dept_id',
                'departments.Name as emp_dept',
                'salary_earnings.adjustment_type as adjustment_type',
                DB::raw('CAST(salary_earnings.amount AS UNSIGNED) as amount'),
                'salary_earnings.date as date',
                'salary_earnings.remarks as remarks',
                DB::raw("'earning' as type")
            ); 

        $deductions = DB::table('employees')
            ->join('officials', 'employees.id', '=', 'officials.EID')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('salary_deductions', 'employees.id', '=', 'salary_deductions.employee_id')
            ->select(
                'employees.id as eid',
                'employees.Full_Name as emp_name',
                'departments.id as dept_id',
                'departments.Name as emp_dept',
                'salary_deductions.adjustment_type as adjustment_type',
                DB::raw('CAST(salary_deductions.amount AS UNSIGNED) as amount'), 
                'salary_deductions.date as date',
                'salary_deductions.remarks as remarks',
                DB::raw("'deduction' as type")
            );

        $data = $earnings
            ->unionAll($deductions)
            ->orderBy('date', 'desc')
            ->paginate(12);

        return response()->json($data);
    }

    // public function index()
    // {
    //     $salary_adjustment = employee::with('salary_earning', 'salary_deduction')->get();
    //     return response()->json($salary_adjustment);
    // }

    public function create(Request $request)
    {
        
        DB::beginTransaction();
        $userId = Auth::user()->EID;       
        

        if ($request->type == "deduction") {
            $adjustment = salary_deduction::where('employee_id', $request->eid)
                ->where('adjustment_type', $request->type)
                ->exists();
        } else if ($request->type == "earning") {
            $adjustment = salary_earning::where('employee_id', $request->eid)
                ->where('adjustment_type', $request->type)
                ->exists();
        }

        $response = [
            'flag'   =>  'add',
            'message'   =>  'Adjustment already submitted.',
        ];

        if ($adjustment) {
            return response()->json($response);
        }
        if($request->type == "deduction"){
            salary_deduction::create([
                'employee_id' => $request->input('eid'),
                'adjustment_type' => $request->input('adjustmentCategory'),
                'date' => $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01',
                'amount' => $request->input('amount'),
                'remarks' => $request->input('remarks'),
                'created_by' => $userId,
            ]);
        } else if($request->type == "earning"){
            salary_earning::create([
                'employee_id' => $request->input('eid'),
                'adjustment_type' => $request->input('adjustmentCategory'),
                'date' => $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01',
                'amount' => $request->input('amount'),
                'remarks' => $request->input('remarks'),
                'created_by' => $userId,
            ]);

        }

        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully inserted',
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


    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        $userId = Auth::user()->EID;

        try {
            $earning = null;
            $deduction = null;
            if($request->type == "earning"){
                $earning = salary_earning::where('employee_id', $id)->first();
            } else if($request->type == "deduction"){
                $deduction = salary_deduction::where('employee_id', $id)->first();
            }        

            
            if (!$earning && !$deduction) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Salary adjustment record not found.',
                ], 404);
            }
            if ($earning) {
                $earning->update([
                    'employee_id' => $request->input('eid'),
                    'adjustment_type' => $request->input('adjustmentCategory'),
                    'date' => $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01',
                    'amount' => $request->input('amount'),
                    'remarks' => $request->input('remarks'),
                    'created_by' => $userId,
                ]);
            } else if ($deduction) {
                $deduction->update([
                    'employee_id' => $request->input('eid'),
                    'adjustment_type' => $request->input('adjustmentCategory'),
                    'date' => $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01',
                    'amount' => $request->input('amount'),
                    'remarks' => $request->input('remarks'),
                    'created_by' => $userId,                    
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Successfully updated', // Changed message to reflect update
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error while updating: ' . $e->getMessage(),
            ], 500);
        }
    }
}
