<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalaryDisbursementExport implements FromArray, WithHeadings, WithEvents
{
  protected $data;
  protected $title;
  protected $monthName;
  protected $subtotal;

  public function __construct($data, $title, $monthName, $subtotal)
  {
    $this->data = $data;
    $this->title = $title;
    $this->monthName = $monthName;
    $this->subtotal = $subtotal;
  }

  public function array(): array
  {
    $rows = [];

    foreach ($this->data as $item) {
      $rows[] = [
        'ABNB-' . ($item->employee_id ?? ''),
        $item->full_name ?? '',
        "" . ($item->bank_account_no ?? ''),
        number_format($item->net_pay ?? 0, 2),
      ];
    }

    // Grand Total row: first 3 cells merged
    $rows[] = [
      'Grand Total :',
      '',
      '',
      number_format($this->subtotal ?? 0, 2)
    ];

    return $rows;
  }

  public function headings(): array
  {
    return [
      [$this->title],                     // Row 1: Title
      ["Month: {$this->monthName}"],      // Row 2: Month
      [],                                 // Row 3: Empty row for spacing
      ['Employee ID', 'Employee Name', 'Bank Account', 'Net Salary'], // Row 4: Headers
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {

        // Merge title and month across all 4 columns
        $event->sheet->mergeCells('A1:D1');
        $event->sheet->mergeCells('A2:D2');

        // Center align title and month
        $event->sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $event->sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Bold title and month
        $event->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $event->sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        // Add row height for month to create a gap
        $event->sheet->getRowDimension(2)->setRowHeight(20);

        // Bold column headers
        $event->sheet->getStyle('A4:D4')->getFont()->setBold(true);

        // Right align column headers (optional)
        $event->sheet->getStyle('D4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Grand Total styling
        $lastRow = count($this->data) + 5; // +5: title + month + empty + header = 4 rows offset
        $event->sheet->mergeCells("A{$lastRow}:C{$lastRow}"); // Merge first 3 columns

        // Bold Grand Total text and value
        $event->sheet->getStyle("A{$lastRow}:D{$lastRow}")->getFont()->setBold(true);

        // Right align "Grand Total:" text in merged cells
        $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Right align total value in D column
        $event->sheet->getStyle("D{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
      },
    ];
  }
}
