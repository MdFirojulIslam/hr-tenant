<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Expense Summary Report</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .header h3 {
            margin: 0;
            padding: 0;
        }

        .header h6 {
            margin: 0;
            padding: 0;
        }

        p,
        th,
        td {
            font-size: 10px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-table th,
        .info-table td {
            padding: 2px 4px;
            font-size: 10px;
            border: none;
        }

        .info-table th {
            text-align: left;
            width: 120px;
            font-weight: bold;
        }

        .info-table td {
            text-align: left;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 70px;
        }

        .expense-table tr,
        .expense-table td,
        .expense-table th {
            border: 1px solid black;
        }

        .expense-table th {
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .grand-total {
            font-weight: bold;
            font-size: 11px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            border: none;
            padding: 4px;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h3>Artisan Business Network Bangladesh</h3>
            <h6>Travel/Daily Expense Report</h6>
        </div>

        <table class="info-table">
            <tr>
                <th>Month:</th>
                <td>{{ $monthName }} {{ $year }}</td>
                @if($departmentFilter)
                <th>Department:</th>
                <td>{{ $departmentFilter }}</td>
                @endif
            </tr>
            <tr>
                <th>Export Date & Time:</th>
                <td>{{ \Carbon\Carbon::now()->setTimezone('Asia/Dhaka')->format('d F, Y h:i A') }}</td>
            </tr>
        </table>

        <table class="expense-table">
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Designation</th>
                    <th>TA</th>
                    <th>DA/Others</th>
                    <th>Amount</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $index => $expense)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">ABNB{{ $expense->Employee_Id }}</td>
                    <td>{{ $expense->Full_Name }}</td>
                    <td>{{ $expense->Des_Name }}</td>
                    <td class="text-center">{{ number_format($expense->total_ta, 2) }}</td>
                    <td class="text-center">{{ number_format($expense->total_da_oe, 2) }}</td>
                    <td class="text-center">{{ number_format($expense->total_expense, 2) }}</td>
                    <td></td>
                </tr>
                @endforeach
                <tr>
                    <th colspan="4" class="text-center">Grand Total</th>
                    <th class="text-center grand-total">{{ number_format($totalTa, 2) }}</th>
                    <th class="text-center grand-total">{{ number_format($totalDaOe, 2) }}</th>
                    <th class="text-center grand-total">{{ number_format($totalAmount, 2) }}</th>
                    <th></th>
                </tr>
            </tbody>
        </table>

        <table class="signature-table">
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>__________________</td>
                <td>__________________</td>
                <td>__________________</td>
            </tr>
            <tr>
                <th>Manager</th>
                <th>Recommended By</th>
                <th>Approved By</th>
            </tr>
        </table>
    </div>
</body>

</html>
