<?php

namespace App\Http\Controllers\payroll;

use App\Exports\SalarySheetExport;
use App\Http\Controllers\Controller;
use App\Models\designation;
use App\Models\employee;
use App\Models\employee_salary;
use App\Models\employee_salary_sheet;
use App\Models\loan;
use App\Models\loan_repayment;
use App\Models\salary_adjustment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeSalaryController extends Controller
{
    public function index()
    {
        $salaries = DB::table('employee_salaries as es')
            ->leftJoin('officials', 'es.employee_id', '=', 'officials.EID')
            ->leftJoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftJoin('employees as emp', 'es.employee_id', '=', 'emp.id')
            ->leftJoin('salary_earnings as se', 'es.employee_id', '=', 'se.employee_id')
            ->leftJoin('salary_deductions as sd', 'es.employee_id', '=', 'sd.employee_id')
            ->select(
                'emp.id as eid',
                'departments.id as dept_id',
                'emp.Employee_Id',
                'departments.Name as emp_dept',
                'emp.Full_Name',
                DB::raw('CAST(es.basic AS UNSIGNED) as basic'),
                DB::raw('CAST(es.total_payment AS UNSIGNED) as total_payment'),
                'es.bank_account_no',
                'es.gross_salary',
                'es.house_rent',
                'es.conveyance',
                'es.medical_allowance',
                'es.consolidated',
                'es.festival_bonus',
                'es.travel_allowance',
                'es.overtime',
                'es.daily_allowance',
                'es.mobile_bill',
                'es.other_allowance',
                'es.provident_fund',
                'es.income_tax',
                'es.revenue_stamp',
                'es.leave_without_pay',
                'es.penalty_other_deduction',
                'es.advance',
                'es.total_deduction',
                'es.net_pay as salary_net_pay',
                'es.created_by',
                'es.updated_by',
                'es.created_at',
                'es.updated_at',
                DB::raw('CAST(COALESCE(SUM(se.amount), 0) AS UNSIGNED) as earning_amount'),
                DB::raw('CAST(COALESCE(SUM(sd.amount), 0) AS UNSIGNED) as deduction_amount'),
                DB::raw('CAST(((es.total_payment + COALESCE(SUM(se.amount), 0)) - COALESCE(SUM(sd.amount), 0)) AS UNSIGNED) as net_pay')
            )
            ->groupBy(
                'emp.id',
                'emp.Employee_Id',
                'emp.Full_Name',
                'departments.id',
                'departments.Name',
                'es.basic',
                'es.total_payment',
                'es.bank_account_no',
                'es.gross_salary',
                'es.house_rent',
                'es.conveyance',
                'es.medical_allowance',
                'es.consolidated',
                'es.festival_bonus',
                'es.travel_allowance',
                'es.overtime',
                'es.daily_allowance',
                'es.mobile_bill',
                'es.other_allowance',
                'es.provident_fund',
                'es.income_tax',
                'es.revenue_stamp',
                'es.leave_without_pay',
                'es.penalty_other_deduction',
                'es.advance',
                'es.total_deduction',
                'es.net_pay',
                'es.created_by',
                'es.updated_by',
                'es.created_at',
                'es.updated_at'
            )->paginate(12);

        return response()->json($salaries);


        // $salaries = DB::table('employee_salaries as es')
        //     ->join('employees as emp', 'es.employee_id', '=', 'emp.id')
        //     ->leftJoin('salary_earnings as se', 'es.employee_id', '=', 'se.employee_id')
        //     ->leftJoin('salary_deductions as sd', 'es.employee_id', '=', 'sd.employee_id')
        //     ->select(
        //         'emp.id',
        //         'emp.Employee_Id',
        //         'emp.Full_Name',
        //         DB::raw('CAST(es.basic AS UNSIGNED) as basic'),
        //         DB::raw('CAST(es.total_payment AS UNSIGNED) as total_payment'),
        //         'es.bank_account_no',
        //         DB::raw('CAST(COALESCE(SUM(se.amount), 0) AS UNSIGNED) as earning_amount'),
        //         DB::raw('CAST(COALESCE(SUM(sd.amount), 0) AS UNSIGNED) as deduction_amount'),
        //         DB::raw('CAST(((es.total_payment + COALESCE(SUM(se.amount), 0)) - COALESCE(SUM(sd.amount), 0)) AS UNSIGNED) as net_pay')
        //     )
        //     ->groupBy('emp.id', 'emp.Employee_Id', 'emp.Full_Name', 'es.basic', 'es.total_payment', 'es.bank_account_no')
        //     ->paginate(12);
    }



    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eid'             => 'unique:employee_salaries,EID',
            // 'bank_account_no' => 'unique:employee_salaries,Bank_Account_No',
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

            // 1. Build the “base” payload
            $data = [
                'employee_id'                     => $request->input('eid'),
                'gross_salary'            => $request->input('gross_salary'),
                'basic'                   => $request->input('basic'),
                'house_rent'              => $request->input('house_rent'),
                'conveyance'              => $request->input('conveyance'),
                'medical_cllowance'       => $request->input('medical_allowance'),
                'consolidated'            => $request->input('consolidated'),
                'festival_bonus'          => $request->input('festival_bonus'),
                'travel_allowance'        => $request->input('travel_allowance'),
                'overtime'                => $request->input('overtime'),
                'daily_allowance'         => $request->input('daily_allowance'),
                'mobile_bill'             => $request->input('mobile_bill'),
                'other_allowance'         => $request->input('other_allowance'),

                'total_payment'           => $request->input('total_payment'),
                'provident_fund'          => $request->input('provident_fund'),
                'income_tax'              => $request->input('income_tax'),
                'revenue_stamp'           => $request->input('revenue_stamp'),
                'leave_without_pay'       => $request->input('leave_without_pay'),
                'penalty_other_deduction' => $request->input('penalty_other_deduction'),
                'advance'                 => $request->input('advance'),
                'total_deduction'         => $request->input('total_deduction'),
                'net_pay'                 => $request->input('net_pay'),
                'salary_type'             => $request->input('salary_type'),
                'created_by'              => $userId,
            ];

            // 2. Conditionally add the bank account number
            if ($request->input('salary_type') === 'bank') {
                $data['bank_account_no'] = $request->input('bank_account_no');
            }

            // 3. Persist to DB
            employee_salary::create($data);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Successfully inserted'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            // Log the error if you like: Log::error($e);
            return response()->json([
                'success' => false,
                'message' => 'Error while inserting',
                'error'   => $e->getMessage(),   // optional, hide in production
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            // 'bank_account_no' => 'unique:employee_salaries,Bank_Account_No',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        $userId = Auth::user()->EID;
        $employee_salary = employee_salary::find($id);

        $employee_salary->update([
            'employee_id' => $request->input('eid'),
            'gross_salary' => $request->input('gross_salary'),
            'basic' => $request->input('basic'),
            'house_rent' => $request->input('house_rent'),
            'conveyance' => $request->input('conveyance'),
            'medical_allowance' => $request->input('medical_allowance'),
            'consolidated' => $request->input('consolidated'),
            'festival_bonus' => $request->input('festival_bonus'),
            'travel_allowance' => $request->input('travel_allowance'),
            'overtime' => $request->input('overtime'),
            'daily_allowance' => $request->input('daily_allowance'),
            'mobile_bill' => $request->input('mobile_bill'),
            'other_allowance' => $request->input('other_allowance'),

            'total_payment' => $request->input('total_payment'),
            'provident_fund' => $request->input('provident_fund'),
            'income_tax' => $request->input('income_tax'),
            'revenue_stamp' => $request->input('revenue_stamp'),
            'leave_without_pay' => $request->input('leave_without_pay'),
            'penalty_other_deduction' => $request->input('penalty_other_deduction'),
            'advance' => $request->input('advance'),
            'total_deduction' => $request->input('total_deduction'),
            'net_pay' => $request->input('net_pay'),
            'salary_type' => $request->input('salary_type'),
            'bank_account_no' => $request->input('bank_account_no'),
            'updated_by' => $userId,
        ]);

        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully updated',
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


    public function salarySearch(Request $request)
    {
        $employeeName = $request->input('employeeName');
        $employeeID = $request->input('employeeID');
        $bank_account_no = $request->input('bank_account_no');

        $salaries = DB::table('employee_salaries as es')
            ->join('employees as emp', 'es.employee_id', '=', 'emp.id')

            ->leftJoin('salary_earnings as se', 'es.employee_id', '=', 'se.employee_id')
            ->leftJoin('salary_deductions as sd', 'es.employee_id', '=', 'sd.employee_id')
            ->select(
                'emp.Employee_Id',
                'emp.Full_Name',
                'es.basic',
                'es.total_payment',
                'es.bank_account_no',
                DB::raw('COALESCE(SUM(se.amount), 0) as earning_amount'),
                DB::raw('COALESCE(SUM(sd.amount), 0) as deduction_amount'),
                DB::raw('((es.total_payment + COALESCE(SUM(se.amount), 0)) - COALESCE(SUM(sd.amount), 0)) as net_pay')
            )
            ->when($employeeName, function ($q) use ($employeeName) {
                $q->where('emp.Full_Name', 'LIKE', "%{$employeeName}%");
            })
            ->when($employeeID, function ($q) use ($employeeID) {
                $q->where('emp.Employee_Id', 'LIKE', "%{$employeeID}%");
            })
            ->when($bank_account_no, function ($q) use ($bank_account_no) {
                $q->where('es.bank_account_no', 'LIKE', "%{$bank_account_no}%");
            })
            ->groupBy('emp.id', 'emp.Employee_Id', 'emp.Full_Name', 'es.basic', 'es.total_payment', 'es.bank_account_no')
            ->paginate(12);

        return response()->json($salaries);
    }


    //Salary sheet Generatate without Loan Amount
    // public function generateSalary(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $userId = Auth::user()->employee_id;
    //         $month = $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01';

    //         $employee_salaries = employee_salary::all();

    //         foreach ($employee_salaries as $salary) {
    //             // Fetch all salary adjustments for this employee for the month
    //             $adjustments = \App\Models\salary_adjustment::where('EID', $salary->EID)
    //                 ->whereMonth('Month', $request->month)
    //                 ->whereYear('Month', $request->year)
    //                 ->get();

    //             // Initialize adjustment-based values
    //             $adjustmentAdvance = 0;
    //             $adjustmentDeduction = 0;

    //             foreach ($adjustments as $adjustment) {
    //                 if (strtolower($adjustment->Type) === 'advance') {
    //                     $adjustmentAdvance += $adjustment->Amount;
    //                 } elseif (strtolower($adjustment->Type) === 'deduction') {
    //                     $adjustmentDeduction += $adjustment->Amount;
    //                 }
    //             }

    //             // Add adjustments to original salary values
    //             $finalAdvance = $salary->Advance + $adjustmentAdvance;
    //             $finalOtherDeduction = $salary->Other_Deduction + $adjustmentDeduction;

    //             $loanRepayments = loan_repayment::with('loan')      // eager‑load to close later
    //                 ->whereHas('loan', function ($q) use ($salary) {
    //                     $q->where('EID', $salary->EID)
    //                         ->where('Status', 'active');
    //                 })
    //                 ->whereMonth('Due_On', $request->month)
    //                 ->whereYear('Due_On',  $request->year)
    //                 ->where('Is_Paid', false)
    //                 ->get();

    //                 dd($loanRepayments);

    //             $loanPayable = $loanRepayments->sum('Amount');

    //             $totalDeduction =
    //                 $salary->Provident_Fund +
    //                 $salary->Income_Tax +
    //                 $salary->Revenue_Stamp +
    //                 $salary->Leave_Without_Pay +
    //                 $salary->Penalty_Other_Deduction +
    //                 $finalOtherDeduction +
    //                 $finalAdvance +
    //                 $loanPayable;

    //             $netPay = $salary->Total_Payment - $totalDeduction;

    //             // Create or update salary sheet
    //             employee_salary_sheet::updateOrCreate(
    //                 ['EID' => $salary->EID, 'Month' => $month],
    //                 [
    //                     'Gross_Salary' => $salary->Gross_Salary,
    //                     'Basic' => $salary->Basic,
    //                     'House_Rent' => $salary->House_Rent,
    //                     'Conveyance' => $salary->Conveyance,
    //                     'Medical_Allowance' => $salary->Medical_Allowance,
    //                     'Consolidated' => $salary->Consolidated,
    //                     'Festival_Bonus' => $salary->Festival_Bonus,
    //                     'Travel_Allowance' => $salary->Travel_Allowance,
    //                     'Overtime' => $salary->Overtime,
    //                     'Daily_Allowance' => $salary->Daily_Allowance,
    //                     'Mobile_Bill' => $salary->Mobile_Bill,
    //                     'Other_Allowance' => $salary->Other_Allowance,
    //                     'Total_Payment' => $salary->Total_Payment,

    //                     'Provident_Fund' => $salary->Provident_Fund,
    //                     'Income_Tax' => $salary->Income_Tax,
    //                     'Revenue_Stamp' => $salary->Revenue_Stamp,
    //                     'Leave_Without_Pay' => $salary->Leave_Without_Pay,
    //                     'Penalty_Other_Deduction' => $salary->Penalty_Other_Deduction,
    //                     'Other_Deduction' => $salary->Other_Deduction,
    //                     'Advance' => $finalAdvance,
    //                     'Loan_Payable' => $loanPayable,
    //                     'Other_Deduction' => $finalAdvance,
    //                     'Total_Deduction' => $totalDeduction,
    //                     'Net_Pay' => $netPay,
    //                     'Bank_Account_No' => $salary->Bank_Account_No,
    //                     'created_by' => $userId,
    //                 ]
    //             );
    //             foreach ($loanRepayments as $repayment) {
    //                 $repayment->update([
    //                     'Is_Paid' => true,
    //                     'Paid_On' => Carbon::now(),
    //                 ]);
    //             }
    //             foreach ($loanRepayments->pluck('loan')->unique('id') as $loan) {
    //             if (
    //                 $loan->repayments()
    //                      ->where('Is_Paid', false)
    //                      ->count() === 0
    //             ) {
    //                 $loan->update(['Status' => 'closed']);
    //             }
    //         }

    //         }


    //         DB::commit();
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Salary sheets created or updated successfully with adjustments.',
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error occurred: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }


    //Salary sheet Generatate with Loan Amount
    public function generateSalary(Request $request)
    {
        DB::beginTransaction();

        $user_id = Auth::user()->EID;
        $monthString = $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01';
        $employeeSalaries = employee_salary::all();



        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Salary sheets generated with loan deductions.',
        ]);

        // try {
        //     $userId = Auth::user()->EID;
        //     $monthString = $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01';

        //     $employeeSalaries = employee_salary::all();

        //     foreach ($employeeSalaries as $salary) {

        //         /* ----------------------------------------------------
        //          * 1.  AD‑HOC ADJUSTMENTS (advance / deduction)
        //          * -------------------------------------------------- */
        //         $adjustments = salary_adjustment::where('EID', $salary->EID)
        //             ->whereMonth('Month', $request->month)
        //             ->whereYear('Month', $request->year)
        //             ->get();

        //         $adjustmentAdvance   = 0;
        //         $adjustmentDeduction = 0;

        //         foreach ($adjustments as $adj) {
        //             if (strtolower($adj->type) === 'advance') {
        //                 $adjustmentAdvance   += $adj->amount;
        //             } elseif (strtolower($adj->type) === 'deduction') {
        //                 $adjustmentDeduction += $adj->amount;
        //             }
        //         }

        //         $finalAdvance        = $salary->advance        + $adjustmentAdvance;
        //         $finalOtherDeduction = $salary->other_deduction + $adjustmentDeduction;

        //         /* ----------------------------------------------------
        //          * 2.  LOAN PAYABLE FOR THIS MONTH
        //          * (all unpaid instalments whose due_on falls in
        //          * the target month & year) 01619522591
        //          * -------------------------------------------------- */
        //         $loanRepayments = loan_repayment::with('loan')
        //             ->whereHas('loan', function ($q) use ($salary) {
        //                 $q->where('employee_id', $salary->employee_id)
        //                     // make the status check case‑insensitive
        //                     ->whereRaw('LOWER(Status) = ?', ['active']);
        //             })
        //             // stay on the target month & year
        //             ->whereYear('due_on', $request->year)
        //             ->whereMonth('due_on', $request->month)
        //             // catch rows where Is_Paid is either FALSE (0) or NULL
        //             ->where(function ($q) {
        //                 $q->where('is_paid', false)
        //                     ->orWhereNull('is_paid');
        //             })
        //             ->get();

        //         $loanPayable = (float) $loanRepayments->sum('amount');

        //         /* ----------------------------------------------------
        //          * 3.  TOTAL DEDUCTION / NET PAY
        //          * -------------------------------------------------- */
        //         $totalDeduction =
        //             $salary->provident_fund         +
        //             $salary->income_tax             +
        //             $salary->revenue_stamp          +
        //             $salary->leave_without_pay      +
        //             $salary->penalty_other_deduction +
        //             $finalOtherDeduction            +
        //             $finalAdvance                   +
        //             $loanPayable;

        //         $netPay = $salary->total_payment - $totalDeduction;

        //         /* ----------------------------------------------------
        //          * 4.  UPSERT SALARY SHEET ROW
        //          * -------------------------------------------------- */
        //         employee_salary_sheet::updateOrCreate(
        //             ['employee_id' => $salary->employee_id, 'month' => $monthString],
        //             [
        //                 /* ------- earnings ------- */
        //                 'gross_salary'      => $salary->gross_salary,
        //                 'basic'             => $salary->basic,
        //                 'house_rent'        => $salary->house_rent,
        //                 'conveyance'        => $salary->conveyance,
        //                 'medical_allowance' => $salary->medical_allowance,
        //                 'consolidated'      => $salary->consolidated,
        //                 'festival_bonus'    => $salary->festival_bonus,
        //                 'travel_allowance'  => $salary->travel_allowance,
        //                 'overtime'          => $salary->overtime,
        //                 'daily_allowance'   => $salary->daily_allowance,
        //                 'mobile_bill'       => $salary->mobile_bill,
        //                 'other_allowance'   => $salary->other_allowance,
        //                 'total_payment'     => $salary->total_payment,

        //                 /* ------- deductions ------- */
        //                 'provident_fund'           => $salary->provident_fund,
        //                 'income_tax'               => $salary->income_tax,
        //                 'revenue_stamp'            => $salary->revenue_stamp,
        //                 'leave_without_pay'        => $salary->leave_without_pay,
        //                 'penalty_other_deduction'  => $salary->penalty_other_deduction,
        //                 'other_deduction'          => $finalOtherDeduction,
        //                 'advance'                  => $finalAdvance,
        //                 'loan_payable'             => $loanPayable,
        //                 'total_deduction'          => $totalDeduction,
        //                 'net_pay'                  => $netPay,

        //                 /* misc */
        //                 'bank_account_no' => $salary->bank_account_no,
        //                 'created_by'      => $userId,
        //             ]
        //         );

        //         /* ----------------------------------------------------
        //          * 5.  FLAG THE INSTALMENTS AS PAID
        //          * -------------------------------------------------- */
        //         foreach ($loanRepayments as $repayment) {
        //             $repayment->update([
        //                 'is_paid' => true,
        //                 'paid_on' => Carbon::now(),
        //             ]);
        //         }

        //         // Fetch all active loans for the current employee to check for closure
        //         $activeLoans = Loan::where('employee_id', $salary->employee_id)
        //             ->where('status', 'active')
        //             ->get();

        //         foreach ($activeLoans as $loan) {
        //             // Check if this specific loan has any unpaid repayments
        //             if (
        //                 $loan->repayments()
        //                 ->where('is_paid', false)
        //                 ->count() === 0
        //             ) {
        //                 $loan->update(['status' => 'closed']);
        //             }
        //         }
        //     }

        //     DB::commit();

        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Salary sheets generated with loan deductions.',
        //     ]);
        // } catch (\Throwable $e) {

        //     DB::rollBack();

        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Error occurred: ' . $e->getMessage(),
        //     ], 500);
        // }
    }

    //compress

    public function salarySheet(Request $request)
    {


        // $salaries = employee_salary_sheet::with('employee')->whereYear('date', $year)->whereMonth('date', $month)->get();

        // return response()->json($salaries);
        $year = $request->year;
        $month = $request->month;




        $salaries = DB::table('employees')
            ->join('employee_salary_sheets', 'employees.id', '=', 'employee_salary_sheets.employee_id')
            ->leftJoin('salary_earnings', 'employees.id', '=', 'salary_earnings.employee_id')
            ->leftJoin('salary_deductions', 'employees.id', '=', 'salary_deductions.employee_id')
            ->whereYear('employee_salary_sheets.salary_date', $year)
            ->whereMonth('employee_salary_sheets.salary_date', $month)
            ->select(
                'employees.id',
                'employees.Employee_Id',
                'employees.Full_Name',
                'employee_salary_sheets.gross_salary',
                'employee_salary_sheets.total_payment',
                DB::raw('COALESCE(SUM(salary_earnings.amount), 0) as earning_amount'),
                DB::raw('COALESCE(SUM(salary_deductions.amount), 0) as deduction_amount'),
                DB::raw('((employee_salary_sheets.total_payment + COALESCE(SUM(salary_earnings.amount), 0)) - COALESCE(SUM(salary_deductions.amount), 0)) as net_pay')
            )
            ->groupBy(
                'employees.id',
                'employees.Employee_Id',
                'employees.Full_Name',
                'employee_salary_sheets.gross_salary',
                'employee_salary_sheets.total_payment',
                'employee_salary_sheets.net_pay'
            )
            ->get();


        return response()->json($salaries);
    }


    public function exportSalarySheet(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        return Excel::download(new SalarySheetExport($year, $month), "salary_sheet.xlsx");
    }

    //added by firoj
    public function salary_profile(Request $request, $id)
    {

        // $salaries = employee_salary::with([
        //     'employee:id,Full_Name,Employee_Id',
        //     'employee.official:id,EID,Department_Id',
        //     'employee.department:id,Name',
        //     'employee.department.designation:id,Department_Id,Name',
        //     'employee.emp_img:id,EID,img_url',
        //     'employee.salary_earning:employee_id,amount',
        //     'employee.salary_deduction:employee_id,amount'
        // ])->whereHas('employee:id',$id)
        // ->select('id','Full_Name', 'img_url', 'Employee_Id',);

        //  return response()->json($salaries);

        $salaries = DB::table('employee_salaries as es')
            ->join('employees as emp', 'es.employee_id', '=', 'emp.id')
            ->join('officials', 'emp.id', '=', 'officials.EID')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'departments.id', '=', 'designations.Department_Id')
            ->leftJoin('emp_imgs as employee_images', 'employee_images.EID', '=', 'emp.id')
            ->leftJoin('salary_earnings as se', 'es.employee_id', '=', 'se.employee_id')
            ->leftJoin('salary_deductions as sd', 'es.employee_id', '=', 'sd.employee_id')
            ->select(
                'emp.Employee_Id',
                'emp.Full_Name',
                'employee_images.img_url',
                'departments.Name as departmentName',
                'designations.Name as designationName',
                DB::raw('CAST(es.basic AS UNSIGNED) as basic'),
                DB::raw('CAST(es.total_payment AS UNSIGNED) as total_payment'),
                'es.bank_account_no',
                DB::raw('CAST(COALESCE(SUM(se.amount), 0) AS UNSIGNED) as earning_amount'),
                DB::raw('CAST(COALESCE(SUM(sd.amount), 0) AS UNSIGNED) as deduction_amount'),
                DB::raw('CAST(((es.total_payment + COALESCE(SUM(se.amount), 0)) - COALESCE(SUM(sd.amount), 0)) AS UNSIGNED) as net_pay')
            )
            ->where('emp.id', $id)
            ->groupBy(
                'emp.id',
                'emp.Employee_Id',
                'emp.Full_Name',
                'es.basic',
                'es.total_payment',
                'es.bank_account_no',
                'employee_images.img_url',
                'departments.Name',
                'designations.Name'
            )
            ->first();

        return response()->json($salaries);
    }

    public function getEmployeeInfo($id)
    {
        dd($id);
        $employee = employee::select('employees.id', 'employees.Full_Name', 'emp_imgs.img_url', 'designations.Name')
            ->leftJoin('emp_imgs as img_url', 'employees.id', '=', 'emp_imgs.EID')
            ->leftJoin('officials', 'employees.id', '=', 'officials.EID')
            ->leftJoin('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('id', $id)
            ->first();

        if ($employee) {
            return response()->json($employee);
        } else {
            return response()->json(['message' => 'Employee not found'], 404);
        }
    }
}
