<?php


namespace App\Exports;


use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;


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

            return [
                'Employee ID' => 'ABNB' . $row->official_id,
                'Employee Name' => $row->employee_name,
                'Department' => $row->department_name,
                'Date' => $row->date,
                'Day' => $row->day_name,
                'Status' => $row->attendance_status,
                'In' => $row->fmt_in,
                'Out' => $row->fmt_out,
                'In Diff' => $row->in_diff,
                'Out Diff' => $row->out_diff,
                'Working Hours' => $row->working_hours,
                'Rostered In' => $row->fmt_rostered_in,
                'Rostered Out' => $row->fmt_rostered_out,
                'Rostered Status' => $row->rostered_status,
                'Remarks' => $row->remarks,
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
            'Rostered In',
            'Rostered Out',
            'Rostered Status',
            'Remarks',
        ];
    }
}