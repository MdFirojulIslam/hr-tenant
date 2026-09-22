<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MeetingMinutesExport implements FromArray, ShouldAutoSize, WithEvents
{
    protected $rows;

    protected $template;

    public function __construct(array $rows, string $template)
    {
        $this->rows = $rows;
        $this->template = $template;
    }

    public function array(): array
    {
        if ($this->template === 'town_wise_meeting_view') {
            return $this->buildGroupedView('town_name', 'Town: ');
        }

        if ($this->template === 'tso_wise_meeting_view') {
            return $this->buildGroupedView('employee_name', 'TSO: ');
        }

        return $this->buildMasterView();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Employee',
            'Department',
            'Town',
            'Start Time',
            'End Time',
            'Meeting With',
            'Minutes of Meeting',
            'File',
            'Created At',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                $headings = $this->headings();

                for ($row = 1; $row <= $highestRow; $row++) {
                    $firstCell = $sheet->getCell("A{$row}")->getValue();
                    $isHeadingRow = is_string($firstCell) && in_array($firstCell, $headings, true);
                    $isGroupHeader = is_string($firstCell) && (strpos($firstCell, 'Town: ') === 0 || strpos($firstCell, 'TSO: ') === 0);

                    if ($isHeadingRow || $isGroupHeader) {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFont()->setBold(true);
                    }

                    if ($isGroupHeader) {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('D9E1F2');
                    }
                }
            },
        ];
    }

    protected function buildMasterView(): array
    {
        $out = [$this->headings()];

        foreach ($this->rows as $row) {
            $out[] = $this->buildRecordRow($row);
        }

        return $out;
    }

    protected function buildGroupedView(string $groupKey, string $prefix): array
    {
        $out = [];

        foreach ($this->groupRows($groupKey) as $groupLabel => $items) {
            $out[] = [$prefix . $groupLabel];
            $out[] = $this->headings();

            foreach ($items as $row) {
                $out[] = $this->buildRecordRow($row);
            }

            $out[] = [''];
        }

        return $out;
    }

    protected function groupRows(string $groupKey): array
    {
        $grouped = [];

        foreach ($this->rows as $row) {
            $label = $row[$groupKey] ?? '-';
            $grouped[$label][] = $row;
        }

        ksort($grouped, SORT_STRING);

        return $grouped;
    }

    protected function buildRecordRow(array $row): array
    {
        return [
            $row['date'] ?? '-',
            $row['employee_name'] ?? '-',
            $row['department_name'] ?? '-',
            $row['town_name'] ?? '-',
            $row['str_time'] ?? '-',
            $row['end_time'] ?? '-',
            $row['meeting_with'] ?? '-',
            $row['mom'] ?? '-',
            $row['file_url'] ?? '-',
            $row['created_at'] ?? '-',
        ];
    }
}