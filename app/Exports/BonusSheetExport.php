<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BonusSheetExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle
{
    protected $year;
    protected $month;
    protected $totalBonus = 0;

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function collection()
    {
        $data = DB::table('bonus_sheets')
            ->join('bonuses', 'bonuses.id', '=', 'bonus_sheets.bonus_id')
            ->join('employees', 'employees.id', '=', 'bonus_sheets.employee_id')
            ->join('officials', 'officials.EID', '=', 'bonus_sheets.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->whereYear('bonus_sheets.bonus_date', $this->year)
            ->whereMonth('bonus_sheets.bonus_date', $this->month)
            ->select(
                DB::raw("CONCAT('ABNB-', employees.Employee_Id) as Employee_ID"),
                'employees.Full_Name as Employee_Name',
                'departments.Name as Department',
                'officials.DOJ as Date_of_Join', // ✅ new column
                'bonus_sheets.gross_salary as Gross_Salary',
                'bonus_sheets.basic_salary as Basic_Salary',
                'bonus_sheets.bonus_percentages as Percentage',
                'bonus_sheets.bonus_amounts as Bonus_Amount',
                'bonuses.name as Festival_Name',
                DB::raw("COALESCE(bonus_sheets.bank_account_no, 'Cash') as Bank_Account"),
            )
            ->get();

        $this->totalBonus = $data->sum('Bonus_Amount');

        return $data;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Date of Join',
            'Gross Salary',
            'Basic Salary',
            'Bonus %',
            'Bonus Amount',
            'Festival',
            'Bank Account'
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

                // Convert month number to month name
                $monthName = date("F", mktime(0, 0, 0, $this->month, 1));

                // Insert Header Rows
                $sheet->insertNewRowBefore(1, 2);

                // Company Name
                $sheet->setCellValue('A1', 'Artisan Business Network Bangladesh');
                $sheet->mergeCells('A1:J1');

                // Sheet Title
                $sheet->setCellValue('A2', 'Bonus Sheet - ' . $monthName . ' ' . $this->year);
                $sheet->mergeCells('A2:J2');

                // Style for headers
                $sheet->getStyle('A1:J2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1:J2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Header background
                $sheet->getStyle('A3:J3')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('D9E1F2');

                $lastRow = $sheet->getHighestRow();

                // Currency format for Gross and Basic Salary
                $sheet->getStyle("E4:F{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // Bonus Amount column
                $sheet->getStyle("H4:H{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // Percentage format
                $sheet->getStyle("G4:G{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER);

                // Add total row
                $totalRow = $lastRow + 1;
                $sheet->setCellValue("G{$totalRow}", 'Total Bonus');
                $sheet->setCellValue("H{$totalRow}", $this->totalBonus);
                $sheet->getStyle("G{$totalRow}:H{$totalRow}")->getFont()->setBold(true);

                // FULL TABLE BORDER
                $sheet->getStyle("A3:J{$totalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }

    public function title(): string
    {
        return 'Bonus Sheet';
    }
}
