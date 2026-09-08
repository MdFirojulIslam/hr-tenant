<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class EmployeeDocumentationHorizontalExport implements
    FromCollection,
    ShouldAutoSize,
    WithHeadings,
    WithStyles,
    WithEvents,
    WithTitle
{
    protected $documentFields = [
        'job_circular'            => 'Job Circular',
        'interview_evalation'     => 'Interview Evaluation / Notes',
        'appointment_letter'      => 'Appointment Letter',
        'updated_resume'          => 'Updated Resume / CV',
        'nid_passport'            => 'NID or Passport',
        'academic_cert'           => 'Academic Certificates',
        'post_graduate_cert'      => 'Graduation / Postgraduate Certificates',
        'prev_cmp_exp_letter'     => 'Experience Letters (Previous Employment)',
        'prev_cmp_release_letter' => 'Release Letters (Previous Company)',
        'prev_cmp_salary_cert'    => 'Salary Certificate (Previous Company)',
        'recent_passport_pic'     => 'Recent Passport-size Photograph',
        'two_emegency_info'       => 'Two Emergency Contacts',
        'nid_verified'            => 'NID Verified',
        'job_description'         => 'Job Description',
        'joining_letter'          => 'Joining Letter',
        'kye_form'                => 'KYE Form',
        'emp_declation_letter'    => 'Employee Declaration Letter',
        'pa_pp_address'           => 'Present & Permanent Address Declaration',
    ];

    public function headings(): array
    {
        $headings = [
            'Employee Code',
            'Employee Name',
            'Department',
            'Designation',
        ];

        foreach ($this->documentFields as $label) {
            $headings[] = $label;
        }

        $headings[] = 'Completed Percent';
        $headings[] = 'Status';

        return $headings;
    }

    public function collection()
    {
        $employees = DB::table('employees')
            ->leftJoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftJoin('departments', 'departments.id', '=', 'officials.Department_Id')
            ->leftJoin('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->leftJoin('emp_info_documentations', 'emp_info_documentations.employee_id', '=', 'employees.id')
            ->select(
                'employees.Employee_Id as employee_code',
                'employees.Full_Name as employee_name',
                'departments.Name as department_name',
                'designations.Name as designation_name',
                'officials.Status as status',
                'emp_info_documentations.job_circular',
                'emp_info_documentations.interview_evalation',
                'emp_info_documentations.appointment_letter',
                'emp_info_documentations.updated_resume',
                'emp_info_documentations.nid_passport',
                'emp_info_documentations.academic_cert',
                'emp_info_documentations.post_graduate_cert',
                'emp_info_documentations.prev_cmp_exp_letter',
                'emp_info_documentations.prev_cmp_release_letter',
                'emp_info_documentations.prev_cmp_salary_cert',
                'emp_info_documentations.recent_passport_pic',
                'emp_info_documentations.two_emegency_info',
                'emp_info_documentations.nid_verified',
                'emp_info_documentations.job_description',
                'emp_info_documentations.joining_letter',
                'emp_info_documentations.kye_form',
                'emp_info_documentations.emp_declation_letter',
                'emp_info_documentations.pa_pp_address',
                'emp_info_documentations.completed_percent'
            )
            ->where('officials.Status', '=', 'N')
            ->orderBy('employees.Full_Name', 'asc')
            ->get();

        $rows = [];

        foreach ($employees as $emp) {
            $row = [];
            $row[] = 'ABNB' . $emp->employee_code;
            $row[] = $emp->employee_name;
            $row[] = $emp->department_name ?? '';
            $row[] = $emp->designation_name ?? '';

            foreach (array_keys($this->documentFields) as $field) {
                $row[] = $this->docLabel($emp->{$field});
            }

            $row[] = $emp->completed_percent !== null ? $emp->completed_percent . '%' : '0%';
            $row[] = $emp->status === 'N' ? 'Active' : 'Inactive';

            $rows[] = $row;
        }

        return collect($rows);
    }

    protected function docLabel($value)
    {
        if ($value === 'Yes') {
            return 'Yes';
        }
        if ($value === 'Not Applicable') {
            return 'Not Applicable';
        }
        return 'No';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'D9E1F2'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:{$lastCol}1")
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle("A1:{$lastCol}1")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('D9E1F2');

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $statusCol = $lastCol;
                for ($row = 2; $row <= $lastRow; $row++) {
                    $statusValue = $sheet->getCell("{$statusCol}{$row}")->getValue();
                    if ($statusValue === 'Inactive') {
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                            ->getFont()
                            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Documentation Export';
    }
}
