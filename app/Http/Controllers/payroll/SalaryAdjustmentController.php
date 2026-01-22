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
            ->join('salary_adjustment_types', 'salary_earnings.adjustment_types_id', '=', 'salary_adjustment_types.id')
            ->where('salary_adjustment_types.adjustment_type', 1)
            ->select(
                'salary_earnings.id as earning_id',
                'employees.id as eid',
                'employees.Employee_Id as employee_official_id',
                'employees.Full_Name as emp_name',
                'departments.id as dept_id',
                'departments.Name as emp_dept',
                'salary_adjustment_types.id as adjustment_id',
                'salary_adjustment_types.adjustment_name',
                DB::raw('CAST(salary_earnings.amount AS UNSIGNED) as amount'),
                'salary_earnings.date as date',
                'salary_earnings.remarks as remarks',
                DB::raw("'earning' as type")
            );

        $deductions = DB::table('employees')
            ->join('officials', 'employees.id', '=', 'officials.EID')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('salary_deductions', 'employees.id', '=', 'salary_deductions.employee_id')
            ->join('salary_adjustment_types', 'salary_deductions.adjustment_types_id', '=', 'salary_adjustment_types.id')
            ->where('salary_adjustment_types.adjustment_type', 0)
            ->select(
                'salary_deductions.id as deduction_id',
                'employees.id as eid',
                'employees.Employee_Id as employee_official_id',
                'employees.Full_Name as emp_name',
                'departments.id as dept_id',
                'departments.Name as emp_dept',
                'salary_adjustment_types.id as adjustment_id',
                'salary_adjustment_types.adjustment_name',
                DB::raw('CAST(salary_deductions.amount AS UNSIGNED) as amount'),
                'salary_deductions.date as date',
                'salary_deductions.remarks as remarks',
                DB::raw("'deduction' as type")
            );

        $data = $earnings
            ->unionAll($deductions)
            ->orderBy('dept_id', 'asc')
            ->orderBy('date', 'desc')
            ->paginate(30);

        return response()->json($data);
    }

    public function getAdjustmentToInsert()
    {
        $adjustmentTypes = DB::table('salary_adjustment_types')
            ->select('id', 'adjustment_name', 'adjustment_type')
            ->where('status', 1)
            ->get();

        $earnings = $adjustmentTypes->where('adjustment_type', 1)->values();
        $deductions = $adjustmentTypes->where('adjustment_type', 0)->values();

        // Example: return as JSON
        return response()->json([
            'earnings' => $earnings,
            'deductions' => $deductions,
        ]);
    }

    public function getAdjustmentToEdit()
    {
        $adjustmentTypes = DB::table('salary_adjustment_types')
            ->select(
                'id as adjustment_id',
                'adjustment_name',
                'adjustment_type'
            )
            ->where('status', 1)
            ->get();

        return response()->json($adjustmentTypes);
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


        $date = $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01';

        if (!empty($request->earnings)) {
            foreach ($request->earnings as $earning) {
                if ($earning['adjustment_type'] != NULL) {
                    salary_earning::create([
                        'employee_id' => $request->input('eid'),
                        'adjustment_types_id' => $earning['adjustment_type'],
                        'date' => $date,
                        'amount' => $earning['amount'],
                        'remarks' => $earning['remarks'],
                        'created_by' => $userId,
                    ]);
                }
            }
        }
        if (!empty($request->deductions)) {
            foreach ($request->deductions as $deduction) {
                if ($deduction['adjustment_type'] != NULL) {
                    salary_deduction::create([
                        'employee_id' => $request->input('eid'),
                        'adjustment_types_id' => $deduction['adjustment_type'],
                        'date' => $date,
                        'amount' => $deduction['amount'],
                        'remarks' => $deduction['remarks'],
                        'created_by' => $userId,
                    ]);
                }
            }
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

        $adjustment_type = DB::table('salary_adjustment_types')
            ->where('id', $request->input('adjustment_type'))
            ->value('adjustment_type');

        try {
            $earning = null;
            $deduction = null;
            if ($adjustment_type == 1) {
                $earning = salary_earning::where('employee_id', $id)->first();
            } else if ($adjustment_type == 0) {
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
                    'adjustment_types_id' => $request->input('adjustment_type'),
                    'date' => $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01',
                    'amount' => $request->input('amount'),
                    'remarks' => $request->input('remarks'),
                    'created_by' => $userId,
                ]);
            } else if ($deduction) {
                $deduction->update([
                    'employee_id' => $request->input('eid'),
                    'adjustment_types_id' => $request->input('adjustment_type'),
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
