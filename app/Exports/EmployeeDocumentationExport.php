<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class EmployeeDocumentationExport implements
    FromCollection,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle
{
    protected $employeeId;

    protected $documents = [
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

    protected $employeeBlocks = [];

    public function __construct($employeeId = null)
    {
        $this->employeeId = $employeeId;
    }

    public function collection()
    {
        $query = DB::table('emp_info_documentations')
            ->join('employees', 'emp_info_documentations.employee_id', '=', 'employees.id')
            ->leftJoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftJoin('departments', 'departments.id', '=', 'officials.Department_Id')
            ->leftJoin('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->select(
                'employees.Employee_Id as employee_code',
                'employees.Full_Name as employee_name',
                'departments.Name as department_name',
                'designations.Name as designation_name',
                'emp_info_documentations.completed_percent',
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
                'emp_info_documentations.pa_pp_address'
            )
            ->orderBy('employees.Employee_Id');

        if ($this->employeeId) {
            $query->where('emp_info_documentations.employee_id', $this->employeeId);
        }

        $records = $query->get();

        $rows = [];
        $this->employeeBlocks = [];

        $rows[] = ['Employee Documentation Report', ''];

        foreach ($records as $emp) {
            $rowOffset = count($rows);
            $code = 'ABNB' . $emp->employee_code;

            $rows[] = ['Employee Code', $code];
            $rows[] = ['Employee Name', $emp->employee_name];
            $rows[] = ['Department', $emp->department_name ?? ''];
            $rows[] = ['Designation', $emp->designation_name ?? ''];
            $rows[] = ['Completed Percent', $emp->completed_percent . '%'];
            $rows[] = ['Document Name', 'Status'];

            foreach ($this->documents as $field => $label) {
                $rows[] = [$label, $this->statusLabel($emp->{$field})];
            }

            // Row numbers in sheet (1-indexed). Collection index i => sheet row i+1.
            $this->employeeBlocks[] = [
                'headerRow' => $rowOffset + 6,
                'docCount'  => count($this->documents),
            ];
        }

        return collect($rows);
    }

    protected function statusLabel($value)
    {
        if ($value === 'Yes') {
            return 'Available';
        }
        if ($value === 'Not Applicable') {
            return 'Not Applicable';
        }
        return 'Missing';
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $lastRow = $sheet->getHighestRow();

                $sheet->mergeCells("A1:B1");
                $sheet->setCellValue('A1', 'Employee Documentation Report');
                $sheet->getStyle('A1')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(14);
                $sheet->getStyle('A1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("A1:B{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                foreach ($this->employeeBlocks as $block) {
                    $hr = $block['headerRow'];
                    $dStart = $hr + 1;
                    $dEnd = $hr + $block['docCount'];

                    $sheet->getStyle("A{$hr}:B{$hr}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB('D9E1F2');
                    $sheet->getStyle("A{$hr}:B{$hr}")
                        ->getFont()
                        ->setBold(true);

                    $infoStart = $hr - 5;
                    $infoEnd = $hr - 1;
                    for ($r = $infoStart; $r <= $infoEnd; $r++) {
                        $sheet->getStyle("A{$r}")->getFont()->setBold(true);
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Documentation Report';
    }
}
