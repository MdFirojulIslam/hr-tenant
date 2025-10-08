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
            <h4>Field Force Monthly Attendance Report</h4>
        </div>
        <br>

        <table class="table">
            <thead>
                <tr>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Employee Id</th>
                    <th rowspan="2">Name</th>
                    <th rowspan="2">Designation</th>
                    <th colspan="3">Check In</th>
                    <th colspan="3">Check Out</th>
                </tr>
                <tr>
                    <th>Time</th>
                    <th>Location</th>
                    <th>Image</th>
                    <th>Time</th>
                    <th>Location</th>
                    <th>Image</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceRecords as $item)
                <tr>
                    <td>{{ $item->date }} ({{ $item->weekday }})</td>
                    <td>ABNB{{ $item->Employee_Id }}</td>
                    <td>{{ $item->Full_Name }}</td>
                    <td>{{ $item->designation }}</td>
                    <td>{{ $item->time_in }}</td>
                    <td>{{ $item->in_location }}</td>
                    @if ($item->in_image != null)
                        <td><a href="{{ 'https://hr.artisanbn.com/storage/'.$item->in_image }}">Show Image</a></td>
                    @else
                        <td>Not Available</td>
                    @endif
                    <td>{{ $item->time_out }}</td>
                    <td>{{ $item->out_location }}</td>
                    @if ($item->out_image != null)
                        <td><a href="{{ 'https://hr.artisanbn.com/storage/'.$item->out_image }}">Show Image</a></td>
                    @else
                        <td>Not Available</td>
                    @endif
                </tr>
                @endforeach
            </tbody>

        </table>
    </div>
</body>

</html>