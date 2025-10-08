<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        .mail-container {
            text-align: center;
        }

        table,
        tr,
        th,
        td {
            border: 1px solid black;
            text-align: left;
            padding: 5px 20px;
        }

        h1 {
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
            <thead>
                <tr>
                    <th>Employee Id</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Town</th>
                    <th>Time</th>
                    <th>Location</th>
                    <th>Map Link</th>
                    <th>Image Link</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendence as $item)
                <tr>
                    <td>ABNB{{ $item->Employee_Id }}</td>
                    <td>{{ $item->Full_Name }}</td>
                    <td>{{ $item->designation }}</td>
                    <td>{{ $item->town_name }}</td>
                    <td>{{ $item->time_in }}</td>
                    <td>{{ $item->in_location }}</td>
                    @if ($item->latitude != null)
                    <td><a href="{{ 'https://maps.google.com/?q='.$item->latitude.','.$item->longitude }}">Show in Map</a></td>
                    @else
                    <td>Not Available</td>
                    @endif
                    @if ($item->in_image != null)
                    <td><a href="{{ 'https://hr.artisanbn.com/storage/'.$item->in_image }}">Show Image</a></td>
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