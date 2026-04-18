<?php


namespace App\Exports;


use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;


class AttendanceReport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $data;


    public function __construct($data)
    {
        $this->data = $data;
    }


    public function collection()
    {
        return collect($this->data)->map(function ($row) {


            // ✅ time formatter (AM/PM)
            $formatTime = function ($time) {
                if (!$time) return '-';


                try {
                    return Carbon::parse($time)->format('g:i A'); // 12-hour format
                } catch (\Exception $e) {
                    return $time;
                }
            };


            return [
                'Employee ID' => $row->official_id,
                'Employee Name' => $row->employee_name,
                'Department' => $row->department_name,


                'Date' => $row->report_date,
                'Day' => $row->day_name,


                'Status' => $row->attendance_status,


                'In' => $formatTime($row->actual_in),
                'Out' => $formatTime($row->actual_out),


                'In Diff' => $row->in_diff,
                'Out Diff' => $row->out_diff,


                'Working Hours' => $row->working_hours,
            ];
        });
    }


    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',


            'Date',
            'Day',


            'Status',


            'In',
            'Out',


            'In Diff',
            'Out Diff',


            'Working Hours',
        ];
    }
}
