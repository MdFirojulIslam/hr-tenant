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

class IncentiveTemplateExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle
{
    protected $employees;
    protected $period;

    public function __construct($employees, $period)
    {
        $this->employees = $employees;
        $this->period = $period;
    }

    public function collection()
    {
        return $this->employees->values()->map(function ($emp) {
            return [
                'period' => $this->period,
                'department' => $emp->department_name,
                'employee_id' => 'ABNB' . $emp->Employee_Id,
                'employee_name' => $emp->Full_Name,
                'designation' => $emp->designation_name ?? '-',
                'incentive_amount' => '',
                'remarks' => '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Month Period',
            'Department',
            'Employee ID',
            'Employee Name',
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
        $period = $this->period;
        $totalEmployees = $this->employees->count();

        return [
            AfterSheet::class => function (AfterSheet $event) use ($period, $totalEmployees) {
                $sheet = $event->sheet;

                $sheet->insertNewRowBefore(1, 4);

                $sheet->setCellValue('A1', 'Artisan Business Network Bangladesh');
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A2', 'Incentive Bulk Entry Template');
                $sheet->mergeCells('A2:G2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A4', 'Incentive Period:');
                $sheet->setCellValue('B4', $period);
                $sheet->setCellValue('D4', 'Total Employees:');
                $sheet->setCellValue('E4', $totalEmployees);
                $sheet->getStyle('A4')->getFont()->setBold(true);
                $sheet->getStyle('D4')->getFont()->setBold(true);

                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A5:G{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle('A5:G5')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('D9E1F2');

                $sheet->getStyle("A5:G5")->getFont()->setBold(true);

                $sheet->getStyle("F6:F{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            }
        ];
    }

    public function title(): string
    {
        return 'Incentive Template';
    }
}
