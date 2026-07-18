<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 80px 50px 80px 50px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .report-title {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .info-label {
            font-weight: bold;
        }

        .summary-section {
            margin: 15px 0;
            padding: 10px 15px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        .summary-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
            text-decoration: underline;
        }

        .summary-row {
            display: flex;
            gap: 40px;
            font-size: 11px;
        }

        .summary-item {
            display: flex;
            gap: 5px;
        }

        .summary-label {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 11px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        th {
            background-color: #e8e8e8;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            font-size: 11px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .footer {
            margin-top: 30px;
        }

        .footer-section {
            margin-bottom: 15px;
        }

        .footer-label {
            font-weight: bold;
            font-size: 12px;
        }

        .signature-area {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-block {
            width: 45%;
        }

        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
            font-size: 11px;
        }

        .date-line {
            margin-top: 10px;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="company-name">ARTISAN BUSINESS NETWORK BANGLADESH</div>
        <div class="report-title">Employee Incentive Report</div>
        <div class="info-row">
            <span>
                <span class="info-label">Incentive Period:</span>
                {{ $incentive->incentive_periods }}
            </span>
            <span>
                <span class="info-label">Export Date:</span>
                {{ \Carbon\Carbon::now()->format('dS M Y') }}
            </span>
            <span>
                <span class="info-label">Status:</span>
                {{ $incentive->status }}
            </span>
        </div>
    </div>

    <div class="summary-section">
        <div class="summary-title">Summary</div>
        <div class="summary-row">
            <div class="summary-item">
                <span class="summary-label">Total Employees:</span>
                <span>{{ $totalEmployees }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Incentive Amount:</span>
                <span>{{ number_format($totalAmount, 2) }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">SL</th>
                <th width="12%">Employee ID</th>
                <th width="20%">Employee Name</th>
                <th width="18%">Department</th>
                <th width="18%">Designation</th>
                <th width="14%">Incentive Amount</th>
                <th width="13%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $detail)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">ABNB{{ $detail->employee_id }}</td>
                <td>{{ $detail->employee_name }}</td>
                <td>{{ $detail->department_name }}</td>
                <td>{{ $detail->designation_name }}</td>
                <td class="text-right">{{ number_format($detail->amounts, 2) }}</td>
                <td>{{ $detail->remarks ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">Total Incentive Amount</td>
                <td class="text-right">{{ number_format($totalAmount, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div class="footer-section">
            <span class="footer-label">Prepared By:</span>
            {{ $createdByUser ? $createdByUser->name : '-' }}
        </div>

        <div class="signature-area">
            <div class="signature-block">
                <div class="signature-line">Prepared by</div>
            </div>
            <div class="signature-block">
                <div class="signature-line">Approved by</div>
            </div>
        </div>
    </div>
</body>

</html>
