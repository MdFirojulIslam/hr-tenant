<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SalaryAdjustmentExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle
{
    protected $month;
    protected $year;
    protected $totalAmount = 0;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        // Earnings
        $earnings = DB::table('salary_earnings as se')
            ->join('employees', 'employees.id', '=', 'se.employee_id')
            ->join('salary_adjustment_types as sat', 'sat.id', '=', 'se.adjustment_types_id')
            ->select(
                DB::raw("CONCAT('ABNB', employees.Employee_Id) as Employee_ID"),
                'employees.Full_Name as Employee_Name',
                DB::raw("'Earning' as Type"),
                'sat.adjustment_name as Adjustment_Name',
                'se.amount as Amount',
                'se.remarks as Remarks'
            )
            ->whereMonth('se.date', $this->month)
            ->whereYear('se.date', $this->year);

        // Deductions
        $deductions = DB::table('salary_deductions as sd')
            ->join('employees', 'employees.id', '=', 'sd.employee_id')
            ->join('salary_adjustment_types as sat', 'sat.id', '=', 'sd.adjustment_types_id')
            ->select(
                DB::raw("CONCAT('ABNB', employees.Employee_Id) as Employee_ID"),
                'employees.Full_Name as Employee_Name',
                DB::raw("'Deduction' as Type"),
                'sat.adjustment_name as Adjustment_Name',
                'sd.amount as Amount',
                'sd.remarks as Remarks'
            )
            ->whereMonth('sd.date', $this->month)
            ->whereYear('sd.date', $this->year);

        $data = $earnings->unionAll($deductions)->get();
        $this->totalAmount = $data->sum('Amount');

        return $data;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Type',
            'Adjustment Name',
            'Amount',
            'Remarks'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Insert title rows
                $sheet->insertNewRowBefore(1, 2);
                $sheet->setCellValue('A1', 'Artisan Business Network Bangladesh');
                $sheet->mergeCells('A1:F1');

                $monthName = date("F", mktime(0, 0, 0, $this->month, 1));
                $sheet->setCellValue('A2', "Salary Adjustment Report - {$monthName} {$this->year}");
                $sheet->mergeCells('A2:F2');

                $sheet->getStyle('A1:F2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();

                // Amount formatting
                $sheet->getStyle("E4:E{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // Total row
                $totalRow = $lastRow + 1;
                $sheet->setCellValue("D{$totalRow}", 'Total Amount');
                $sheet->setCellValue("E{$totalRow}", $this->totalAmount);
                $sheet->getStyle("D{$totalRow}:E{$totalRow}")->getFont()->setBold(true);

                // Table borders
                $sheet->getStyle("A3:F{$totalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Header fill
                $sheet->getStyle('A3:F3')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('D9E1F2');
            }
        ];
    }

    public function title(): string
    {
        return 'Salary Adjustment';
    }
}
