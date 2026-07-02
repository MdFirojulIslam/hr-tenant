<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
// Ei namespace gulo thikmoto thaka khub-i jaruri
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalaryDisbursementBankExport extends DefaultValueBinder implements FromArray, WithEvents, WithColumnFormatting, WithCustomValueBinder
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

  /**
   * BindValue method signature must match exactly
   */
  public function bindValue(Cell $cell, $value)
  {
    // Column C holo Bank Account No.
    // string-e cast kore explicit set korle exponential ashbe na.
    if ($cell->getColumn() == 'C' && (is_numeric($value) || is_string($value))) {
      $cell->setValueExplicit($value, DataType::TYPE_STRING);
      return true;
    }

    // Default behavior for other cells
    return parent::bindValue($cell, $value);
  }

  public function columnFormats(): array
  {
    return [
      'C' => NumberFormat::FORMAT_TEXT,
    ];
  }

  public function array(): array
  {
    $rows = [];

    // Header and layout
    $rows[] = ['Date: ' . date('d F Y')];
    $rows[] = ['The Manager'];
    $rows[] = ['The City Bank Ltd'];
    $rows[] = ['Gulshan Branch, Dhaka'];
    $rows[] = ['Subject: Requesting to Disburse Salary for the month of ' . $this->monthName . ' ' . $this->year];
    $rows[] = ['Dear Sir,'];
    $rows[] = ['Please arrange to transfer the salary to our individual staff account as follows:'];

    // Table Header
    $rows[] = ['SL.No', 'Name', 'Bank AC No', 'Amount'];

    // Data loop
    foreach ($this->data as $index => $row) {
      $rows[] = [
        $index + 1,
        $row->full_name,
        (string)$row->bank_account_no, // Explicit string casting
        number_format($row->final_payments, 2),
      ];
    }

    // Footer
    $rows[] = ['', '', 'Total', number_format($this->subtotal, 2)];
    $rows[] = [];
    $rows[] = ['Inword: ' . $this->inWords . ' Taka Only'];
    $rows[] = [];
    $rows[] = ['According to above mention statement, you are requested to debit from our Artisan Business Network Bangladesh, A/C No.: 3101496682001'];
    $rows[] = [];
    $rows[] = ['Your Sincerely'];
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
        $dataStart = 8;
        $lastRow = $dataStart + count($this->data);
        $total = $lastRow + 1;
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(15);

        // $sheet->getStyle('A6')->getFont()->setBold(true);
        $sheet->getStyle('A8:D8')->getFont()->setBold(true);
        $sheet->getStyle("A{$total}:D{$total}")->getFont()->setBold(true);

        $sheet->getStyle("A{$dataStart}:A{$lastRow}")
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C{$dataStart}:C{$lastRow}")
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
      },
    ];
  }
}
