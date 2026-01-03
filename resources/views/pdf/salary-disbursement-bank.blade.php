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

    .first-page-header {
      display: block;
    }

    /* Footer */
    footer {
      position: fixed;
      bottom: -60px;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 10px;
    }

    footer .page:after {
      content: counter(page) " of " counter(pages);
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
      margin-top: 80px;
    }

    p {
      margin: 0;
    }
  </style>
</head>

<body>

  <!-- Section 1: Header content (only once) -->
  <div class="first-page-header">
    <p>Date: {{ \Carbon\Carbon::now()->format('dS F Y') }}</p>

    <p>
      The Manager <br>
      The City Bank Ltd <br>
      Gulshan Branch, Dhaka
    </p>

    <p><strong>Subject:</strong> Requesting to Disburse Salary for the month of {{ $monthName }} {{ $year }}.</p>

    <p>Dear Sir,</p>

    <p>
      Please arrange to transfer the salary to our individual staff account as follows:
    </p>
  </div>

  <!-- Section 2: Table -->
  <table>
    <thead>
      <tr>
        <th width="10%">EID</th>
        <th width="30%">Name</th>
        <th width="30%">Bank A/C No</th>
        <th width="15%">Amount</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data as $row)
      <tr>
        <td class="text-center">ABNB{{ $row->employee_id }}</td>
        <td>{{ $row->full_name }}</td>
        <td class="text-center">{{ $row->bank_account_no }}</td>
        <td class="text-right">{{ number_format($row->net_pay, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- Section 3: Footer / Signature (only once) -->
  <p style="margin-top: 25px;">
    According to the statement mentioned above, you are requested to debit from our
    Artisan Business Network Bangladesh,
    A/C No.: <strong>3101496682001</strong>
  </p>

  <div class="signature">
    <p>Yours Sincerely</p>
    <br><br>
    ___________________________<br>
    Signature & Seal
  </div>

  <!-- Page numbers -->
  <footer>
    <span class="page">Page </span>
  </footer>
</body>


</html>
