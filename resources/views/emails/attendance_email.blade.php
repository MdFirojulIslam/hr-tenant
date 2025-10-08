<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
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
        <h1><i>{{ $title }}</i></h1>
        <p>
            [This is a system generated email. Please do not reply to this message]<br>
            <i>Artisan Business Network Bangladesh <br>Date: {{ $date }}</i>
        </p>
        <table>
            <tr>
                <th>Employee ID</th>
                <th>Employee Name</th>
                <th>Designation</th>
                <th>Entry Time</th>
            </tr>
            @foreach($attendence as $item)
            <tr>
                <th>ABNB{{ $item->Employee_Id }}</th>
                <td>{{ $item->Full_Name }}</td>
                <td>{{ $item->Name }}</td>
                <td>{{ $item->Time_In }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>

</html>