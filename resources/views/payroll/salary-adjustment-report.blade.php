<!DOCTYPE html>
<html>

<head>

  <meta charset="utf-8">

  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #000;
      padding: 6px;
    }

    th {
      background: #eaeaea;
    }

    .title {
      text-align: center;
      margin-bottom: 10px;
    }

    .total-row {
      font-weight: bold;
      background: #f0f0f0;
    }
  </style>

</head>

<body>

  <div class="title">
    <h2>Salary Adjustment Report</h2>
    <p>{{ $monthName }} {{ $year }}</p>
  </div>

  <table>

    <tr>
      <th>S/N</th>
      <th>Employee ID</th>
      <th>Employee Name</th>
      <th>Adjustment Name</th>
      <th>Type</th>
      <th>Amount</th>
      <th>Remarks</th>
    </tr>

    @php $totalAmount = 0; @endphp

    @foreach($data as $key => $row)
    @php $totalAmount += $row->amount; @endphp
    <tr>
      <td>{{ $key+1 }}</td>
      <td>ABNB-{{ $row->employee_id }}</td>
      <td>{{ $row->employee_name }}</td>
      <td>{{ $row->adjustment_name }}</td>
      <td>{{ $row->adjustment_type }}</td>
      <td>{{ number_format($row->amount,2) }}</td>
      <td>{{ $row->remarks }}</td>
    </tr>
    @endforeach

    <tr class="total-row">
      <td colspan="5" style="text-align:right">Total</td>
      <td>{{ number_format($totalAmount,2) }}</td>
      <td></td>
    </tr>

  </table>

</body>

</html>
