<!-- <!DOCTYPE html>
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
        <td class="text-center">{{ $row->payment_method }}</td>
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


</html> -->





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

    .first-page-header {
      text-align: center;
      margin-bottom: 30px;
    }


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

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      page-break-inside: auto;
    }

    thead {
      display: table-header-group;
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

    .make-uppercase {
      text-transform: capitalize;
    }

    .signature-row {
      display: flex;
      justify-content: space-between;
      margin-top: 100px;
    }

    .left {
      text-align: left;
    }

    .right {
      text-align: right;
    }

    p {
      margin: 0;
    }
  </style>
</head>

<body>

  <div class="first-page-header">
    <h3>Salary for the month of {{ $monthName }} {{ $year }}</h3>
    <h4>Artisan Business Network Bangladesh</h4>
  </div>

  <table>
    <thead>
      <tr>
        <th width="7%">SL.No</th>
        <th width="25%">Name</th>
        <th width="27%">Designation</th>
        <th width="18%">Payment Method</th>
        <th width="10%">Amount</th>
        <th width="13%">Signature</th>
      </tr>
    </thead>

    <tbody>
      @foreach($data as $row)
      <tr>
        <td class="text-center">{{ $loop->iteration }}</td>
        <td>{{ $row->full_name }}</td>
        <td>{{ $row->designation }}</td>
        <td class="text-center">{{ $row->payment_method }}</td>
        <td class="text-center">{{ rtrim(rtrim(number_format($row->final_payments, 2), '0'), '.') }}</td>
        <td></td>
      </tr>
      @endforeach
    </tbody>

    <tfoot>
      <tr>
        <th colspan="4" class="text-right">Total</th>
        <th colspan="2" style="text-align: left;">{{ rtrim(rtrim(number_format($grandTotal, 2), '0'), '.') }}</th>
      </tr>
    </tfoot>
  </table>


  <p style="margin-top: 25px;">
    <strong class="make-uppercase"> Taka: {{ $grandTotalInWords }} Taka Only</strong>
  </p>



  <div class="signature">
    <div class="signature-row">
      <div class="left">
        <p class="signature-gap">Recommended by</p>
      </div>

      <div class="right">
        <p class="signature-gap">Approved by CEO/Managing Director</p>
      </div>
    </div>
  </div>



</body>

</html>
