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
use PhpOffice\PhpSpreadsheet\Style\{
    Alignment,
    NumberFormat,
    Border
};

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
     * =========================
     * DB QUERY
     * =========================
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
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->whereYear('salary_date', $this->year)
            ->whereMonth('salary_date', $this->month)
            ->orderBy('departments.id')
            ->orderBy('employees.Employee_Id');
    }

    /**
     * =========================
     * ROW MAPPING
     * =========================
     */
    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->Full_Name,
            $row->designation_name,
            $row->DOJ,
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
            (float) $row->net_pay,
            ucfirst($row->payment_method),
            (string) $row->bank_account_no,
        ];
    }

    /**
     * =========================
     * HEADINGS (NO EMPTY ROW)
     * =========================
     */
    public function headings(): array
    {
        return [
            ["Salary Sheet of " . date('F', mktime(0, 0, 0, $this->month)) . " {$this->year}"],
            ["Artisan Business Network Bangladesh"],
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
     * =========================
     * COLUMN FORMAT
     * =========================
     */
    public function columnFormats(): array
    {
        return [
            'AB' => NumberFormat::FORMAT_TEXT, // Bank Account No
        ];
    }

    /**
     * =========================
     * STYLING & EVENTS
     * =========================
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet      = $event->sheet;
                $lastColumn = $sheet->getHighestColumn();
                $lastRow    = $sheet->getHighestRow();

                /* ===== Merge Titles ===== */
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");

                /* ===== Title Styles ===== */
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                /* ===== Header Row ===== */
                $sheet->freezePane('A4');

                $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'textRotation' => 90,
                    ],
                ]);

                /* ===== Total Net Pay ===== */
                $netPayColumn = 'Z';
                $totalRow = $lastRow + 1;

                $sheet->setCellValue("Y{$totalRow}", 'Total');
                $sheet->setCellValue(
                    "{$netPayColumn}{$totalRow}",
                    "=SUM({$netPayColumn}4:{$netPayColumn}{$lastRow})"
                );

                $sheet->getStyle("Y{$totalRow}:{$netPayColumn}{$totalRow}")
                    ->getFont()
                    ->setBold(true);

                /* ===== Full Border (Header + Data + Total) ===== */
                $sheet->getStyle("A3:{$lastColumn}{$totalRow}")
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
            },
        ];
    }
}
