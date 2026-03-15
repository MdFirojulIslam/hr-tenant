<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Loan Report</title>
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
      padding: 5px;
      text-align: center;
    }

    th {
      background: #eaeaea;
    }

    tfoot td {
      font-weight: bold;
      background: #f2f2f2;
    }
  </style>
</head>

<body>
  <h3 style="text-align:center;">Employee Loan Report</h3>
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
        <th>Last Installment</th>
        <th>Last Installment Date</th>
      </tr>
    </thead>
    <tbody>
      @php
      $totalLoan = 0;
      $totalPaid = 0;
      $totalRemaining = 0;
      @endphp

      @foreach($data as $row)
      @php
      $totalLoan += $row['total_loan_amount'];
      $totalPaid += $row['total_paid'];
      $totalRemaining += $row['remaining_amount'];
      @endphp
      <tr>
        <td>{{ $row['employee_id'] }}</td>
        <td>{{ $row['employee_name'] }}</td>
        <td>{{ number_format($row['total_loan_amount'],2) }}</td>
        <td>{{ $row['tenure'] }}</td>
        <td>{{ $row['paid_tenure'] }}</td>
        <td>{{ number_format($row['total_paid'],2) }}</td>
        <td>{{ number_format($row['remaining_amount'],2) }}</td>
        <td>{{ $row['last_installment'] ? number_format($row['last_installment'],2) : '-' }}</td>
        <td>{{ $row['last_installment_date'] ?? '-' }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2">TOTAL</td>
        <td>{{ number_format($totalLoan,2) }}</td>
        <td></td>
        <td></td>
        <td>{{ number_format($totalPaid,2) }}</td>
        <td>{{ number_format($totalRemaining,2) }}</td>
        <td></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
</body>

</html>
