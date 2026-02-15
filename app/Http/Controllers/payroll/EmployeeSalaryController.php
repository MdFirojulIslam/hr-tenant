<?php

namespace App\Http\Controllers\payroll;

use App\Exports\SalarySheetExport;
use App\Http\Controllers\Controller;
use App\Models\employee;
use App\Models\employee_salary_sheet;
use App\Models\salaries;
use Carbon\Carbon;
use App\Models\salary_sheet;
use App\Models\department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\PhpWord;
use App\Exports\SalaryDisbursementExport;
use App\Models\official;
use App\Models\salary_disbursement;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeSalaryController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('salaries')
            ->join('employees', 'employees.id', '=', 'salaries.employee_id')
            ->join('officials', 'officials.EID', '=', 'salaries.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->select(
                'salaries.*',
                'employees.Employee_Id as official_id',
                'employees.Full_Name as full_name',
                'departments.Name as department_name',
                'departments.id as department_id',
                'designations.Name as designation_name',
                DB::raw('
                salaries.consolidated +
                salaries.festival_bonus +
                salaries.daily_allowance +
                salaries.travel_allowance +
                salaries.mobile_bill +
                salaries.other_allowance AS total_earning
            '),
                DB::raw('
                salaries.provident_fund +
                salaries.income_tax +
                salaries.revenue_stamp AS total_deduction
            ')
            );

        /* 🔍 Search by Account No or Official ID */
        if ($request->filled('query')) {
            $search = $request->query('query');
            $query->where(function ($q) use ($search) {
                $q->where('salaries.bank_account_no', 'like', "%{$search}%")
                    ->orWhere('employees.Employee_Id', 'like', "%{$search}%");
            });
        }

        /* 🏢 Department filter */
        if ($request->filled('department_id')) {
            $query->where('departments.id', $request->department_id);
        }

        /* 👤 Employee filter */
        if ($request->filled('employee_id')) {
            $query->where('employees.id', $request->employee_id);
        }

        $salaries = $query->paginate(16);

        return response()->json($salaries);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eid'             => 'unique:salaries,employee_id',
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


            $data = [
                'employee_id'             => $request->input('eid'),
                'gross_salary'            => $request->input('gross_salary'),
                'basic'                   => $request->input('basic'),
                'house_rent'              => $request->input('house_rent'),
                'conveyance'              => $request->input('conveyance'),
                'medical_allowance'       => $request->input('medical_allowance'),
                'consolidated'            => $request->input('consolidated'),
                'festival_bonus'          => $request->input('festival_bonus'),
                'travel_allowance'        => $request->input('travel_allowance'),
                // 'overtime'                => $request->input('overtime'),
                'daily_allowance'         => $request->input('daily_allowance'),
                'mobile_bill'             => $request->input('mobile_bill'),
                'other_allowance'         => $request->input('other_allowance'),

                'total_payment'           => $request->input('total_payment'),
                'provident_fund'          => $request->input('provident_fund'),
                'income_tax'              => $request->input('income_tax'),
                'revenue_stamp'           => $request->input('revenue_stamp'),
                'net_pay'                 => $request->input('net_pay'),
                'salary_type'             => $request->input('salary_type'),
                'created_by'              => $userId,
            ];

            // 2. Conditionally add the bank account number
            if ($request->input('salary_type') === 'bank') {
                $data['bank_account_no'] = $request->input('bank_account_no');
            }

            // 3. Persist to DB
            salaries::create($data);

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

        $employee_salary = salaries::where('employee_id', $id)->first();


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
        $query = $request->input('query');

        $salariesQuery = DB::table('salaries')
            ->join('employees', 'employees.id', '=', 'salaries.employee_id')
            ->join('officials', 'officials.EID', '=', 'salaries.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->select(
                'salaries.*',
                DB::raw('salaries.employee_id'),
                'employees.Employee_Id as official_id',
                'employees.Full_Name as full_name',
                'departments.Name as department_name',
                'departments.id as department_id',
                'designations.Name as designation_name',
                DB::raw('salaries.consolidated + salaries.festival_bonus + salaries.travel_allowance + salaries.mobile_bill + salaries.other_allowance AS total_earning'),
                DB::raw('salaries.provident_fund + salaries.income_tax + salaries.revenue_stamp AS total_deduction')
            );

        if ($query) {
            $salariesQuery->where(function ($q) use ($query) {
                $q->where('salaries.bank_account_no', 'like', '%' . $query . '%')
                    ->orWhere('employees.Employee_Id', 'like', '%' . $query . '%');
            });
        }

        $salaries = $salariesQuery->paginate(12);

        return response()->json($salaries);
    }




    //Salary sheet Generatate with Loan Amount
    public function generateSalary(Request $request)
    {
        DB::beginTransaction();

        $user_id = Auth::user()->EID;

        $monthString = $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01';
        $month_start = Carbon::parse($monthString)->startOfMonth()->toDateString();
        $month_end   = Carbon::parse($monthString)->endOfMonth()->toDateString();

        // Earnings adjustment
        $earnings = DB::table('salary_earnings')
            ->select('employee_id', DB::raw('SUM(amount) as total_earning'))
            ->whereBetween('date', [$month_start, $month_end])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        // Deduction adjustment
        $deductions = DB::table('salary_deductions')
            ->select('employee_id', DB::raw('SUM(amount) as total_deduction'))
            ->whereBetween('date', [$month_start, $month_end])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        // Loan repayments
        $loans = DB::table('loans')
            ->join('loan_repayments', 'loan_repayments.loan_id', '=', 'loans.id')
            ->select('loans.employee_id', DB::raw('SUM(loan_repayments.installment_amounts) as total_loan'))
            ->where('loans.status', 'active')
            ->where('loan_repayments.paid_status', 'paid')
            ->whereBetween('loan_repayments.paid_on_dates', [$month_start, $month_end])
            ->groupBy('loans.employee_id')
            ->get()
            ->keyBy('employee_id');

        // All salaries
        $salaries = DB::table('salaries')
            ->join('employees', 'employees.id', '=', 'salaries.employee_id')
            ->join('officials', 'officials.EID', '=', 'salaries.employee_id')
            ->select(
                'employees.id as employee_id',
                'employees.Employee_Id as official_id',
                'salaries.*'
            )->where('officials.Status', '=', 'N')
            ->whereDate('officials.DOJ', '<=', $month_end)
            ->get();

        $maxMonthlySalaryId = salary_sheet::max('monthly_salary_id') ?? 0;

        if ($maxMonthlySalaryId == 0) {
            $monthly_salary_id = 1;
        } else {
            $monthly_salary_id = $maxMonthlySalaryId + 1;
        }

        foreach ($salaries as $salary) {

            $earning = $earnings[$salary->employee_id]->total_earning ?? 0;
            $deduction = $deductions[$salary->employee_id]->total_deduction ?? 0;
            $loan = $loans[$salary->employee_id]->total_loan ?? 0;

            $net_salary = ($salary->total_payment + $earning)
                - ($salary->provident_fund + $salary->income_tax + $salary->revenue_stamp + $deduction + $loan);
            $total_deduction = ($salary->provident_fund + $salary->income_tax + $salary->revenue_stamp + $deduction);
            $total_payment = $earning + ($salary->gross_salary + $salary->consolidated + $salary->festival_bonus + $salary->travel_allowance
                + $salary->daily_allowance + $salary->mobile_bill + $salary->other_allowance);

            salary_sheet::updateOrCreate(
                [
                    'employee_id' => $salary->employee_id,
                    'salary_date' => $monthString
                ],
                [
                    'monthly_salary_id' => $monthly_salary_id,
                    //gross_salary	basic_salary	house_rent	conveyance
                    'gross_salary' => $salary->gross_salary,
                    'basic_salary' => $salary->basic,
                    'house_rent' => $salary->house_rent,
                    'conveyance' => $salary->conveyance,
                    'medical_allowance' => $salary->medical_allowance,
                    //medical_allowance	consolidated	festival_bonus	travel_allowance

                    'consolidated' => $salary->consolidated,
                    'festival_bonus' => $salary->festival_bonus,
                    'travel_allowance' => $salary->travel_allowance,
                    //overtime
                    //daily_allowance	mobile_bill	other_allowance
                    //earning_adjustment	total_payment	provident_fund	income_tax	revenue_stamp

                    'daily_allowance' => $salary->daily_allowance,
                    'mobile_bill' => $salary->mobile_bill,
                    'other_allowance' => $salary->other_allowance,
                    'earning_adjustment' => $earning, //overtime
                    'total_payment' => $total_payment,
                    'provident_fund' => $salary->provident_fund,
                    'income_tax' => $salary->income_tax,
                    'revenue_stamp' => $salary->revenue_stamp,
                    //leave without pay	deduction_adjustment
                    //total_deduction	monthly_loan_repayments	present_days	absent_days	late_days	net_pay	salary_status	payment_method
                    //bank_account_no	created_by	created_at	updated_at
                    'deduction_adjustment' => $deduction,
                    'total_deduction' => $total_deduction,
                    'monthly_loan_repayments' => $loan,
                    'salary_status' => 'approved',
                    'payment_method' => $salary->salary_type,
                    'bank_account_no' => $salary->bank_account_no,
                    'net_pay' => $net_salary,
                    'created_by' => $user_id,

                ]
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Salary sheets generated successfully.',
        ]);
    }


    //compress

    public function salarySheet(Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        // Salary date for matching in salary_disbursement
        $salaryDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');

        // Fetch salaries along with department and employee info
        $salaries = DB::table('salary_sheets')
            ->join('employees', 'employees.id', '=', 'salary_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'salary_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            // Left join to get disbursement status
            ->leftJoin('salary_disbursements as sd', function ($join) use ($salaryDate) {
                $join->on('sd.salary_month', '=', DB::raw("'$salaryDate'"));
            })
            ->whereYear('salary_sheets.salary_date', $year)
            ->whereMonth('salary_sheets.salary_date', $month)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                'departments.Name as department_name',
                'salary_sheets.gross_salary',
                DB::raw('salary_sheets.earning_adjustment + salary_sheets.consolidated + salary_sheets.daily_allowance + salary_sheets.festival_bonus + salary_sheets.travel_allowance + salary_sheets.mobile_bill + salary_sheets.other_allowance AS total_earning'),
                DB::raw('salary_sheets.deduction_adjustment + salary_sheets.provident_fund + salary_sheets.income_tax + salary_sheets.revenue_stamp AS total_deduction'),
                'salary_sheets.monthly_loan_repayments',
                'salary_sheets.bank_account_no',
                'salary_sheets.net_pay',
                'salary_sheets.disbursement_status',
                DB::raw("COALESCE(sd.status, 'PENDING') as disbursement_status") // fetch status from salary_disbursement table
            )
            ->orderBy('departments.id')
            ->get();

        $net_total = $salaries->sum(fn($s) => (float) $s->net_pay);

        return response()->json([
            'salaries' => $salaries,
            'net_total' => $net_total
        ]);
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
        $employee = employee::select('employees.id', 'employees.Full_Name', 'emp_imgs.img_url', 'designations.Name as designation_name', 'designations.id as designation_id')
            ->leftJoin('emp_imgs', 'employees.id', '=', 'emp_imgs.EID')
            ->join('officials', 'employees.id', '=', 'officials.EID')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('employees.id', $id)
            ->first();

        if ($employee) {
            return response()->json($employee);
        } else {
            return response()->json(['message' => 'Employee not found'], 404);
        }
    }

    public function deleteSalary($id)
    {


        DB::beginTransaction();

        try {
            // Find the salary record by employee_id
            $salary = salaries::where('employee_id', $id)->first();

            if (!$salary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salary record not found'
                ], 404);
            }

            // Delete the salary record
            $salary->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Salary record deleted successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error while deleting salary record',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function salaryDisbursement(Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        // Fetch salaries already disbursed
        $salaries = DB::table('salary_sheets')
            ->join('employees', 'employees.id', '=', 'salary_sheets.employee_id')
            ->join('salary_disbursements', function ($join) use ($year, $month) {
                $join->on('salary_disbursements.salary_month', '=', DB::raw("DATE_FORMAT(salary_sheets.salary_date, '%Y-%m-01')"))
                    ->whereYear('salary_disbursements.salary_month', $year)
                    ->whereMonth('salary_disbursements.salary_month', $month);
            })
            ->whereYear('salary_sheets.salary_date', $year)
            ->whereMonth('salary_sheets.salary_date', $month)
            ->where('salary_sheets.disbursement_status', 1)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                'salary_sheets.bank_account_no',
                'salary_sheets.net_pay',
                'salary_disbursements.id as disbursement_id',
                'salary_disbursements.status as disbursement_status'
            )
            ->get();

        // Calculate totals
        $bankTotal = $salaries
            ->whereNotNull('bank_account_no')   // only bank accounts
            ->sum(fn($s) => (float) $s->net_pay);

        $cashTotal = $salaries
            ->whereNull('bank_account_no')      // only cash payments
            ->sum(fn($s) => (float) $s->net_pay);

        return response()->json([
            'salaries' => $salaries,
            'bank_total' => $bankTotal,
            'cash_total' => $cashTotal,
        ]);
    }




    public function SalaryDisbursementBankPDF(Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        // Convert month number to month name
        $monthName = date('F', mktime(0, 0, 0, $month, 10)); // e.g., 1 => January

        $salaries = DB::table('salary_sheets')
            ->join('employees', 'employees.id', '=', 'salary_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'salary_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('salary_sheets.payment_method', 'bank')
            ->whereYear('salary_sheets.salary_date', $year)
            ->whereMonth('salary_sheets.salary_date', $month)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                'salary_sheets.bank_account_no',
                'salary_sheets.net_pay'
            )->orderBy('departments.id')->get();
        $grandTotal = $salaries->sum('net_pay');

        $pdf = Pdf::loadView('pdf.salary-disbursement-bank-pdf', [
            'data' => $salaries,
            'year' => $year,
            'month' => $month,
            'monthName' => $monthName, // pass it to view
            'grandTotal' => $grandTotal
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("Salary_Disbursement_{$monthName}_{$year}.pdf");
    }


    public function SalaryDisbursementCashPDF(Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        $monthName = date('F', mktime(0, 0, 0, $month, 10)); // e.g., 1 => January

        $salaries = DB::table('salary_sheets')
            ->join('employees', 'employees.id', '=', 'salary_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'salary_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('salary_sheets.payment_method', 'cash')
            ->whereYear('salary_sheets.salary_date', $year)
            ->whereMonth('salary_sheets.salary_date', $month)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                'salary_sheets.payment_method',
                'salary_sheets.net_pay'
            )->orderBy('departments.id')
            ->get();

        // Calculate grand total
        $grandTotal = $salaries->sum('net_pay');

        $pdf = Pdf::loadView('pdf.salary-disbursement-cash-pdf', [
            'data' => $salaries,
            'year' => $year,
            'month' => $month,
            'monthName' => $monthName,
            'grandTotal' => $grandTotal, // pass grand total to view
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("Salary_Disbursement_{$monthName}_{$year}.pdf");
    }


    public function SalaryDisbursementBankXLSX(Request $request)
    {
        $year = $request->year;
        $month = $request->month;
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        $data = DB::table('salary_sheets')
            ->join('employees', 'employees.id', '=', 'salary_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'salary_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('salary_sheets.payment_method', 'bank')
            ->whereYear('salary_sheets.salary_date', $year)
            ->whereMonth('salary_sheets.salary_date', $month)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                'salary_sheets.bank_account_no',
                'salary_sheets.net_pay'
            )->orderBy('departments.id')
            ->get();

        $subtotal = $data->sum('net_pay');

        return Excel::download(
            new SalaryDisbursementExport($data, "Salary Disbursement (Bank)", "{$monthName} {$year}", $subtotal),
            "Salary_Disbursement_Bank_{$monthName}_{$year}.xlsx"
        );
    }

    public function SalaryDisbursementCashXLSX(Request $request)
    {
        $year = $request->year;
        $month = $request->month;
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        $data = DB::table('salary_sheets')
            ->join('employees', 'employees.id', '=', 'salary_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'salary_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('salary_sheets.payment_method', 'cash')
            ->whereYear('salary_sheets.salary_date', $year)
            ->whereMonth('salary_sheets.salary_date', $month)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                DB::raw('"Cash" as bank_account_no'),
                'salary_sheets.net_pay'
            )->orderBy('departments.id')
            ->get();

        $subtotal = $data->sum('net_pay');

        return Excel::download(
            new SalaryDisbursementExport($data, "Salary Disbursement (Cash)", "{$monthName} {$year}", $subtotal),
            "Salary_Disbursement_Cash_{$monthName}_{$year}.xlsx"
        );
    }





    public function SalaryDisbursementBankWORD(Request $request)
    {
        $year = $request->year;
        $month = $request->month;
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        $data = DB::table('salary_sheets')
            ->join('employees', 'employees.id', '=', 'salary_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'salary_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('salary_sheets.payment_method', 'bank')
            ->whereYear('salary_sheets.salary_date', $year)
            ->whereMonth('salary_sheets.salary_date', $month)
            ->select(
                'employees.Employee_Id',
                'employees.Full_Name',
                'salary_sheets.bank_account_no',
                'salary_sheets.net_pay'
            )->orderBy('departments.id')
            ->get();

        $grandTotal = $data->sum('net_pay');

        $phpWord = new PhpWord();

        // Add Section
        $section = $phpWord->addSection([
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 600,
            'marginRight' => 600,
        ]);

        // Add Title
        $section->addText(
            "Salary Disbursement (Bank) - {$monthName} {$year}",
            ['bold' => true, 'size' => 16],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addTextBreak(1);

        // Add Table
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ];

        $firstRowStyle = ['bgColor' => 'CCCCCC'];

        $phpWord->addTableStyle('SalaryTable', $tableStyle, $firstRowStyle);
        $table = $section->addTable('SalaryTable');

        // Table Header
        $table->addRow();
        $table->addCell(1000)->addText('S/N', ['bold' => true]);
        $table->addCell(2000)->addText('Employee ID', ['bold' => true]);
        $table->addCell(4000)->addText('Employee Name', ['bold' => true]);
        $table->addCell(3000)->addText('Bank Account', ['bold' => true]);
        $table->addCell(2000)->addText('Net Salary', ['bold' => true]);

        // Table Rows
        foreach ($data as $index => $row) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(2000)->addText($row->Employee_Id);
            $table->addCell(4000)->addText($row->Full_Name);
            $table->addCell(3000)->addText($row->bank_account_no ?? 'Cash');
            $table->addCell(2000)->addText(number_format($row->net_pay, 2));
        }

        // Grand Total
        $table->addRow();
        $table->addCell(1000, ['gridSpan' => 4])->addText('Grand Total', ['bold' => true]);
        $table->addCell(2000)->addText(number_format($grandTotal, 2), ['bold' => true]);

        // Save File
        $fileName = "Salary_Disbursement_Bank_{$monthName}_{$year}.docx";
        $path = storage_path($fileName);

        $phpWord->save($path, 'Word2007');

        return response()->download($path)->deleteFileAfterSend(true);
    }




    public function SalaryDisbursementCashWORD(Request $request)
    {
        $year  = $request->year;
        $month = $request->month;

        // Month name (1 → January)
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        // Fetch CASH salary data
        $salaries = DB::table('salary_sheets')
            ->join('employees', 'employees.id', '=', 'salary_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'salary_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('salary_sheets.payment_method', 'cash')
            ->whereYear('salary_sheets.salary_date', $year)
            ->whereMonth('salary_sheets.salary_date', $month)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                'salary_sheets.net_pay'
            )
            ->orderBy('departments.id')
            ->get();

        // Grand total
        $grandTotal = $salaries->sum('net_pay');

        // Create Word file
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Title
        $section->addText(
            "Salary Disbursement (Cash) - {$monthName} {$year}",
            ['bold' => true, 'size' => 14]
        );
        $section->addTextBreak(1);

        // Table style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ];
        $phpWord->addTableStyle('CashTable', $tableStyle);

        $table = $section->addTable('CashTable');

        // Table header
        $table->addRow();
        $table->addCell(2000)->addText('Employee ID', ['bold' => true]);
        $table->addCell(4000)->addText('Employee Name', ['bold' => true]);
        $table->addCell(2000)->addText('Payment Method', ['bold' => true]);
        $table->addCell(2000)->addText('Net Salary', ['bold' => true]);

        // Table data
        foreach ($salaries as $salary) {
            $table->addRow();
            $table->addCell(2000)->addText($salary->employee_id);
            $table->addCell(4000)->addText($salary->full_name);
            $table->addCell(2000)->addText('Cash');
            $table->addCell(2000)->addText(number_format($salary->net_pay, 2));
        }

        // Grand total row
        $table->addRow();
        $table->addCell(8000, ['gridSpan' => 3])
            ->addText('Grand Total', ['bold' => true]);
        $table->addCell(2000)
            ->addText(number_format($grandTotal, 2), ['bold' => true]);

        // File name & path
        $fileName = "Salary_Disbursement_Cash_{$monthName}_{$year}.docx";
        $filePath = storage_path($fileName);

        // Save Word file
        $phpWord->save($filePath, 'Word2007');

        // Download & auto delete
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
    public function salaryPayslip(Request $request, $id)
    {
        // Get year & month from query parameters
        $year = $request->query('year');    // e.g. "2025"
        $month = $request->query('month');  // numeric month, e.g. 12
        // Ensure month is numeric
        $month = (int) $month;
        // Create first day of the month in YYYY-MM-DD format
        $salaryDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        $salary = DB::table('salary_sheets')
            ->join('employees', 'employees.id', '=', 'salary_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'salary_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->join('salary_disbursements as sd', function ($join) use ($salaryDate) {
                $join->on('sd.salary_month', '=', DB::raw("'$salaryDate'"))
                    ->where('sd.status', 'COMPLETED')
                    ->whereYear('sd.salary_month', Carbon::parse($salaryDate)->year)
                    ->whereMonth('sd.salary_month', Carbon::parse($salaryDate)->month);
            })
            ->leftJoin('emp_imgs as employee_images', 'employee_images.EID', '=', 'employees.id')
            ->where('employees.id', $id)
            ->whereDate('salary_sheets.salary_date', $salaryDate) // exact first day of month
            ->select(
                'employees.Employee_Id',
                'employees.Full_Name',
                'employee_images.img_url',
                'departments.Name as departmentName',
                'designations.Name as designationName',
                'salary_sheets.*'
            )
            ->first();
        return response()->json($salary);
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


    public function getEmployees($dept_id)
    {
        $employee = employee::select('employees.id', 'employees.Full_Name')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->where('departments.id', '=', $dept_id)
            ->where('officials.Status', '=', 'N')
            ->orderby('employees.Full_Name', 'asc')
            ->get();


        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }


        return response()->json($employee);
    }



    public function completeSalaryDisbursement(Request $request)
    {
        $user_id = Auth::user()->EID;
        $year = $request->input('year');
        $month = (int) $request->input('month');

        $salaryDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');

        // Check if the record exists for this month
        $record = salary_disbursement::where('salary_month', $salaryDate)->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'No salary disbursement record found for this month.',
                'data' => null
            ], 404);
        }

        // Check if already completed
        if ($record->status === 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Salary disbursement for this month already completed.',
                'data' => null
            ], 400);
        }

        // Update the status
        $record->status = 'COMPLETED';
        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Salary disbursement marked as completed.',
            'data' => $record
        ]);
    }


    public function salaryDisburse(Request $request)
    {
        $user_id = Auth::user()->EID;
        $year = $request->input('year');
        $month = (int) $request->input('month');

        $salaryDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');

        $monthly_salary_id = salary_sheet::where('salary_date', $salaryDate)
            ->value('monthly_salary_id');

        if (!$monthly_salary_id) {
            return response()->json([
                'message' => 'Salary sheet not found for selected month'
            ], 404);
        }

        DB::transaction(function () use ($salaryDate, $monthly_salary_id, $user_id) {

            salary_sheet::where('monthly_salary_id', $monthly_salary_id)
                ->update(['disbursement_status' => 1]);

            salary_disbursement::updateOrCreate(
                [
                    'salary_month' => $salaryDate,
                    'salary_id' => $monthly_salary_id,
                ],
                [
                    'status' => 'PENDING',
                    'created_by' => $user_id,
                ]
            );
        });
        return response()->json([
            'success' => true,
            'message' => 'Salary disbursement initiated successfully'
        ]);
    }
}
