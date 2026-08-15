<?php

namespace App\Exports;

use App\Models\attendence;
use App\Models\employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Events\AfterSheet;

class AttendanceReportExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year  = $year;
    }

    public function collection()
    {
        $startDate     = Carbon::createFromDate($this->year, $this->month, 1);
        $daysInMonth   = $startDate->daysInMonth;

        $employees = employee::with([
            'official',              // parent relation
            'official.department',   // nested relations
            'official.designation',
            'official.branch',
        ])
            ->orderBy('Full_Name', 'asc')
            ->get();

        $data = [];

        foreach ($employees as $emp) {
            $row = [
                $emp->id,
                $emp->Employee_Id,
                $emp->Full_Name,
                $emp->official->department->Name ?? '',
                $emp->official->designation->Name ?? '',
                $emp->official->branch->Name ?? '',
            ];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::create($this->year, $this->month, $day)->toDateString();

                $att = Attendence::where('EID', $emp->id)
                    ->whereDate('Date', $date)
                    ->first();

                dd($emp->Full_Name, $att);

                if ($att) {
                    $timeIn  = $att->Time_In  ? Carbon::parse($att->Time_In)->format('H:i')  : '';
                    $timeOut = $att->Time_Out ? Carbon::parse($att->Time_Out)->format('H:i') : '';
                    $row[] = $timeIn && $timeOut ? "$timeIn - $timeOut" : ($timeIn ?: $timeOut);
                } else {
                    $row[] = '';   // absent / no record
                }
            }

            $data[] = $row;
        }
        dd($data);

        return collect($data);
    }
    public function headings(): array
    {
        $start = Carbon::create($this->year, $this->month, 1);
        $days = [];

        for ($i = 1; $i <= $start->daysInMonth; $i++) {
            $days[] = $i;
        }

        return array_merge([
            'Id',
            'Employee Id',
            'Employee Name',
            'Department',
            'Designation',
            'Branch'
        ], $days);
    }

    public function title(): string
    {
        return 'Attendance Report';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Styling (optional)
                $event->sheet->getDelegate()->getStyle('A1:Z1')->getFont()->setBold(true);
            }
        ];
    }
}
