<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certificate of Income</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            padding: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 6px 10px;
            text-align: left;
        }

        .no-border {
            border: none;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .mt-2 {
            margin-top: 20px;
        }

        .mt-5 {
            margin-top: 50px;
        }
    </style>
</head>

<body>

    <p>Date: {{ \Carbon\Carbon::now()->format('dS F Y') }}</p>

    <h3 class="center">CERTIFICATE OF INCOME</h3>

    <table>
        <tr>
            <td><strong>Name</strong></td>
            <td>{{ $employee->Full_Name }}</td>
        </tr>
        <tr>
            <td><strong>Designation</strong></td>
            <td>{{ $employee->official->designation->Name }}</td>
        </tr>
        <tr>
            <td><strong>Joining Date</strong></td>
            <td>{{ \Carbon\Carbon::parse($employee->official->DOJ)->format('d-F-Y') }}</td>
        </tr>
        <tr>
            <td><strong>Income Period</strong></td>
            <td>{{ $period_from }} To {{ $period_to }}</td>
        </tr>
    </table>

    <p class="bold">Payment Breakdown has been given below;</p>

    <table>
        <thead>
            <tr>
                <th>Particulars</th>
                <th>Amount in Bangladeshi Taka</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic</td>
                <td>{{ number_format($salary->Basic, 0) }}</td>
            </tr>
            <tr>
                <td>House Rent</td>
                <td>{{ number_format($salary->House_Rent, 0) }}</td>
            </tr>
            <tr>
                <td>Conveyance</td>
                <td>{{ number_format($salary->Conveyance, 0) }}</td>
            </tr>
            <tr>
                <td>Medical Allowance</td>
                <td>{{ number_format($salary->Medical_Allowance, 0) }}</td>
            </tr>
            <tr>
                <td>Festival Bonus</td>
                <td>{{ number_format($salary->Festival_Bonus, 0) }}</td>
            </tr>
            <tr>
                <td>Overtime</td>
                <td>{{ number_format($salary->Overtime, 0) }}</td>
            </tr>
            <tr>
                <td>Food Allowance</td>
                <td>{{ number_format($salary->Food_Allowance ?? 0, 0) }}</td>
            </tr>
            <tr>
                <td>Travel Allowance</td>
                <td>{{ number_format($salary->Travel_Allowance, 0) }}</td>
            </tr>
            <tr>
                <td>Other Allowance</td>
                <td>{{ number_format($salary->Other_Allowance, 0) }}</td>
            </tr>
            <tr>
                <th>Total Income</th>
                <th>{{ number_format($total_income, 0) }}</th>
            </tr>
        </tbody>
    </table>

    <p class="bold">TAX PAID</p>

    <p>During the period income Tax deducted and deposited as per the details has been given below,</p>

    <table>
        <thead>
            <tr>
                <th>SL</th>
                <th>Month</th>
                <th>Challan No.</th>
                <th>Date of Challan</th>
                <th>Bank/Branch</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="6" class="center">No Tax Deducted</td>
            </tr>
        </tbody>
    </table>

    <p>This is to certify that the above information is correct.</p>

    <p>
        And we also certify that the total amount of BDT {{ number_format($tax_paid, 2) }} (In Word - {{ $tax_in_words }}) only has been deducted at source from his salary & allowance as per the income tax ordinance 2023.
    </p>

    <div class="mt-5">
        <p>
            For ARTISAN BUSINESS NETWORK BANGLADESH<br><br>
            ____________________________<br>
            Md. Hasan Miah<br>
            Assistant Manager, Credit Control & Revenue
        </p>
    </div>

</body>

</html>
