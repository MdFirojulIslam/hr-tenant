<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExpenseSummaryExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $expenses;
    protected $totalTa;
    protected $totalDaOe;
    protected $totalAmount;
    protected $monthName;
    protected $year;
    protected $departmentFilter;

    public function __construct($expenses, $totalTa, $totalDaOe, $totalAmount, $monthName, $year, $departmentFilter = null)
    {
        $this->expenses = $expenses;
        $this->totalTa = $totalTa;
        $this->totalDaOe = $totalDaOe;
        $this->totalAmount = $totalAmount;
        $this->monthName = $monthName;
        $this->year = $year;
        $this->departmentFilter = $departmentFilter;
    }

    public function collection()
    {
        return $this->expenses->values()->map(function ($item, $index) {
            return [
                'sl' => $index + 1,
                'employee_id' => 'ABNB' . $item->Employee_Id,
                'employee_name' => $item->Full_Name,
                'designation' => $item->Des_Name,
                'ta' => (float) $item->total_ta,
                'da_others' => (float) $item->total_da_oe,
                'amount' => (float) $item->total_expense,
                'remarks' => '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SL',
            'Employee ID',
            'Employee Name',
            'Designation',
            'TA',
            'DA/Others',
            'Amount',
            'Remarks',
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
        $expenses = $this->expenses;
        $totalTa = $this->totalTa;
        $totalDaOe = $this->totalDaOe;
        $totalAmount = $this->totalAmount;
        $monthName = $this->monthName;
        $year = $this->year;
        $departmentFilter = $this->departmentFilter;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($expenses, $totalTa, $totalDaOe, $totalAmount, $monthName, $year, $departmentFilter) {
                $sheet = $event->sheet;

                $sheet->insertNewRowBefore(1, 4);

                $lastCol = 'H';

                $sheet->setCellValue('A1', 'Artisan Business Network Bangladesh');
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A2', 'Travel/Daily Expense Report');
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A3', 'Month:');
                $sheet->setCellValue('B3', "{$monthName} {$year}");
                $sheet->getStyle('A3')->getFont()->setBold(true);

                if ($departmentFilter) {
                    $sheet->setCellValue('D3', 'Department:');
                    $sheet->setCellValue('E3', $departmentFilter);
                    $sheet->getStyle('D3')->getFont()->setBold(true);
                }

                $sheet->setCellValue('A4', 'Export Date & Time:');
                $sheet->setCellValue('B4', now()->setTimezone('Asia/Dhaka')->format('d F, Y h:i A'));
                $sheet->getStyle('A4')->getFont()->setBold(true);

                $dataStartRow = 6;
                $lastDataRow = $dataStartRow + $expenses->count() - 1;

                if ($expenses->count() > 0) {
                    $sheet->getStyle("A{$dataStartRow}:{$lastCol}{$lastDataRow}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    $sheet->getStyle("A{$dataStartRow}:{$lastCol}{$dataStartRow}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('D9E1F2');
                }

                $sheet->getStyle("E{$dataStartRow}:G{$lastDataRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                $totalRow = $lastDataRow + 1;
                $sheet->setCellValue("A{$totalRow}", '');
                $sheet->setCellValue("B{$totalRow}", '');
                $sheet->setCellValue("C{$totalRow}", '');
                $sheet->setCellValue("D{$totalRow}", 'Grand Total');
                $sheet->setCellValue("E{$totalRow}", $totalTa);
                $sheet->setCellValue("F{$totalRow}", $totalDaOe);
                $sheet->setCellValue("G{$totalRow}", $totalAmount);
                $sheet->setCellValue("H{$totalRow}", '');
                $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle("D{$totalRow}:G{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("E{$totalRow}:G{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                $totalRowCount = $totalRow + 2;
                $sheet->setCellValue("A{$totalRowCount}", 'Manager');
                $sheet->setCellValue("B{$totalRowCount}", 'Recommended By');
                $sheet->setCellValue("C{$totalRowCount}", 'Approved By');

                $lineRow = $totalRowCount + 1;
                $sheet->setCellValue("A{$lineRow}", '__________________');
                $sheet->setCellValue("B{$lineRow}", '__________________');
                $sheet->setCellValue("C{$lineRow}", '__________________');

                $sheet->getStyle("A{$totalRowCount}:C{$lineRow}")->getFont()->setBold(true);
            }
        ];
    }
}
