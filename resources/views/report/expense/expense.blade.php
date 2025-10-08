<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Employee Expense</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-top: 0px;
        }

        .header h3 {
            margin: 0px;
        }

        .header h6 {
            margin: 0px;
        }

        p,
        th,
        td {
            font-size: 10px;
            text-align: justify;
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

        .no-border td {
            border: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h3>Artisan Business Network Bangladesh</h3>
            <h6>Travel/Daily Expanse Report</h6>
        </div>
        <br>

        <table width="100%">
            <tr>
                <th>Employee Name</th>
                <td>: {{ $employee->Full_Name }}</td>
                <th>Employee ID</th>
                <td>: ABNB{{ $employee->Employee_Id }}</td>
            </tr>
            <tr>
                <th>Department</th>
                <td>: {{ $employee->official->department->Name }}</td>
                <th>Mobile No</th>
                <td>: {{ $employee->Contact_No }}</td>
            </tr>
            <tr>
                <th>Designation</th>
                <td>: {{ $employee->official->designation->Name }}</td>
                <th>Print Date & Time</th>
                <td>: {{ \Carbon\Carbon::now()->setTimezone('Asia/Dhaka')->format('d F, Y h:i A') }}</td>
            </tr>
        </table>


        <table class="expense-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Town Name</th>
                    <th>Place of Visit</th>
                    <th>TA</th>
                    <th>DA</th>
                    <th>Others</th>
                    <th>Amount</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $date => $expense)
                <tr>
                    <td>{{ $date }} <br>
                        {{\Carbon\Carbon::parse($date)->format('l')}}</td>
                    <td>
                        <table class="no-border">
                            @if($expense['ta']->isNotEmpty())
                                @foreach($expense['ta'] as $item)
                                <tr>
                                    <td style="text-align: center">{{ $item->town->Name }}</td>
                                </tr>
                                @endforeach
                            @else
                                @foreach($expense['da'] as $item)
                                    <tr>
                                        <td style="text-align: center">{{ $item->town->Name }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </table>
                    </td>

                    <td>
                        <table class="no-border">
                            @foreach($expense['ta'] as $item)
                            <tr>
                                <td>{{ $item->From }} - {{ $item->To }}</td>
                                <td> : {{ number_format($item->Amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </td>
                    <td style="text-align: center">{{ number_format($expense['ta_total'], 2) }}</td>
                    <td style="text-align: center">{{ number_format($expense['da_total'], 2) }}</td>
                    <td>
                        <table class="no-border">
                            @foreach($expense['oe'] as $item)
                            <tr>
                                <td>{{ $item->Description }}</td>
                                <td> : {{ number_format($item->Amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </td>
                    <td style="text-align: center">{{ number_format($expense['total_amount'], 2) }}</td>
                    <td></td>
                </tr>
                @endforeach
                <tr>
                    <th colspan="3" style="text-align: center">Grand Total</th>
                    <th>{{ number_format($gross_ta, 2) }}</th>
                    <th>{{ number_format($gross_da, 2) }}</th>
                    <th>{{ number_format($gross_oe, 2) }}</th>
                    <th>{{ number_format($gross_total, 2) }}</th>
                    <th></th>
                </tr>
            </tbody>
        </table>
        <table width="100%">
        <tr>
                <td>
                    @if($status >= 1)
                        {{ $employee->Full_Name }}
                    @endif
                </td>
                <td>
                    @if($status >= 2)
                        {{ $employee->official->supervisor->Full_Name }}
                    @endif
                </td>
                <td>
                    @if($status >= 3)
                        SM Mahmud Hossain
                    @endif
                </td>
                <td>
                    @if($status >= 4)
                        Mohammad Kamrul Hasan
                    @endif
                </td>
            </tr>
            <tr>
                <td>__________________</td>
                <td>__________________</td>
                <td>__________________</td>
                <td>__________________</td>
            </tr>
            <tr>
                <th>Claimant</th>
                <th>Manager</th>
                <th>Recommended By</th>
                <th>Approved By</th>
            </tr>
        </table>
    </div>
</body>

</html>