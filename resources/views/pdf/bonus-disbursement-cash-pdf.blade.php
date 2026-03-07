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

    /* ================= HEADER (FIRST PAGE ONLY) ================= */
    .first-page-header {
      text-align: center;
      margin-bottom: 30px;
    }


    /* ================= FOOTER ================= */
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

    /* ================= TABLE ================= */
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

    /* ================= SIGNATURE ================= */
    .signature {
      margin-top: 80px;
    }


    p {
      margin: 0;
    }
  </style>
</head>

<body>

  <!-- ================= FIRST PAGE HEADER ================= -->
  <div class="first-page-header">
    <h3>Bonus for the month of {{ $monthName }} {{ $year }}</h3>

    <h5>Artisan Business Network Bangladesh</h5>
  </div>

  <!-- ================= TABLE ================= -->
  <table>
    <thead>
      <tr>
        <th width="10%">EID</th>
        <th width="30%">Name</th>
        <th width="30%">Payment Method</th>
        <th width="15%">Amount</th>
      </tr>
    </thead>

    <tbody>
      @foreach($data as $row)
      <tr>
        <td class="text-center">ABNB{{ $row->employee_id }}</td>
        <td>{{ $row->full_name }}</td>
        <td class="text-center">{{ $row->payment_methods }}</td>
        <td class="text-right">{{ number_format($row->bonus_amounts, 2) }}</td>
      </tr>
      @endforeach
    </tbody>

    <!-- Grand Total -->
    <tfoot>
      <tr>
        <th colspan="3" class="text-right">Total</th>
        <th class="text-right">{{ number_format($grandTotal, 2) }}</th>
      </tr>
    </tfoot>
  </table>


  <!-- ================= FOOTER TEXT / SIGNATURE ================= -->


  <div class="signature">

    ___________________________<br>
    Signature &amp; Seal
  </div>

  <!-- ================= PAGE NUMBER ================= -->
  <!-- <footer>
    <span class="page">Page </span>
  </footer> -->

</body>

</html>
