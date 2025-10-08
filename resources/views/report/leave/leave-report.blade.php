<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leave Report</title>
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

        .header h4 {
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
            <h4>Leave Report</h4>
        </div>
        <br>

        <table class="expense-table">
            <thead>
                <tr>
                    <th>Employee Id</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th>Leave Type</th>
                    <th>Status</th>
                    <th>Total Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaves as $leave)
                <tr>
                    <td>ABNB{{ $leave->Employee_Id }}</td>
                    <td>{{ $leave->Full_Name }}</td>
                    <td>{{ $leave->department }}</td>
                    <td>{{ $leave->designation }}</td>
                    <td>{{ $leave->From_Date }}</td>
                    <td>{{ $leave->To_Date }}</td>
                    <td>{{ $leave->Leave_Type }}</td>
                    <td>{{ $leave->Status }}</td>
                    <td style="text-align: center">{{ $leave->Total_Days }}</td>
                    
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="8">Total Leave</th>
                    <th>{{ $subtotal }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</body>

</html>