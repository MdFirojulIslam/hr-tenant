<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Noto Sans Bengali', sans-serif;
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
            font-size: 8px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 70px;
        }

        .table tr,
        .table td,
        .table th {
            border: 1px solid black;
        }

        .table th {
            text-align: center;
        }

    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h3>Artisan Business Network Bangladesh</h3>
            <h4>Attendance Report</h4>
        </div>
        <br>

        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Weekday</th>
                    <th>Employee Id</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceRecords as $item)
                <tr>
                    <td align="center">{{ $item->date }}</td>
                    <td align="center">{{ $item->weekday }}</td>
                    <td align="center">ABNB{{ $item->Employee_Id }}</td>
                    <td align="center">{{ $item->Full_Name }}</td>
                    <td align="center">{{ $item->designation }}</td>
                    <td align="center">{{ $item->time_in }}</td>
                    <td align="center">{{ $item->time_out }}</td>
                </tr>
                @endforeach
            </tbody>

        </table>
    </div>
</body>

</html>