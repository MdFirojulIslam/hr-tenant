<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalaryDisbursementCashExport implements FromArray, WithEvents
{
  protected $data;
  protected $monthName;
  protected $year;
  protected $subtotal;
  protected $inWords;

  public function __construct($data, $monthName, $year, $subtotal, $inWords)
  {
    $this->data = $data;
    $this->monthName = $monthName;
    $this->year = $year;
    $this->subtotal = $subtotal;
    $this->inWords = $inWords;
  }

  public function array(): array
  {
    $rows = [];

    // ================= HEADER =================
    $rows[] = ['Salary Month of ' . $this->monthName . ' ' . $this->year];
    $rows[] = ['ARTISAN BUSINESS NETWORK BANGLADESH'];


    // ================= TABLE HEADER =================
    $rows[] = ['SL.No', 'Name', 'Designation', 'Payment Method', 'Amount', 'Signature'];

    // ================= DATA =================
    foreach ($this->data as $index => $row) {
      $rows[] = [
        $index + 1,
        $row->full_name,
        $row->designation,
        'Cash', // fixed payment method
        number_format($row->final_payments, 2),
        ''
      ];
    }

    // ================= TOTAL =================
    $rows[] = ['', '', '', 'Total', number_format($this->subtotal, 2), ''];
    $rows[] = [];
    $rows[] = ['Taka: ' . $this->inWords . ' Taka Only'];
    $rows[] = [];
    $rows[] = ['Recommended by', '', '', '', '', 'Approved by CEO/Managing Director'];

    return $rows;
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {

        $sheet = $event->sheet;

        // ================= COLUMN WIDTH =================
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);

        // ================= HEADER STYLE =================
        $sheet->getStyle('A3:D3')->getFont()->setBold(true);

        // ================= ROW CALC =================
        $dataStart = 4;
        $dataEnd = $dataStart + count($this->data) - 1;
        $totalRow = $dataEnd + 1;
        $totalRowEnd = $totalRow + 1;
        // ================= TOTAL BOLD =================
        $sheet->getStyle("A{$totalRowEnd}:D{$totalRowEnd}")
          ->getFont()->setBold(true);

        // ================= ALIGNMENT =================
        $sheet->getStyle("A{$dataStart}:A{$totalRow}")
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("C{$dataStart}:C{$dataEnd}")
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
      }
    ];
  }
}
