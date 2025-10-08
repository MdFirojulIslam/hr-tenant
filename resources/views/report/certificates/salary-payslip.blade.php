<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payslip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .payslip-container {
            width: 650px;
            margin: auto;
            margin-top: 10%;
            border: 1px solid #000;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h2 {
            margin: 5px 0;
        }

        .header p {
            font-size: 12px;
        }

        .section {
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 6px 8px;
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .no-border {
            border: none;
        }

        .summary-table {
            width: 100%;
        }

        .summary-table td {
            padding: 8px;
            font-weight: bold;
        }

        .net-pay {
            background-color: #ccc;
        }
    </style>
</head>

<body>
    <div class="payslip-container">
        <div class="header">
            <h2>Artisan Business Network Bangladesh</h2>
            <p>The Legend, Level # A-1, House # 13, Road # 09, Gulshan-1, Dhaka-1212, Tel: +88 02 222292198, info@artisanbn.com</p>
            <h3>PAY SLIP</h3>
        </div>

        <table class="section">
            <tr>
                <td><strong>EID</strong></td>
                <td>ABNB{{ $employee->Employee_Id }}</td>
                <td><strong>Month:</strong></td>
                <td>{{ \Carbon\Carbon::parse($salary->Month)->format('F, Y') }}</td>
            </tr>
            <tr>
                <td><strong>Name</strong></td>
                <td colspan="3">{{ $employee->Full_Name }}</td>
            </tr>
            <tr>
                <td><strong>Date of Join</strong></td>
                <td colspan="3">{{ \Carbon\Carbon::parse($employee->official->DOJ)->format('d-F-Y') }}</td>
            </tr>
        </table>

        <table class="section">
            <thead>
                <tr>
                    <th colspan="2">PAYMENT</th>
                    <th colspan="2">DEDUCTION</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Gross Salary</td>
                    <td>{{ $salary->Gross_Salary == 0 ? '-' : number_format($salary->Gross_Salary, 2) }}</td>
                    <td>Provident Fund</td>
                    <td>{{ $salary->Provident_Fund == 0 ? '-' : number_format($salary->Provident_Fund, 2) }}</td>
                </tr>
                <tr>
                    <td>Basic</td>
                    <td>{{ $salary->Basic == 0 ? '-' : number_format($salary->Basic, 2) }}</td>
                    <td>Income Tax</td>
                    <td>{{ $salary->Income_Tax == 0 ? '-' : number_format($salary->Income_Tax, 2) }}</td>
                </tr>
                <tr>
                    <td>House Rent</td>
                    <td>{{ $salary->House_Rent == 0 ? '-' : number_format($salary->House_Rent, 2) }}</td>
                    <td>Revenue Stamp</td>
                    <td>{{ $salary->Revenue_Stamp == 0 ? '-' : number_format($salary->Revenue_Stamp, 2) }}</td>
                </tr>
                <tr>
                    <td>Conveyance</td>
                    <td>{{ $salary->Conveyance == 0 ? '-' : number_format($salary->Conveyance, 2) }}</td>
                    <td>Leave Without Pay</td>
                    <td>{{ $salary->Leave_Without_Pay == 0 ? '-' : number_format($salary->Leave_Without_Pay, 2) }}</td>
                </tr>
                <tr>
                    <td>Medical Allowance</td>
                    <td>{{ $salary->Medical_Allowance == 0 ? '-' : number_format($salary->Medical_Allowance, 2) }}</td>
                    <td>Penalty/Other Deduction</td>
                    <td>{{ $salary->Penalty_Other_Deduction == 0 ? '-' : number_format($salary->Penalty_Other_Deduction, 2) }}</td>
                </tr>
                <tr>
                    <td>Festival Bonus</td>
                    <td>{{ $salary->Festival_Bonus == 0 ? '-' : number_format($salary->Festival_Bonus, 2) }}</td>
                    <td class="bold">Total Deduction</td>
                    <td class="bold">{{ $salary->Total_Deduction == 0 ? '-' : number_format($salary->Total_Deduction, 2) }}</td>
                </tr>
                <tr>
                    <td>Travel Allowance</td>
                    <td>{{ $salary->Travel_Allowance == 0 ? '-' : number_format($salary->Travel_Allowance, 2) }}</td>
                    <td colspan="2" class="no-border"></td>
                </tr>
                <tr>
                    <td>Daily Allowance</td>
                    <td>{{ $salary->Daily_Allowance == 0 ? '-' : number_format($salary->Daily_Allowance, 2) }}</td>
                    <td colspan="2" class="no-border"></td>
                </tr>
                <tr>
                    <td>Mobile Bill Allowance</td>
                    <td>{{ $salary->Mobile_Bill == 0 ? '-' : number_format($salary->Mobile_Bill, 2) }}</td>
                    <td colspan="2" class="no-border"></td>
                </tr>
                <tr>
                    <td>Other Allowance</td>
                    <td>{{ $salary->Other_Allowance == 0 ? '-' : number_format($salary->Other_Allowance, 2) }}</td>
                    <td class="net-pay bold" rowspan="2">NET PAY</td>
                    <td class="text-right net-pay bold" rowspan="2">{{ number_format($salary->Total_Payment, 2) }}</td>
                </tr>
                <tr>
                    <td class="bold">Total Payment</td>
                    <td class="bold">{{ number_format($salary->Total_Payment, 2) }}</td>
                </tr>
            </tbody>
        </table>

    </div>
</body>

</html>