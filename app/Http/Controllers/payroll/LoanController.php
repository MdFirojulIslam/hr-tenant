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
            ->join('loans', 'employees.id', '=', 'loans.employee_id')
            ->select(
                'loans.id as loan_id',
                'employees.id as eid',
                'employees.Full_Name as employee_name',
                'departments.id as deptID',
                'departments.Name as deptName',
                DB::raw('CAST(loans.principal_amounts AS UNSIGNED) as principal_amounts'),
                DB::raw('CAST(loans.tenure_months AS UNSIGNED) as tenure_months'),
                DB::raw('CAST(loans.monthly_installments AS UNSIGNED) as monthly_installments'),
                'loans.status',
                'loans.remarks',
                'loans.installment_dates',
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
        $userId = Auth::user()->EID;

        $validator = Validator::make($request->all(), [
            'employee_id'        => 'required|exists:employees,id',
            'principal_amount'       => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {

            $loan = Loan::create([
                'employee_id' => $request->employee_id,
                'principal_amounts' => $request->principal_amount,
                'tenure_months' =>  $request->tenure_month,
                'monthly_installments' => $request->installment_amount,
                'installment_dates' => $request->first_installment_date,
                'remarks' => $request->remarks ?? '',
                'created_by' => $userId,
            ]);

            if ($loan) {
                loan_repayment::create([
                    'loan_id' => $loan->id,
                    'installment_dates'  => $request->first_installment_date,
                    'installment_amounts'  => $request->installment_amount,
                    'created_by' => $userId,
                ]);
            }

            DB::commit();
            if ($loan) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully inserted'
                ]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error while inserting',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function createRepayment(Request $request)
    {
        $userId = Auth::user()->EID;

        $validator = Validator::make($request->all(), [
            'loan_id'        => 'required|exists:loans,id',
            'installment_dates'       => 'required|date',
            'installment_amounts'       => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            loan_repayment::create([
                'loan_id' => $request->loan_id,
                'installment_dates'  => $request->installment_dates,
                'installment_amounts'  => $request->installment_amounts,
                'created_by' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully inserted'
            ]);
        } catch (\Throwable $e) {
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


    public function getLoanAmount($id)
    {
        $loan = Loan::where('employee_id', $id)
            ->where('status', 'active')
            ->leftJoin('loan_repayments', 'loans.id', '=', 'loan_repayments.loan_id')
            ->select(
                'loans.id',
                'loans.principal_amounts',
                'loans.tenure_months',
                DB::raw('COALESCE(SUM(loan_repayments.installment_amounts), 0) as total_installments')
            )
            ->groupBy('loans.id', 'loans.principal_amounts', 'loans.tenure_months')
            ->first();

        if ($loan) {
            // calculate remaining loan
            $remaining = $loan->principal_amounts - $loan->total_installments;

            return response()->json([
                'success' => true,
                'principal_amount' => $loan->principal_amounts,
                'tenure_months' => $loan->tenure_months,
                'total_installments' => $loan->total_installments,
                'remaining_amount' => $remaining,
                'installment_amount' => $loan->tenure_months ? $loan->principal_amounts / $loan->tenure_months : 0,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No loan found for the given employee ID.',
        ], 404);
    }
}
