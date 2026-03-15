<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Total Loan Report</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
    }

    h2 {
      text-align: center;
      margin-bottom: 5px;
    }

    h4 {
      text-align: center;
      margin-top: 0;
      margin-bottom: 15px;
      font-weight: normal;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th,
    td {
      border: 1px solid #000;
      padding: 5px;
      text-align: center;
    }

    th {
      background-color: #D9E1F2;
    }

    tfoot td {
      font-weight: bold;
    }
  </style>
</head>

<body>
  <h2>Artisan Business Network Bangladesh</h2>
  <h4>Total Loan Adjustment Report{{ $reportMonthYear ? " - {$reportMonthYear}" : '' }}</h4>

  <table>
    <thead>
      <tr>
        <th>Employee ID</th>
        <th>Employee Name</th>
        <th>Total Loan Amount</th>
        <th>Tenure</th>
        <th>Paid Tenure</th>
        <th>Total Paid</th>
        <th>Remaining Amount</th>
        <th>Last Installment Date</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data as $row)
      <tr>
        <td>{{ $row['employee_id'] }}</td>
        <td>{{ $row['employee_name'] }}</td>
        <td>{{ number_format($row['total_loan_amount'], 2) }}</td>
        <td>{{ $row['tenure'] }}</td>
        <td>{{ $row['paid_tenure'] }}</td>
        <td>{{ number_format($row['total_paid'], 2) }}</td>
        <td>{{ number_format($row['remaining_amount'], 2) }}</td>
        <td>{{ $row['last_installment_date'] ?? '-' }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2">TOTAL</td>
        <td>{{ number_format(collect($data)->sum('total_loan_amount'), 2) }}</td>
        <td></td>
        <td>{{ collect($data)->sum('paid_tenure') }}</td>
        <td>{{ number_format(collect($data)->sum('total_paid'), 2) }}</td>
        <td>{{ number_format(collect($data)->sum('remaining_amount'), 2) }}</td>
        <td></td>
      </tr>
    </tfoot>
  </table>
</body>

</html>
