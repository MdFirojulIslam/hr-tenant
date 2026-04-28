<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalaryDisbursementCashExport implements FromArray, WithEvents
{
  protected $data;
  protected $title;
  protected $period;
  protected $subtotal;
  protected $inWords;

  public function __construct($monthName, $year, $period, $subtotal, $inWords)
  {
    $this->data = $monthName;
    $this->title = $year;
    $this->period = $period;
    $this->subtotal = $subtotal;
    $this->inWords = $inWords;
  }

  public function array(): array
  {
    $rows = [];

    // ================= HEADER =================
    $rows[] = ['Date: ' . date('dS F Y')];
    $rows[] = ['ARTISAN BUSINESS NETWORK BANGLADESH'];
    $rows[] = ['Subject: ' . $this->title . ' for ' . $this->period];
    $rows[] = ['Salary Month of ' . $this->monthName . ' ' . $this->year];
    $rows[] = [];
    $rows[] = ['Dear Sir,'];
    $rows[] = ['Please arrange to disburse cash salary as follows:'];
    $rows[] = [];

    // ================= TABLE HEADER =================
    $rows[] = ['SL.No', 'Name', 'Payment Method', 'Amount'];

    // ================= DATA =================
    foreach ($this->data as $index => $row) {
      $rows[] = [
        $index + 1,
        $row->full_name,
        'Cash', // fixed payment method
        number_format($row->net_pay, 2),
      ];
    }

    // ================= TOTAL =================
    $rows[] = ['', '', 'Total', number_format($this->subtotal, 2)];
    $rows[] = [];
    $rows[] = ['Inwords: ' . $this->inWords . ' Taka Only'];
    $rows[] = [];
    $rows[] = ['Yours Sincerely'];
    $rows[] = [];
    $rows[] = ['___________________________'];
    $rows[] = ['Signature & Seal'];

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
        $sheet->getStyle('A10:D10')->getFont()->setBold(true);

        // ================= ROW CALC =================
        $dataStart = 11;
        $dataEnd = $dataStart + count($this->data) - 1;
        $totalRow = $dataEnd + 1;

        // ================= TOTAL BOLD =================
        $sheet->getStyle("A{$totalRow}:D{$totalRow}")
          ->getFont()->setBold(true);

        // ================= ALIGNMENT =================
        $sheet->getStyle("A{$dataStart}:A{$dataEnd}")
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("C{$dataStart}:C{$dataEnd}")
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
      }
    ];
  }
}
