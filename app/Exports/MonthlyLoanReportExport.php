<?php

namespace App\Exports;

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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonthlyLoanReportExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle
{
    protected $data;
    protected $reportMonthYear;

    public function __construct($data, $reportMonthYear = '')
    {
        $this->data = $data;
        $this->reportMonthYear = $reportMonthYear;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Total Loan Amount',
            'Last Installment',
            'Last Installment Date'
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

                // -------------------------
                // Title Rows
                // -------------------------
                $sheet->insertNewRowBefore(1, 2);
                $sheet->setCellValue('A1', 'Artisan Business Network Bangladesh');
                $sheet->mergeCells('A1:E1');

                $sheet->setCellValue(
                    'A2',
                    'Monthly Loan Adjustment Report' . ($this->reportMonthYear ? " - {$this->reportMonthYear}" : '')
                );
                $sheet->mergeCells('A2:E2');

                $sheet->getStyle('A1:E2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1:E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();

                // -------------------------
                // Borders
                // -------------------------
                $sheet->getStyle("A3:E{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // -------------------------
                // Header Fill
                // -------------------------
                $sheet->getStyle('A3:E3')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('D9E1F2');

                // -------------------------
                // Format numeric columns
                // -------------------------
                $sheet->getStyle("C4:D{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // -------------------------
                // Total row for Last Installment (optional)
                // -------------------------
                $totalRow = $lastRow + 1;
                $sheet->setCellValue("B{$totalRow}", 'TOTAL');
                $sheet->setCellValue("D{$totalRow}", "=SUM(D4:D{$lastRow})");

                // Bold total row
                foreach (['B', 'D'] as $col) {
                    $sheet->getStyle("{$col}{$totalRow}")->getFont()->setBold(true);
                }

                // Border for total row
                $sheet->getStyle("A{$totalRow}:E{$totalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }

    public function title(): string
    {
        return 'Monthly Loan Report';
    }
}
