<?php

namespace App\Exports;

use App\Models\salary_sheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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

        //         ss.salary_date, ss.gross_salary, ss.basic_salary, ss.house_rent, ss.conveyance, ss.medical_allowance, ss.consolidated, ss.festival_bonus, ss.travel_allowance,
        // ss.mobile_bill, ss.other_allowance, ss.total_payment, ss.provident_fund, ss.income_tax, ss.revenue_stamp, ss.earning_adjustment, ss.deduction_adjustment,
        // ss.monthly_loan_repayments, ss.loan_balance_after, ss.present_days, ss.absent_days, ss.late_days, ss.net_pay, ss.salary_status, ss.payment_method, ss.bank_account_no

        return salary_sheet::select(
            DB::raw("CONCAT('ABNB', employees.Employee_Id) as Employee_Id"), // 'EID',
            'employees.Full_Name',  // 'Name',
            'officials.DOJ', // 'Date Of Join',
            // 'Absent',
            'salary_sheets.absent_days',
            'salary_sheets.gross_salary', // 'Gross Salary',
            'salary_sheets.basic_salary', // 'Basic Salary',
            'salary_sheets.house_rent',   // 'House Rent',
            'salary_sheets.conveyance', // 'Conveyance',
            'salary_sheets.medical_allowance', // 'Medical Allowance',
            'salary_sheets.consolidated',  // 'Consolidated',
            'salary_sheets.festival_bonus', // 'Festival Bonus',v
            'salary_sheets.travel_allowance', // 'Travel Allowance',
            'salary_sheets.earning_adjustment', // 'Overtime/Adjustment',
            'salary_sheets.daily_allowance', // 'Daily Allowance',
            'salary_sheets.mobile_bill', // 'Mobile Bill Allowance',
            'salary_sheets.other_allowance', // 'Other Allowance',
            'salary_sheets.total_payment', // 'Total Payment',
            'salary_sheets.provident_fund', // 'Provident Fund',
            'salary_sheets.income_tax', // 'Income Tax',
            'salary_sheets.revenue_stamp', // 'Revenue Stamp',
            'salary_sheets.leave_without_pay', // 'Leave Without Pay',
            'salary_sheets.deduction_adjustment', // 'Advance/Other Deduction',
            'salary_sheets.total_deduction', // 'Total Deduction',
            'salary_sheets.monthly_loan_repayments', // 'Monthly_Loan_Repayments',
            'salary_sheets.net_pay',  // 'Net_Pay',
            'salary_sheets.payment_method', // 'Payment Method',
            'salary_sheets.bank_account_no', // 'Bank Account No',

        )
            ->join('employees', 'salary_sheets.employee_id', '=', 'employees.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_ID', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->whereYear('salary_date', $this->year)
            ->whereMonth('salary_date', $this->month)
            ->get()
            ->map(function ($row) {
                // Format DOJ to 30 May 2025
                $row->DOJ = Carbon::parse($row->DOJ)->format('d M Y');
                return $row;
            });;
    }

    public function headings(): array
    {
        return [
            [
                "Salary Sheet of " . date("F", mktime(0, 0, 0, $this->month, 1)) . " " . $this->year
            ],
            ["Artisan Business Network Bangladesh"],
            [ // Empty row to separate
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                'EID',
                'Name',
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
                'Mobile Bill Allowance',
                'Other Allowance',
                'Total Payment',
                'Provident Fund',
                'Income Tax',
                'Revenue Stamp',
                'Leave Without Pay',
                'Advance/Other Deduction',
                'Total Deduction',
                'Monthly_Loan_Repayments',
                'Net_Pay',
                'Payment Method',
                'Bank Account No',
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
