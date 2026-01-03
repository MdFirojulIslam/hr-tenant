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
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->join('loans', 'employees.id', '=', 'loans.employee_id')
            ->leftJoin('loan_repayments', 'loans.id', '=', 'loan_repayments.loan_id')
            ->select(
                'loans.id as loan_id',
                'employees.id as eid',
                'employees.Full_Name as employee_name',
                'departments.id as deptID',
                'departments.Name as deptName',
                'designations.Name as designation_name',
                DB::raw('CAST(loans.principal_amounts AS UNSIGNED) as principal_amounts'),
                DB::raw('CAST(loans.tenure_months AS UNSIGNED) as tenure_months'),
                DB::raw('CAST(loans.monthly_installments AS UNSIGNED) as monthly_installments'),
                DB::raw("COALESCE(SUM(
            CASE WHEN loan_repayments.paid_status = 'paid'
            THEN loan_repayments.installment_amounts
            ELSE 0 END
        ), 0) AS total_paid"),
                'loans.status',
                'loans.remarks',
                'loans.installment_dates'
            )
            ->groupBy(
                'loans.id',
                'employees.id',
                'employees.Full_Name',
                'departments.id',
                'departments.Name',
                'designations.Name',
                'loans.principal_amounts',
                'loans.tenure_months',
                'loans.monthly_installments',
                'loans.status',
                'loans.remarks',
                'loans.installment_dates'
            )
            ->paginate(12);

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
        $data = $request->all();
        $userId = Auth::user()->EID;

        // Validation for array input
        $validator = Validator::make($data, [
            '*.employee_id'         => 'required|integer|exists:loans,employee_id',
            '*.installment_date'    => 'required|date',
            '*.installment_amount'  => 'required|numeric',
            '*.installment_status'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($data as $row) {
                // Get active loan for employee
                $getLoan = loan::where('employee_id', $row['employee_id'])
                    ->where('status', 'active')
                    ->first();

                if (!$getLoan) continue;

                // Update first unpaid repayment if exists
                $unpaidRepayment = loan_repayment::where('loan_id', $getLoan->id)
                    ->where('paid_status', 'unpaid')
                    ->first();

                if ($unpaidRepayment) {
                    $unpaidRepayment->update([
                        'installment_amounts' => $row['installment_amount'],
                        'paid_status'         => $row['installment_status'],
                        'paid_on_dates'       => $row['installment_date'],
                        'created_by'          => $userId,
                    ]);
                }

                // Sum total paid installments
                $totalPaid = loan_repayment::where('loan_id', $getLoan->id)
                    ->where('paid_status', 'paid')
                    ->sum('installment_amounts');

                // Close loan if fully paid
                if ($totalPaid >= $getLoan->principal_amounts) {
                    $getLoan->update(['status' => 'closed']);
                }

                // Create new repayment row if still unpaid installments remain
                if ($totalPaid < $getLoan->principal_amounts) {
                    loan_repayment::create([
                        'loan_id'             => $getLoan->id,
                        'installment_dates'   => $getLoan->installment_dates,
                        'installment_amounts' => $getLoan->monthly_installments,
                        'paid_status'         => 'unpaid',
                        'created_by'          => $userId,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Installments successfully processed'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error while inserting',
                'error'   => $e->getMessage(),
            ], 500);
        }

        // dd($request);
        // $data = $request->all();

        // $userId = Auth::user()->EID;

        // $validator = Validator::make($data, [
        //     // 'loan_id'        => 'required|exists:loans,id',
        //     'installment_date'  => 'required|date',
        //     'installment_amount' => 'required|numeric',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => $validator->errors()
        //     ], 422);
        // }



        // DB::beginTransaction();

        // try {
        //     foreach ($data as $row) {
        //         $getLoanID = loan::where('employee_id', $request->employee_id)
        //             ->where('status', 'active')
        //             ->first();
        //     }
        //     //for all credentials from loan table like $getLoanID->id = loan_id



        //     //for all credentials from loan repayment table like $loanRepaymentID->loan_id = loan_id
        //     $loanRepaymentID = loan_repayment::where('loan_id', $getLoanID->id)
        //         ->first();


        //     //for collect uppaid installment to update installment_amount, paid_status and paid_on_date
        //     //loan_repayment table id or loan_id
        //     $updateConfirm = loan_repayment::where('loan_id', $loanRepaymentID->loan_id)
        //         ->where('paid_status', 'unpaid')
        //         ->first();
        //     //get loan id from loan repayment table
        //     if ($updateConfirm) {
        //         $updateConfirm->update([
        //             'installment_amounts' => $request->installment_amount,
        //             'paid_status'         => $request->installment_status,
        //             'paid_on_dates'       => $request->installment_date,
        //             'created_by'          => $userId,
        //         ]);
        //     }
        //     $checkStatus = DB::table('loan_repayments')
        //         ->where('loan_id', $loanRepaymentID->loan_id)
        //         ->where('paid_status', 'paid')
        //         ->sum('installment_amounts');

        //     //total installment amount from loan repayment table

        //     if ($checkStatus && $checkStatus >= $getLoanID->principal_amounts) {
        //         loan::where('id', $getLoanID->id)->update([
        //             'status' => 'closed',
        //         ]);
        //     }

        //     //newly created a repayment row if the paid installment is less than principal amount
        //     if ($checkStatus && $checkStatus < $getLoanID->principal_amounts) {
        //         if ($updateConfirm) {
        //             loan_repayment::create([
        //                 'loan_id' => $getLoanID->id,
        //                 'installment_dates'  => $getLoanID->installment_dates,
        //                 'installment_amounts'  => $getLoanID->monthly_installments,
        //                 'created_by' => $userId,
        //             ]);
        //         }
        //     } else {
        //         DB::commit();
        //         return response()->json([
        //             'success' => true,
        //             'message' => 'All installments are paid already.'
        //         ]);
        //     }
        //     DB::commit();
        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Installment Successfully Inserted'
        //     ]);
        // } catch (\Throwable $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Error while inserting',
        //         'error'   => $e->getMessage(),
        //     ], 500);
        // }
    }


    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $userId = Auth::user()->EID;

            $loan = loan::find($id);
            $loan_repayments = loan_repayment::where('loan_id', $id)->first();

            if (!$loan) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Loan record not found.',
                ], 404);
            }
            $remarks = $request->input('remarks');
            $remarks = ($remarks === '0' || $remarks === 0 || $remarks === '') ? null : $remarks;
            $loan->update([
                'employee_id'           => $request->employee_id,
                'principal_amounts'     => $request->principal_amount,
                'tenure_months'         => $request->tenure_month,
                'monthly_installments'  => $request->installment_amount,
                'installment_dates'     => $request->first_installment_date,
                'remarks'               => $remarks,
                'updated_by'            => $userId,
            ]);
            if ($loan_repayments) {
                $loan_repayments->update([
                    'installment_dates'    => $request->first_installment_date,
                    'installment_amounts'  => $request->installment_amount,
                    'updated_by'           => $userId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully updated',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
                DB::raw("COALESCE(SUM(CASE WHEN loan_repayments.paid_status = 'paid' THEN loan_repayments.installment_amounts ELSE 0 END), 0) AS total_installments")
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

    public function repaymentHistory($id)
    {
        $repayments = loan_repayment::where('loan_id', $id)->get();
        return response()->json($repayments);
    }

    public function repaymentInfo()
    {
        $repayments = DB::table('loans')
            ->join('employees', 'employees.id', '=', 'loans.employee_id')
            ->join('officials', 'officials.EID', '=', 'loans.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->join('loan_repayments', 'loan_repayments.loan_id', '=', 'loans.id')
            ->select(
                'employees.id as employee_id',
                'employees.Employee_Id as official_id',
                'employees.Full_Name as employee_name',
                'departments.Name as department_name',
                DB::raw('CAST(loans.principal_amounts AS UNSIGNED) as principal_amount'),
                DB::raw('CAST(loans.tenure_months AS UNSIGNED) as tenure_months'),
                DB::raw('CAST(loans.monthly_installments AS UNSIGNED) as monthly_installments'),
                DB::raw("COALESCE(SUM(
                CASE WHEN loan_repayments.paid_status = 'paid'
                THEN loan_repayments.installment_amounts
                ELSE 0 END
            ), 0) AS paid_installments")
            )
            ->where('loans.status', 'active')
            ->groupBy(
                'employees.id',
                'employees.Employee_Id',
                'loans.id',
                'employees.Full_Name',
                'departments.Name',
                'loans.principal_amounts',
                'loans.tenure_months',
                'loans.monthly_installments'
            )
            ->get();

        return response()->json($repayments);
    }

    public function deleteLoan($id)
    {
        $loan = Loan::with('repayments')->find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan record not found.',
            ], 404);
        }

        $loan->repayments()->delete();
        $loan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Loan record deleted successfully.',
        ]);
    }
}
