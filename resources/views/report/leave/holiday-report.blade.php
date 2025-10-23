<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Holiday Report {{ $year }}</title>
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

        .header h2 {
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
            <img style="width: 100px; height: 100px;" src="{{ public_path().'/storage/uploads/1718087744_artisan.png' }}" />
            <h2>Artisan Business Network Bangladesh</h2>
            <h4>List of holidays for the year 2025</h4>
        </div>
        <br>

        <table class="expense-table">
            <thead>
                <tr>
                    <th>Serial No</th>
                    <th>Type of holiday</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>No of Holidays</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($holidays as $index => $holiday)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $holiday->holiday_name }}</td>
                    <td style="text-align: center;">
                        @foreach ($holiday->date_range as $range)
                        {{ $range['date'] }}<br>
                        @endforeach
                    </td>
                    <td style="text-align: center;">
                        @foreach ($holiday->date_range as $range)
                        {{ $range['weekday'] }}<br>
                        @endforeach
                    </td>
                    <td style="text-align: center;">{{ $holiday->Duration }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
