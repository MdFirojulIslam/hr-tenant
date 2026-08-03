<?php

namespace App\Exports;

use App\Models\salary_sheet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{
    FromQuery,
    WithHeadings,
    WithEvents,
    WithMapping,
    ShouldAutoSize,
    WithColumnFormatting,
    WithCustomValueBinder
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\{
    Alignment,
    NumberFormat,
    Border
};

class SalarySheetExport extends DefaultValueBinder implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    ShouldAutoSize,
    WithColumnFormatting,
    WithCustomValueBinder
{
    protected int $year;
    protected int $month;

    public function __construct(int $year, int $month)
    {
        $this->year  = $year;
        $this->month = $month;
    }

    /**
     * Force Bank Account No (AE) as text so Excel does not show scientific notation.
     */
    public function bindValue($cell, $value)
    {
        if ($cell->getColumn() === 'AE') {
            $cell->setValueExplicit((string) ($value ?? ''), DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function query(): Builder
    {
        $monthStart = sprintf('%04d-%02d-01', $this->year, $this->month);
        $monthEnd   = date('Y-m-t', strtotime($monthStart));

        $earningRemarks = DB::table('salary_earnings')
            ->select(
                'employee_id',
                DB::raw("GROUP_CONCAT(NULLIF(TRIM(remarks), '') SEPARATOR '; ') as remarks")
            )
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->whereNotNull('remarks')
            ->where('remarks', '!=', '')
            ->groupBy('employee_id');

        $deductionRemarks = DB::table('salary_deductions')
            ->select(
                'employee_id',
                DB::raw("GROUP_CONCAT(NULLIF(TRIM(remarks), '') SEPARATOR '; ') as remarks")
            )
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->whereNotNull('remarks')
            ->where('remarks', '!=', '')
            ->groupBy('employee_id');

        return salary_sheet::query()
            ->select(
                DB::raw("CONCAT('ABNB', employees.Employee_Id) as employee_code"),
                'employees.Full_Name',
                'departments.Name as department_name',
                'designations.Name as designation_name',
                'officials.DOJ',
                'salary_sheets.present_days',
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
                'salary_sheets.net_pay',
                'salary_sheets.monthly_loan_repayments',
                'salary_sheets.final_payments',
                'salary_sheets.payment_method',
                'salary_sheets.bank_account_no',
                DB::raw("CONCAT_WS('/', er.remarks, dr.remarks) as adjustment_remarks")
            )
            ->join('employees', 'salary_sheets.employee_id', '=', 'employees.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->leftJoinSub($earningRemarks, 'er', function ($join) {
                $join->on('er.employee_id', '=', 'salary_sheets.employee_id');
            })
            ->leftJoinSub($deductionRemarks, 'dr', function ($join) {
                $join->on('dr.employee_id', '=', 'salary_sheets.employee_id');
            })
            ->whereYear('salary_date', $this->year)
            ->whereMonth('salary_date', $this->month)
            ->orderBy('departments.id')
            ->orderBy('employees.Employee_Id');
    }

    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->Full_Name,
            $row->department_name,
            $row->designation_name,
            $row->DOJ,
            $row->present_days,
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
            (float) $row->net_pay,
            $row->monthly_loan_repayments,
            (float) $row->final_payments,
            ucfirst($row->payment_method),
            (string) ($row->bank_account_no ?? ''),
            $row->adjustment_remarks ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            ["Salary Sheet of " . date('F', mktime(0, 0, 0, $this->month)) . " {$this->year}"],
            ["Artisan Business Network Bangladesh"],
            [
                'Employee ID',
                'Full Name',
                'Department',
                'Designation',
                'Date Of Join',
                'Present',
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
                'Net Pay',
                'Monthly Loan Repayment',
                'Payment Current Month',
                'Payment Method',
                'Bank Account No',
                'Remarks',
            ]
        ];
    }

    public function columnFormats(): array
    {
        return [
            // AE = Bank Account No (31st column)
            'AE' => NumberFormat::FORMAT_TEXT,
        ];
    }

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


                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->freezePane('A4');

                $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);


                $totalRow = $lastRow + 1;

                $sheet->setCellValue("G{$totalRow}", "Total");

                // Sum numeric money/day columns only: H (Gross) through AC (Payment Current Month).
                // Do not sum AD (Payment Method), AE (Bank Account No), or AF (Remarks).
                $startColumn = 'H';
                $endColumn   = 'AC';

                for ($column = $startColumn; $column !== $endColumn; $column++) {
                    $sheet->setCellValue(
                        "{$column}{$totalRow}",
                        "=SUM({$column}4:{$column}{$lastRow})"
                    );
                }

                $sheet->setCellValue(
                    "AC{$totalRow}",
                    "=SUM(AC4:AC{$lastRow})"
                );

                $sheet->getStyle("G{$totalRow}:AC{$totalRow}")
                    ->getFont()
                    ->setBold(true);

                // Keep bank account column as text after formulas/styles are applied
                $sheet->getStyle("AE4:AE{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);

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
