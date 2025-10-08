<!DOCTYPE html>
<html>

<head>
    <title>Attendance Report</title>
    <style>
        .mail-container {
            text-align: center;
        }
        table, tr, th, td{
            border: 1px solid black;
            text-align: left;
            padding: 5px 20px;
        }
        h1{
            margin-bottom: 0px;
        }
    </style>
</head>

<body>
    <div class='mail-container'>
        <h1><i>Monthly Attendance Report</i></h1>
        <h2><i>{{ $employeeAttendance[0]->Full_Name }}</i></h2>
        <p><i>{{ $employeeAttendance[0]->Name }}</i></p>
        <p>
            [This is a system generated email. Please do not reply to this message]<br>
            <i>Artisan Business Network Bangladesh <br>Month: {{ $monthName }}, {{ $year }}</i>
        </p>
        <table>
            <tr>
                <th>Employee ID</th>
                <th>Date</th>
                <th>Weekday</th>
                <th>Entry Time</th>
                <th>Exit Time</th>
            </tr>
            @foreach($employeeAttendance as $item)
            <tr>
                <th>ABNB{{ $item->Employee_Id }}</th>
                <td>{{ $item->Date }}</td>
                <td>{{ $item->weekday }}</td>
                <td>{{ $item->Time_In }}</td>
                <td>{{ $item->Time_Out }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>

</html>