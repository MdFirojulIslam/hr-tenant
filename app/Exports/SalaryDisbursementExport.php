<?php


namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalaryDisbursementExport implements FromArray, WithHeadings, WithEvents
{
  protected $data;
  protected $monthName;
  protected $year;
  protected $subtotal;

  public function __construct($data, $monthName, $year, $subtotal)
  {
    $this->data = $data;
    $this->monthName = $monthName;
    $this->year = $year;
    $this->subtotal = $subtotal;
  }

  public function headings(): array
  {
    return [
      ['Salary Disbursement Bank Report'],
      ["Month: {$this->monthName} {$this->year}"],
      [], // spacing row
      ['SL', 'Employee Name', 'Bank Account', 'Net Salary'],
    ];
  }

  public function array(): array
  {
    $rows = [];

    foreach ($this->data as $index => $item) {
      $rows[] = [
        $index + 1,
        $item->full_name ?? '',
        $item->bank_account_no ?? '',
        number_format($item->net_pay ?? 0, 2),
      ];
    }

    // Total row
    $rows[] = [
      '',
      'Grand Total',
      '',
      number_format($this->subtotal, 2),
    ];

    return $rows;
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {

        $sheet = $event->sheet;

        // ===== HEADER MERGE =====
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');

        // Center header
        $sheet->getStyle('A1:A2')
          ->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Bold title
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        // ===== TABLE HEADER STYLE =====
        $sheet->getStyle('A4:D4')->getFont()->setBold(true);
        $sheet->getStyle('A4:D4')
          ->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ===== DATA ALIGNMENT =====
        $lastRow = $this->data->count() + 5;

        $sheet->getStyle("A5:A{$lastRow}")
          ->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("D5:D{$lastRow}")
          ->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ===== GRAND TOTAL ROW =====
        $sheet->mergeCells("A{$lastRow}:C{$lastRow}");

        $sheet->getStyle("A{$lastRow}:D{$lastRow}")
          ->getFont()
          ->setBold(true);

        $sheet->getStyle("A{$lastRow}")
          ->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("D{$lastRow}")
          ->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
      }
    ];
  }
}




// namespace App\Exports;

// use Maatwebsite\Excel\Concerns\FromArray;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Events\AfterSheet;

// class SalaryDisbursementExport implements FromArray, WithHeadings, WithEvents
// {
//   protected $data;
//   protected $title;
//   protected $monthName;
//   protected $subtotal;

//   public function __construct($data, $title, $monthName, $subtotal)
//   {
//     $this->data = $data;
//     $this->title = $title;
//     $this->monthName = $monthName;
//     $this->subtotal = $subtotal;
//   }

//   public function array(): array
//   {
//     $rows = [];

//     foreach ($this->data as $item) {
//       $rows[] = [
//         'ABNB-' . ($item->employee_id ?? ''),
//         $item->full_name ?? '',
//         "" . ($item->bank_account_no ?? ''),
//         number_format($item->net_pay ?? 0, 2),
//       ];
//     }
//     $rows[] = [
//       'Grand Total :',
//       '',
//       '',
//       number_format($this->subtotal ?? 0, 2)
//     ];

//     return $rows;
//   }

//   public function headings(): array
//   {
//     return [
//       [$this->title],
//       ["Month: {$this->monthName}"],
//       [],
//       ['Employee ID', 'Employee Name', 'Bank Account', 'Net Salary'],
//     ];
//   }

//   public function registerEvents(): array
//   {
//     return [
//       AfterSheet::class => function (AfterSheet $event) {

//         $event->sheet->mergeCells('A1:D1');
//         $event->sheet->mergeCells('A2:D2');

//         $event->sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
//         $event->sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

//         $event->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
//         $event->sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

//         $event->sheet->getRowDimension(2)->setRowHeight(20);

//         $event->sheet->getStyle('A4:D4')->getFont()->setBold(true);

//         $event->sheet->getStyle('D4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

//         $lastRow = count($this->data) + 5;
//         $event->sheet->mergeCells("A{$lastRow}:C{$lastRow}");

//         $event->sheet->getStyle("A{$lastRow}:D{$lastRow}")->getFont()->setBold(true);

//         $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

//         $event->sheet->getStyle("D{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
//       },
//     ];
//   }
// }
