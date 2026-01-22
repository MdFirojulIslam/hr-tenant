<?php

namespace App\Exports;

use App\Models\salary_sheet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{
    FromQuery,
    WithHeadings,
    WithEvents,
    WithMapping,
    ShouldAutoSize,
    WithColumnFormatting
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SalarySheetExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    ShouldAutoSize,
    WithColumnFormatting
{
    protected int $year;
    protected int $month;

    public function __construct(int $year, int $month)
    {
        $this->year  = $year;
        $this->month = $month;
    }

    /**
     * ✅ DB-level query (high performance)
     */
    public function query(): Builder
    {
        return salary_sheet::query()
            ->select(
                DB::raw("CONCAT('ABNB-', employees.Employee_Id) as employee_code"),
                'employees.Full_Name',
                'designations.Name as designation_name',
                'officials.DOJ',
                'salary_sheets.absent_days',
                'salary_sheets.gross_salary',
                'salary_sheets.basic_salary',
                'salary_sheets.house_rent',
                'salary_sheets.conveyance',
                'salary_sheets.medical_allowance',
                'salary_sheets.consolidated',
                'salary_sheets.festival_bonus',
                'salary_sheets.travel_allowance',
                'salary_sheets.earning_adjustment',
                'salary_sheets.daily_allowance',
                'salary_sheets.mobile_bill',
                'salary_sheets.other_allowance',
                'salary_sheets.total_payment',
                'salary_sheets.provident_fund',
                'salary_sheets.income_tax',
                'salary_sheets.revenue_stamp',
                'salary_sheets.leave_without_pay',
                'salary_sheets.deduction_adjustment',
                'salary_sheets.total_deduction',
                'salary_sheets.monthly_loan_repayments',
                'salary_sheets.net_pay',
                'salary_sheets.payment_method',
                'salary_sheets.bank_account_no'
            )
            ->join('employees', 'salary_sheets.employee_id', '=', 'employees.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_ID', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->whereYear('salary_date', $this->year)
            ->whereMonth('salary_date', $this->month)
            ->orderBy('departments.id')
            ->orderBy('employees.Employee_Id');
    }

    /**
     * ✅ Row mapping
     */
    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->Full_Name,
            $row->designation_name,
            Carbon::parse($row->DOJ)->format('d M Y'),
            $row->absent_days,
            $row->gross_salary,
            $row->basic_salary,
            $row->house_rent,
            $row->conveyance,
            $row->medical_allowance,
            $row->consolidated,
            $row->festival_bonus,
            $row->travel_allowance,
            $row->earning_adjustment,
            $row->daily_allowance,
            $row->mobile_bill,
            $row->other_allowance,
            $row->total_payment,
            $row->provident_fund,
            $row->income_tax,
            $row->revenue_stamp,
            $row->leave_without_pay,
            $row->deduction_adjustment,
            $row->total_deduction,
            $row->monthly_loan_repayments,
            $row->net_pay,
            ucfirst($row->payment_method),

            // 🔒 CRITICAL FIX
            (string) $row->bank_account_no,
        ];
    }

    /**
     * ✅ Headings
     */
    public function headings(): array
    {
        return [
            ["Salary Sheet of " . date('F', mktime(0, 0, 0, $this->month)) . " {$this->year}"],
            ["Artisan Business Network Bangladesh"],
            [],
            [
                'EID',
                'Name',
                'Designation',
                'Date Of Join',
                'Absent',
                'Gross Salary',
                'Basic Salary',
                'House Rent',
                'Conveyance',
                'Medical Allowance',
                'Consolidated',
                'Festival Bonus',
                'Travel Allowance',
                'Overtime/Adjustment',
                'Daily Allowance',
                'Mobile Bill',
                'Other Allowance',
                'Total Payment',
                'Provident Fund',
                'Income Tax',
                'Revenue Stamp',
                'Leave Without Pay',
                'Advance/Other Deduction',
                'Total Deduction',
                'Monthly Loan Repayment',
                'Net Pay',
                'Payment Method',
                'Bank Account No'
            ]
        ];
    }

    /**
     * ✅ Force Excel column formatting
     */
    public function columnFormats(): array
    {
        return [
            // Column AB = Bank Account No
            'AB' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /**
     * ✅ Styling & UX
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $lastColumn = $event->sheet->getHighestColumn();

                $event->sheet->mergeCells("A1:{$lastColumn}1");
                $event->sheet->mergeCells("A2:{$lastColumn}2");

                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $event->sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $event->sheet->freezePane('A5');

                $event->sheet
                    ->getStyle("A4:{$lastColumn}4")
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
