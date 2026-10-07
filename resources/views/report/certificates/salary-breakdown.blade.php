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

        .department-padding {
            margin-top: 3.5px;
        }

        .signature-padding {
            margin-bottom: 2px;
        }
    </style>
</head>

<body>
    <div class="container">
        <p class="date">Date: {{ \Carbon\Carbon::now()->format('d F, Y') }}</p>
        <div class="header">
            <h3>SALARY CERTIFICATE WITH BREAK-DOWN</h3>
        </div>
        <div>
            <p>This is certified that, <strong>{{ $employee->Full_Name }}</strong> S/O , {{ $employee->Father_Name }}
                bearing Employee ID: ABNB{{ $employee->Employee_Id }} has been working at Artisan Business Network Bangladesh as
                {{ $employee->official->designation->Name }} in the Department of {{ $employee->official->department->Name }},
                since {{ \Carbon\Carbon::parse($employee->official->DOJ)->format('d F, Y') }}.
            </p>
        </div>
        <div
            <p>He is a permanent employee of the company. His average monthly income is BDT {{ number_format($employee->salaries->total_payment, 0, '.', ',') }} /=
            ({{ $inword['total_payment'] }}).</p>
        </div>

        <div>
            <table width="100%" border="1" cellspacing="0" cellpadding="5">
                <tr>
                    <td>Basic</td>
                    <td>{{ $employee->salaries->basic }}</td>
                    <td>{{ $inword['basic'] }}</td>
                </tr>
                <tr>
                    <td>House Rent</td>
                    <td>{{ $employee->salaries->house_rent }}</td>
                    <td>{{ $inword['house_rent'] }}</td>
                </tr>
                <tr>
                    <td>Conveyance</td>
                    <td>{{ $employee->salaries->conveyance }}</td>
                    <td>{{ $inword['conveyance'] }}</td>
                </tr>
                <tr>
                    <td>Medical Allowance</td>
                    <td>{{ $employee->salaries->medical_allowance }}</td>
                    <td>{{ $inword['medical_allowance'] }}</td>
                </tr>
                @if($employee->salaries->travel_allowance != 0)
                <tr>
                    <td>Travel Allowance</td>
                    <td>{{ $employee->salaries->travel_allowance }}</td>
                    <td>{{ $inword['travel_allowance'] }}</td>
                </tr>
                @endif
                @if($employee->salaries->daily_allowance != 0)
                <tr>
                    <td>Daily Allowance</td>
                    <td>{{ $employee->salaries->daily_allowance }}</td>
                    <td>{{ $inword['daily_allowance'] }}</td>
                </tr>
                @endif
                @if($employee->salaries->mobile_bill != 0)
                <tr>
                    <td>Mobile Bill</td>
                    <td>{{ $employee->salaries->mobile_bill }}</td>
                    <td>{{ $inword['mobile_bill'] }}</td>
                </tr>
                @endif
                <tr>
                    <td><strong>Total Payment</strong></td>
                    <td><strong>{{ $employee->salaries->total_payment }}</strong></td>
                    <td><strong>{{ $inword['total_payment'] }}</strong></td>
                </tr>
            </table>
        </div>

        <div>
            <p>This certificate issued at the request of employee, without any responsibility of the
                company or any of its officials.</p>
        </div>

        <br>
        <br>
        <div>
            <p class="signature-padding">
                ______________________<br>
                <strong>Tanjimatul Alam Iqra</strong><br>
            <div class="department-padding">
                Executive, Human Resource Management<br>
                Human Resource
            </div>
            </p>
        </div>

    </div>
</body>

</html>
