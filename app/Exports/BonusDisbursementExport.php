<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BonusDisbursementExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $data;
    protected $title;
    protected $monthYear;
    protected $subtotal;

    public function __construct($data, $title, $monthYear, $subtotal)
    {
        $this->data      = $data;
        $this->title     = $title;
        $this->monthYear = $monthYear;
        $this->subtotal  = $subtotal;
    }

    public function collection()
    {
        $rows = collect();

        foreach ($this->data as $item) {
            $rows->push([
                $item->employee_id,
                $item->full_name,
                $item->bank_account_no,
                number_format($item->bonus_amounts, 2),
            ]);
        }

        // Add subtotal row
        $rows->push([
            '',
            '',
            'TOTAL',
            number_format($this->subtotal, 2),
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Bank A/C / Payment Type',
            'Bonus Amount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // headings bold
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                // Insert title row at top
                $event->sheet->insertNewRowBefore(1, 2);

                $event->sheet->setCellValue('A1', $this->title);
                $event->sheet->setCellValue('A2', $this->monthYear);

                $event->sheet->mergeCells('A1:D1');
                $event->sheet->mergeCells('A2:D2');

                $event->sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
                $event->sheet->getStyle('A2')->getFont()->setSize(12)->setBold(true);

                // Center align title
                $event->sheet->getStyle('A1:D2')
                    ->getAlignment()
                    ->setHorizontal('center');

                // Bold total row
                $lastRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle("A{$lastRow}:D{$lastRow}")
                    ->getFont()
                    ->setBold(true);
            }
        ];
    }
}
