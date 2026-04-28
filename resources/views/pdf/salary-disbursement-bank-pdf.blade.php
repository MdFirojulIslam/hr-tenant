<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <style>
    @page {
      margin: 120px 50px 80px 50px;
      counter-increment: page;
    }

    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
    }

    /* Header only on first page */
    header {
      position: fixed;
      top: -100px;
      left: 0;
      right: 0;
    }

    .subject {
      margin-bottom: 20px;
    }

    .date-gap {
      margin-bottom: 10px;
    }

    .previous-header-gap {
      margin-bottom: 15px;
    }

    .header-gap {
      margin-bottom: 5px;
    }

    .dear-gap {
      margin-bottom: 7px;
    }

    .arrange-gap {
      margin-bottom: 20px;
    }

    .first-page-header {
      display: block;
    }



    /* Main table */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      page-break-inside: auto;
    }

    tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }

    th,
    td {
      border: 1px solid #000;
      padding: 6px;
      font-size: 11px;
    }

    th {
      background-color: #f2f2f2;
      text-align: center;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .signature {
      margin-top: 40px;
    }

    .make-uppercase {
      text-transform: capitalize;
    }

    .signature-gap {
      margin-bottom: 40px;
    }

    p {
      margin: 0;
    }
  </style>
</head>

<body>

  <!-- Section 1: Header content (only once) -->
  <div class="first-page-header">
    <p class="date-gap">Date: {{ \Carbon\Carbon::now()->format('dS F Y') }}</p>

    <p class="header-gap">
      The Manager <br></p>
    <p class="header-gap">The City Bank Ltd <br></p>
    <p class="previous-header-gap">Gulshan Branch, Dhaka </p>

    <p class="subject"><strong>Subject:</strong> Requesting to Disburse Salary for the month of {{ $monthName }} {{ $year }}.</p>

    <p class="dear-gap">Dear Sir,</p>

    <p class="arrange-gap">
      Please arrange to transfer the salary to our individual staff account as follows:
    </p>
  </div>

  <!-- Section 2: Table -->
  <table>
    <thead>
      <tr>
        <th width="10%">SL.No</th>
        <th width="30%">Name</th>
        <th width="30%">Bank AC No</th>
        <th width="15%">Amount</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data as $row)
      <tr>
        <td class="text-center">{{ $loop->iteration }}</td>
        <td>{{ $row->full_name }}</td>
        <td class="text-center">{{ $row->bank_account_no }}</td>
        <td class="text-center">{{ number_format($row->net_pay, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th colspan="3" class="text-right">Total</th>
        <th class="text-right">{{ number_format($grandTotal, 2) }}</th>
      </tr>
    </tfoot>
  </table>

  <!-- Section 3: Footer / Signature (only once) -->
  <p style="margin-top: 25px;">
    <strong class="make-uppercase"> Inwords: {{ $grandTotalInWords }} Taka Only</strong>
  </p>
  <p style="margin-top: 30px;">
    According to the statement mentioned above, you are requested to debit from our
    Artisan Business Network Bangladesh,
    A/C No.: <strong>3101496682001</strong>
  </p>

  <div class="signature">
    <p class="signature-gap">Yours Sincerely</p>
    <br><br>
    ___________________________<br>
    Signature & Seal
  </div>

</body>


</html>
