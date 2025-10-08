<?php

namespace App\Exports;

use App\Models\employee_salary_sheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalarySheetExport implements FromCollection, WithHeadings, WithEvents
{
    protected $year;
    protected $month;

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function collection()
    {
        return employee_salary_sheet::select(
            DB::raw("CONCAT('ABNB', employees.Employee_Id) as Employee_Id"),
            'employees.Full_Name',
            'departments.Name as dept_name',
            'designations.Name as des_name',
            'employee_salary_sheets.Month',
            'employee_salary_sheets.Gross_Salary',
            'employee_salary_sheets.Basic',
            'employee_salary_sheets.House_Rent',
            'employee_salary_sheets.Conveyance',
            'employee_salary_sheets.Consolidated',
            'employee_salary_sheets.Festival_Bonus',
            'employee_salary_sheets.Overtime',
            'employee_salary_sheets.Daily_Allowance',
            'employee_salary_sheets.Mobile_Bill',
            'employee_salary_sheets.Other_Allowance',
            'employee_salary_sheets.Total_Payment',
            'employee_salary_sheets.Provident_Fund',
            'employee_salary_sheets.Income_Tax',
            'employee_salary_sheets.Revenue_Stamp',
            'employee_salary_sheets.Penalty_Other_Deduction',
            'employee_salary_sheets.Advance',
            'employee_salary_sheets.Total_Deduction',
            'employee_salary_sheets.Net_Pay',
            'employee_salary_sheets.Bank_Account_No',
        )
        ->join('employees', 'employee_salary_sheets.EID', '=', 'employees.id')
        ->join('officials', 'officials.EID', '=', 'employees.id')
        ->join('departments', 'officials.Department_ID', '=', 'departments.id')
        ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
        ->whereYear('Month', $this->year)
        ->whereMonth('Month', $this->month)
        ->get();
    }

    public function headings(): array
    {
        return [
            [
                "Salary Sheet of " . date("F", mktime(0, 0, 0, $this->month, 1)) . " " . $this->year
            ],
            ["Artisan Business Network Bangladesh"],
            [ // Empty row to separate
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
            ],
            [ // Actual column headers
                'Employee Id',
                'Employee Name',
                'Department Name',
                'Designations Name',
                'Month',
                'Gross_Salary',
                'Basic',
                'House_Rent',
                'Conveyance',
                'Consolidated',
                'Festival_Bonus',
                'Overtime',
                'Daily_Allowance',
                'Mobile_Bill',
                'Other_Allowance',
                'Total_Payment',
                'Provident_Fund',
                'Income_Tax',
                'Revenue_Stamp',
                'Penalty_Other_Deduction',
                'Advance',
                'Loan_Payable',
                'Total_Deduction',
                'Net_Pay',
                'Bank_Account_No',
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Merge the cells for title and subtitle
                $event->sheet->mergeCells('A1:X1');
                $event->sheet->mergeCells('A2:X2');

                // Apply styling
                $event->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getStyle('A2')->getFont()->setSize(12);
                $event->sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}

// class SalarySheetExport implements FromCollection, WithHeadings
// {
//     /**
//     * @return \Illuminate\Support\Collection
//     */

//     protected $year;
//     protected $month;

//     public function __construct($year, $month)
//     {
//         $this->year = $year;
//         $this->month = $month;
//     }

//     public function collection()
//     {
//         return employee_salary_sheet::select(
//             'employees.Employee_Id',
//             'employees.Full_Name',
//             'departments.Name as dept_name',
//             'designations.Name as des_name',
//             'employee_salary_sheets.Month',
//             'employee_salary_sheets.Gross_Salary',
//             'employee_salary_sheets.Basic',
//             'employee_salary_sheets.House_Rent',
//             'employee_salary_sheets.Conveyance',
//             'employee_salary_sheets.Consolidated',
//             'employee_salary_sheets.Festival_Bonus',
//             'employee_salary_sheets.Overtime',
//             'employee_salary_sheets.Daily_Allowance',
//             'employee_salary_sheets.Mobile_Bill',
//             'employee_salary_sheets.Other_Allowance',
//             'employee_salary_sheets.Total_Payment',
//             'employee_salary_sheets.Provident_Fund',
//             'employee_salary_sheets.Income_Tax',
//             'employee_salary_sheets.Revenue_Stamp',
//             'employee_salary_sheets.Penalty_Other_Deduction',
//             'employee_salary_sheets.Advance',
//             'employee_salary_sheets.Total_Deduction',
//             'employee_salary_sheets.Net_Pay',
//             'employee_salary_sheets.Bank_Account_No',
//             )
//             ->join('employees', 'employee_salary_sheets.EID', '=', 'employees.id')
//             ->join('officials', 'officials.EID', '=', 'employees.id')
//             ->join('departments', 'officials.Department_ID', '=', 'departments.id')
//             ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
//             ->whereYear('Month', $this->year)
//             ->whereMonth('Month', $this->month)
//             ->get();
//         // return employee_salary_sheet::whereYear('Month', $this->year)
//         //     ->whereMonth('Month', $this->month)
//         //     ->get();
//     }

//     public function headings(): array
//     {
//         return [
//             'Employee Id',
//             'Employee Name',
//             'Department Name',
//             'Designations Name',
//             'Month',
//             'Gross_Salary',
//             'Basic',
//             'House_Rent',
//             'Conveyance',
//             'Consolidated',
//             'Festival_Bonus',
//             'Overtime',
//             'Daily_Allowance',
//             'Mobile_Bill',
//             'Other_Allowance',
//             'Total_Payment',
//             'Provident_Fund',
//             'Income_Tax',
//             'Revenue_Stamp',
//             'Penalty_Other_Deduction',
//             'Advance',
//             'Total_Deduction',
//             'Net_Pay',
//             'Bank_Account_No',
//         ];
//     }
// }
