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

class TotalLoanReportExport implements
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
            'Tenure',
            'Paid Tenure',
            'Total Paid',
            'Remaining Amount',
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
                // Title
                // -------------------------
                $sheet->insertNewRowBefore(1, 2);
                $sheet->setCellValue('A1', 'Artisan Business Network Bangladesh');
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A2', 'Total Loan Adjustment Report' . ($this->reportMonthYear ? " - {$this->reportMonthYear}" : ''));
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A1:H2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1:H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // -------------------------
                // Borders for all data cells
                // -------------------------
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A3:H{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // -------------------------
                // Header Fill
                // -------------------------
                $sheet->getStyle('A3:H3')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('D9E1F2');

                // -------------------------
                // Format numeric columns
                // -------------------------
                $sheet->getStyle("C4:G{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // -------------------------
                // Total row at the bottom
                // -------------------------
                $totalRow = $lastRow + 1;
                $sheet->setCellValue("B{$totalRow}", 'TOTAL');
                $sheet->setCellValue("C{$totalRow}", "=SUM(C4:C{$lastRow})"); // Total Loan Amount
                $sheet->setCellValue("F{$totalRow}", "=SUM(F4:F{$lastRow})"); // Total Paid
                $sheet->setCellValue("G{$totalRow}", "=SUM(G4:G{$lastRow})"); // Remaining Amount

                // Bold total row
                foreach (['B', 'C', 'F', 'G'] as $col) {
                    $sheet->getStyle("{$col}{$totalRow}")->getFont()->setBold(true);
                }

                // Add border to total row
                $sheet->getStyle("A{$totalRow}:H{$totalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }

    public function title(): string
    {
        return 'Total Loan Report';
    }
}
