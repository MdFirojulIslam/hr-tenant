<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Salary Breakdown</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .date {
            margin-top: 20%;
        }

        .container {
            padding: 7%;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            margin-top: 15%;
        }

        p,
        table {
            font-size: 14px;
            text-align: justify;
        }
    </style>
</head>

<body>
    <div class="container">
        <p class="date">Date: {{ \Carbon\Carbon::now()->format('d F, Y') }}</p>
        <div class="header">
            <h3>SALARY CERTIFICATE</h3>
        </div>
        <div>
            <p>This is certified that, <strong>{{ $employee->Full_Name }}</strong> S/O , {{ $employee->Father_Name }}
                bearing Employee ID: ABNB{{ $employee->Employee_Id }} has been working at Artisan Business Network Bangladesh as
                {{ $employee->official->designation->Name }} in the Department of {{ $employee->official->department->Name }},
                since {{ \Carbon\Carbon::parse($employee->official->DOJ)->format('d F, Y') }}.
            </p>
        </div>
        <div
            <p>He is a permanent employee of the company. His average monthly income is BDT {{ number_format($employee->employee_salary->Total_Payment, 0, '.', ',') }} /=
            ({{ $inword['Total_Payment'] }}) only and other benefits.</p>
        </div>

        <div>
            <table width="100%">
                <tr>
                    <td>Basic</td>
                    <td>{{ $employee->employee_salary->Basic }}</td>
                    <td>{{ $inword['Basic'] }}</td>
                </tr>
                <tr>
                    <td>House Rent</td>
                    <td>{{ $employee->employee_salary->House_Rent }}</td>
                    <td>{{ $inword['House_Rent'] }}</td>
                </tr>
                <tr>
                    <td>Conveyance</td>
                    <td>{{ $employee->employee_salary->Conveyance }}</td>
                    <td>{{ $inword['Conveyance'] }}</td>
                </tr>
                <tr>
                    <td>Medical Allowance</td>
                    <td>{{ $employee->employee_salary->Medical_Allowance }}</td>
                    <td>{{ $inword['Medical_Allowance'] }}</td>
                </tr>
                <tr>
                    <td>Travel Allowance</td>
                    <td>{{ $employee->employee_salary->Travel_Allowance }}</td>
                    <td>{{ $inword['Travel_Allowance'] }}</td>
                </tr>
                <tr>
                    <td>Daily Allowance</td>
                    <td>{{ $employee->employee_salary->Daily_Allowance }}</td>
                    <td>{{ $inword['Daily_Allowance'] }}</td>
                </tr>
                <tr>
                    <td>Mobile Bill</td>
                    <td>{{ $employee->employee_salary->Mobile_Bill }}</td>
                    <td>{{ $inword['Mobile_Bill'] }}</td>
                </tr>
                <tr>
                    <td><strong>Total Payment</strong></td>
                    <td><strong>{{ $employee->employee_salary->Total_Payment }}</strong></td>
                    <td><strong>{{ $inword['Total_Payment'] }}</strong></td>
                </tr>
            </table>
        </div>

        <div
            <p>This certificate issued at the request of employee, without any responsibility of the
            company or any of its officials.</p>
        </div>

        <br>
        <br>
        <div>
            <p>
                ______________________<br>
                <strong>Md. Shafiqul Islam</strong><br>
                Assistant Manager- Accounts & Finance
            </p>
        </div>

    </div>
</body>

</html>