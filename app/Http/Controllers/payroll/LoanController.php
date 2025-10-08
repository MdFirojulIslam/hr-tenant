<?php

namespace App\Http\Controllers\payroll;

use App\Http\Controllers\Controller;
use App\Models\employee;
use App\Models\loan;
use App\Models\loan_repayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{

    public function index()
    {
        $loans = DB::table('employees')
        ->join('officials', 'employees.id', '=', 'officials.EID')
        ->join('departments', 'officials.Department_Id', '=', 'departments.id')
        ->join('loans','employees.id', '=', 'loans.employee_id')
        ->select(
            'loans.id as loanID',
            'employees.id as eid',
            'employees.Full_Name as empName',
            'departments.id as deptID',
            'departments.Name as deptName',
            DB::raw('CAST(loans.principal AS UNSIGNED) as principal'),
            DB::raw('CAST(loans.tenure_months AS UNSIGNED) as tenure_months'), 
            DB::raw('CAST(loans.monthly_installment AS UNSIGNED) as monthly_installment'),
            'loans.status',
            'loans.remarks',
            'loans.first_due_on'
        )->paginate(12);
        return response()->json($loans); 
    }

    // public function index()
    // {
    //     $loans = loan::with('employee', 'employee.official.department')->paginate(12);
    //     return response()->json($loans);
    // }

    public function create(Request $request)
    { 


        $validator = Validator::make($request->all(), [
            'employee'        => 'required|exists:employees,id',
            'principal'       => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $userId = Auth::user()->EID;
            $principal = $request->principal;
            $tenure = (int) $request->tenure;
            $installment = round($principal / $tenure, 2);
            $firstDue = Carbon::parse($request->first_due_on)->startOfDay();

            $loan = Loan::create([
                'employee_id' => $request->employee,
                'principal' => $principal,
                'tenure_months' => $tenure,
                'monthly_installment' => $installment,
                'first_due_on' => $firstDue,
                'remarks' => $request->remarks || '',
                'created_by' => $userId,
            ]);

            for ($i = 0; $i < $tenure; $i++) {
                loan_repayment::create([
                    'loan_id' => $loan->id,
                    'due_on'  => $firstDue->copy()->addMonths($i),
                    'amount'  => $installment,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Successfully inserted'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error while inserting',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        $userId = Auth::user()->EID;  

        try {
            $loan = loan::where('id', $id)->first();

            if (!$loan) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Salary adjustment record not found.',
                ], 404);
            }

            $loan->update([
                'employee_id' => $request->employee,
                'principal' => $request->principal,
                'tenure_months' => $request->tenure,
                'first_due_on' => $request->first_due_on,
                'remarks' => $request->remarks || '',
                'created_by' => $userId,
            ]);

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

    public function getLoanRepayment($id)
    {
        $loanRepayment = loan_repayment::where('loan_id', $id)->get();
        return response()->json($loanRepayment);
    }


    public function updateRepaymentDates(Request $request)
    {
        foreach ($request->all() as $repayment) {
            DB::table('loan_repayments')
                ->where('id', $repayment['id'])
                ->update(['due_on' => $repayment['due_on']]);
        }

        return response()->json(['success' => true]);
    }
}
