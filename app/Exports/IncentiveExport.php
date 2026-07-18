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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class IncentiveExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data['details'];
    }

    public function headings(): array
    {
        return [
            'SL',
            'Employee ID',
            'Employee Name',
            'Department',
            'Designation',
            'Incentive Amount',
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
        $incentive = $this->data['incentive'];
        $totalAmount = $this->data['totalAmount'];
        $totalEmployees = $this->data['totalEmployees'];

        return [
            AfterSheet::class => function (AfterSheet $event) use ($incentive, $totalAmount, $totalEmployees) {
                $sheet = $event->sheet;

                $sheet->insertNewRowBefore(1, 6);

                $sheet->setCellValue('A1', 'Artisan Business Network Bangladesh');
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A2', 'Employee Incentive Report');
                $sheet->mergeCells('A2:G2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A4', 'Incentive Period:');
                $sheet->setCellValue('B4', $incentive->incentive_periods);
                $sheet->setCellValue('D4', 'Status:');
                $sheet->setCellValue('E4', $incentive->status);
                $sheet->getStyle('A4')->getFont()->setBold(true);
                $sheet->getStyle('D4')->getFont()->setBold(true);

                $sheet->setCellValue('A5', 'Export Date:');
                $sheet->setCellValue('B5', now()->format('d M Y'));
                $sheet->setCellValue('D5', 'Total Employees:');
                $sheet->setCellValue('E5', $totalEmployees);
                $sheet->getStyle('A5')->getFont()->setBold(true);
                $sheet->getStyle('D5')->getFont()->setBold(true);

                $lastRow = $sheet->getHighestRow();

                $totalRow = $lastRow + 1;
                $sheet->setCellValue("E{$totalRow}", 'Total Amount');
                $sheet->setCellValue("F{$totalRow}", $totalAmount);
                $sheet->getStyle("E{$totalRow}:F{$totalRow}")->getFont()->setBold(true)->setSize(11);

                $sheet->getStyle("A7:F{$totalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle('A7:G7')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('D9E1F2');

                $sheet->getStyle("F8:F{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }
        ];
    }

    public function title(): string
    {
        return 'Incentive Report';
    }
}
