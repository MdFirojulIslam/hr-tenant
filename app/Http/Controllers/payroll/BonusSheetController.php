<?php


namespace App\Http\Controllers\payroll;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\bonus_sheet;
use App\Models\salary_sheet;
use App\Exports\BonusSheetExport;
use App\Exports\BonusDisbursementExport;
use App\Models\bonus_disbursement;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;


class BonusSheetController extends Controller
{
    public function bonusSheet(Request $request)
    {
        $year = $request->year;
        $month = $request->month;
        $bonusDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');

        $baseQuery = DB::table('bonus_sheets')
            ->join('bonuses', 'bonuses.id', '=', 'bonus_sheets.bonus_id')
            ->join('employees', 'employees.id', '=', 'bonus_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'bonus_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->whereYear('bonus_sheets.bonus_date', $year)
            ->whereMonth('bonus_sheets.bonus_date', $month);

        $data = (clone $baseQuery)
            ->leftJoin('bonus_disbursements as bd', function ($join) use ($bonusDate) {
                $join->on('bd.bonus_date', '=', DB::raw("'$bonusDate'"))
                    ->on('bd.bonus_id', '=', 'bonus_sheets.bonus_id');
            })
            ->select(
                'employees.id as employee_id',
                'employees.Employee_Id',
                'employees.Full_Name as full_name',
                'officials.DOJ as date_of_join',
                'departments.Name as department_name',
                'bonus_sheets.id',
                'bonus_sheets.gross_salary',
                'bonus_sheets.basic_salary',
                'bonus_sheets.bonus_amounts',
                'bonus_sheets.bonus_percentages',
                'bonus_sheets.bank_account_no',
                'bonuses.name as festival_name',
                'bonus_sheets.bonus_disbursements',
                DB::raw("COALESCE(bd.status, 'NOT_INITIATED') as disbursement_status")
            )
            ->get();

        $totalBonus = (clone $baseQuery)
            ->sum('bonus_sheets.bonus_amounts');

        return response()->json([
            'data' => $data,
            'total_bonus_amount' => $totalBonus
        ]);
    }


    public function generateBonus(Request $request)
    {
        DB::beginTransaction();
        $user_id = Auth::user()->EID;

        $monthString = $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-01';
        bonus_sheet::where('bonus_date', $monthString)->delete();
        $month_start = Carbon::parse($monthString)->startOfMonth()->toDateString();
        $month_end   = Carbon::parse($monthString)->endOfMonth()->toDateString();

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


        foreach ($salaries as $salary) {
            $bonus = $salary->basic;

            bonus_sheet::updateOrCreate(
                [
                    'employee_id' => $salary->employee_id,
                    'bonus_date' => $monthString
                ],
                [
                    //gross_salary  basic_salary    house_rent  conveyance
                    'bonus_id' => '1',
                    // 'monthly_bonus_id' => $monthly_bonus_id,
                    'gross_salary' => $salary->gross_salary,
                    'basic_salary' => $salary->basic,
                    'bonus_percentages' => 100,
                    'bonus_amounts' => $bonus,
                    'payment_methods' => $salary->salary_type,
                    'bank_account_no' => $salary->bank_account_no,
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


    public function updatePercentage(Request $request)
    {

        $bonus = DB::table('bonus_sheets')
            ->where('id', $request->id)
            ->first();
        if (!$bonus) {
            return response()->json(['success' => false]);
        }

        $percentage = $request->percentage;
        $bonusAmount = ($bonus->basic_salary * $percentage) / 100;
        DB::table('bonus_sheets')
            ->where('id', $request->id)
            ->update([
                'bonus_percentages' => $percentage,
                'bonus_amounts' => $bonusAmount,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'bonus_amounts' => $bonusAmount
        ]);
    }


    public function exportBonusSheet(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');
        return Excel::download(new BonusSheetExport($year, $month), "bonus_sheet.xlsx");
    }

    public function completeBonusDisbursement(Request $request)
    {
        $user_id = Auth::user()->EID;
        $year = $request->input('year');
        $month = (int) $request->input('month');

        $bonusDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');

        // Check if the record exists for this month
        $record = bonus_disbursement::where('bonus_date', $bonusDate)->first();

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

    public function bonusDisbursement(Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        $salaries = DB::table('bonus_sheets')
            ->join('employees', 'employees.id', '=', 'bonus_sheets.employee_id')
            ->join('bonus_disbursements', function ($join) use ($year, $month) {
                $join->on(
                    'bonus_disbursements.bonus_date',
                    '=',
                    DB::raw("DATE_FORMAT(bonus_sheets.bonus_date, '%Y-%m-01')")
                )
                    ->whereYear('bonus_disbursements.bonus_date', $year)
                    ->whereMonth('bonus_disbursements.bonus_date', $month);
            })
            ->whereYear('bonus_sheets.bonus_date', $year)
            ->whereMonth('bonus_sheets.bonus_date', $month)
            ->where('bonus_sheets.bonus_disbursements', 1)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                'bonus_sheets.bank_account_no',
                'bonus_sheets.bonus_amounts',
                'bonus_disbursements.id as disbursement_id',
                'bonus_disbursements.status as bonus_disbursements'
            )
            ->get();

        $bankTotal = $salaries
            ->whereNotNull('bank_account_no')
            ->sum(fn($s) => (float) $s->bonus_amounts);

        $cashTotal = $salaries
            ->whereNull('bank_account_no')
            ->sum(fn($s) => (float) $s->bonus_amounts);

        return response()->json([
            'salaries'   => $salaries,
            'bank_total' => $bankTotal,
            'cash_total' => $cashTotal,
        ]);
    }

    public function BonusDisbursementBankPDF(Request $request)
    {
        $year  = $request->year;
        $month = $request->month;
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        $data = DB::table('bonus_sheets')
            ->join('employees', 'employees.id', '=', 'bonus_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'bonus_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->whereNotNull('bonus_sheets.bank_account_no')
            ->whereYear('bonus_sheets.bonus_date', $year)
            ->whereMonth('bonus_sheets.bonus_date', $month)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                'bonus_sheets.bank_account_no',
                'bonus_sheets.bonus_amounts'
            )
            ->orderBy('departments.id')
            ->get();

        $grandTotal = $data->sum('bonus_amounts');

        $pdf = Pdf::loadView('pdf.bonus-disbursement-bank-pdf', [
            'data' => $data,
            'year' => $year,
            'monthName' => $monthName,
            'grandTotal' => $grandTotal
        ]);

        return $pdf->download("Bonus_Disbursement_Bank_{$monthName}_{$year}.pdf");
    }

    public function BonusDisbursementCashPDF(Request $request)
    {
        $year  = $request->year;
        $month = $request->month;
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        $data = DB::table('bonus_sheets')
            ->join('employees', 'employees.id', '=', 'bonus_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'bonus_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->whereNull('bonus_sheets.bank_account_no')
            ->whereYear('bonus_sheets.bonus_date', $year)
            ->whereMonth('bonus_sheets.bonus_date', $month)
            ->select(
                'employees.Employee_Id as employee_id',
                'employees.Full_Name as full_name',
                DB::raw('"Cash" as bank_account_no'),
                'bonus_sheets.bonus_amounts',
                'bonus_sheets.payment_methods'
            )
            ->orderBy('departments.id')
            ->get();

        $grandTotal = $data->sum('bonus_amounts');

        $pdf = Pdf::loadView('pdf.bonus-disbursement-cash-pdf', [
            'data' => $data,
            'year' => $year,
            'monthName' => $monthName,
            'grandTotal' => $grandTotal
        ]);

        return $pdf->download("Bonus_Disbursement_Cash_{$monthName}_{$year}.pdf");
    }

    public function BonusDisbursementBankXLSX(Request $request)
    {
        $year  = $request->year;
        $month = $request->month;
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        $data = DB::table('bonus_sheets')
            ->join('employees', 'employees.id', '=', 'bonus_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'bonus_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->whereNotNull('bonus_sheets.bank_account_no')
            ->whereYear('bonus_sheets.bonus_date', $year)
            ->whereMonth('bonus_sheets.bonus_date', $month)
            ->select(
                DB::raw("CONCAT('ABNB-', employees.Employee_Id) as employee_id"),
                'employees.Full_Name as full_name',
                'bonus_sheets.bank_account_no',
                'bonus_sheets.bonus_amounts'
            )
            ->orderBy('departments.id')
            ->get();

        $subtotal = $data->sum('bonus_amounts');

        return Excel::download(
            new BonusDisbursementExport($data, "Bonus Disbursement (Bank)", "{$monthName} {$year}", $subtotal),
            "Bonus_Disbursement_Bank_{$monthName}_{$year}.xlsx"
        );
    }

    public function BonusDisbursementCashXLSX(Request $request)
    {
        $year  = $request->year;
        $month = $request->month;
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        $data = DB::table('bonus_sheets')
            ->join('employees', 'employees.id', '=', 'bonus_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'bonus_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->whereNull('bonus_sheets.bank_account_no')
            ->whereYear('bonus_sheets.bonus_date', $year)
            ->whereMonth('bonus_sheets.bonus_date', $month)
            ->select(
                DB::raw("CONCAT('ABNB-', employees.Employee_Id) as employee_id"),
                'employees.Full_Name as full_name',
                DB::raw('"Cash" as bank_account_no'),
                'bonus_sheets.bonus_amounts'
            )
            ->orderBy('departments.id')
            ->get();

        $subtotal = $data->sum('bonus_amounts');

        return Excel::download(
            new BonusDisbursementExport($data, "Bonus Disbursement (Cash)", "{$monthName} {$year}", $subtotal),
            "Bonus_Disbursement_Cash_{$monthName}_{$year}.xlsx"
        );
    }

    public function BonusDisbursementBankWORD(Request $request)
    {
        $year = $request->year;
        $month = $request->month;
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        $data = DB::table('bonus_sheets')
            ->join('employees', 'employees.id', '=', 'bonus_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'bonus_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('bonus_sheets.payment_methods', 'bank')
            ->whereYear('bonus_sheets.bonus_date', $year)
            ->whereMonth('bonus_sheets.bonus_date', $month)
            ->select(
                DB::raw("CONCAT('ABNB', employees.Employee_Id) as employee_id"),
                'employees.Full_Name',
                'bonus_sheets.bank_account_no',
                'bonus_sheets.bonus_amounts'
            )->orderBy('departments.id')
            ->get();

        $grandTotal = $data->sum('bonus_amounts');

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
            "Bonus Disbursement (Bank) - {$monthName} {$year}",
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
        $table->addCell(2000)->addText('Bonus Amount', ['bold' => true]);

        // Table Rows
        foreach ($data as $index => $row) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(2000)->addText($row->employee_id);
            $table->addCell(4000)->addText($row->Full_Name);
            $table->addCell(3000)->addText($row->bank_account_no ?? 'Cash');
            $table->addCell(2000)->addText(number_format($row->bonus_amounts, 2));
        }

        // Grand Total
        $table->addRow();
        $table->addCell(1000, ['gridSpan' => 4])->addText('Grand Total', ['bold' => true]);
        $table->addCell(2000)->addText(number_format($grandTotal, 2), ['bold' => true]);

        // Save File
        $fileName = "Bonus_Disbursement_Bank_{$monthName}_{$year}.docx";
        $path = storage_path($fileName);

        $phpWord->save($path, 'Word2007');

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function BonusDisbursementCashWORD(Request $request)
    {
        $year  = $request->year;
        $month = $request->month;

        // Month name (1 → January)
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        // Fetch CASH salary data
        $salaries = DB::table('bonus_sheets')
            ->join('employees', 'employees.id', '=', 'bonus_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'bonus_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('bonus_sheets.payment_methods', 'cash')
            ->whereYear('bonus_sheets.bonus_date', $year)
            ->whereMonth('bonus_sheets.bonus_date', $month)
            ->select(
                DB::raw("CONCAT('ABNB', employees.Employee_Id) as Employee_Id"),
                'employees.Full_Name as full_name',
                'bonus_sheets.bonus_amounts'
            )
            ->orderBy('departments.id')
            ->get();

        // Grand total
        $grandTotal = $salaries->sum('bonus_amounts');

        // Create Word file
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Title
        $section->addText(
            "Bonus Disbursement (Cash) - {$monthName} {$year}",
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
        $table->addCell(2000)->addText('Net Bonus', ['bold' => true]);

        // Table data
        foreach ($salaries as $salary) {
            $table->addRow();
            $table->addCell(2000)->addText($salary->employee_id);
            $table->addCell(4000)->addText($salary->full_name);
            $table->addCell(2000)->addText('Cash');
            $table->addCell(2000)->addText(number_format($salary->bonus_amounts, 2));
        }

        // Grand total row
        $table->addRow();
        $table->addCell(8000, ['gridSpan' => 3])
            ->addText('Grand Total', ['bold' => true]);
        $table->addCell(2000)
            ->addText(number_format($grandTotal, 2), ['bold' => true]);

        // File name & path
        $fileName = "Bonus_Disbursement_Cash_{$monthName}_{$year}.docx";
        $filePath = storage_path($fileName);

        // Save Word file
        $phpWord->save($filePath, 'Word2007');

        // Download & auto delete
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function bonusDisburse(Request $request)
    {
        $user_id = Auth::user()->EID;
        $year = $request->input('year');
        $month = (int) $request->input('month');

        $bonusDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');

        $bonus_id = bonus_sheet::where('bonus_date', $bonusDate)
            ->value('bonus_id');

        if (!$bonus_id) {
            return response()->json([
                'message' => 'Bonus sheet not found for selected month'
            ], 404);
        }

        DB::transaction(function () use ($bonusDate, $bonus_id, $user_id) {

            $updated = bonus_sheet::where('bonus_date', $bonusDate)
                ->update(['bonus_disbursements' => 1]);

            $disbursement = bonus_disbursement::updateOrCreate(
                [
                    'bonus_date' => $bonusDate,
                    'bonus_id' => $bonus_id,
                ],
                [
                    'status' => 'PENDING',
                    'created_by' => $user_id,
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Bonus disbursement initiated successfully'
        ]);
    }
}
