<!DOCTYPE html>
<html>

<head>
    <title>Leave Request - {{ $name }}</title>
    <style>
        .mail-container {
            text-align: center;
        }
        table, tr, th, td{
            border: 1px solid black;
            text-align: left;
            padding: 5px 20px;
        }
    </style>
</head>

<body>
    <div class='mail-container'>
        <h2><i>You have requested a leava approval;</i></h2>
        <table>
            <tr>
                <th>EID</th>
                <td>ABNB{{ $eid }}</td>
            </tr>
            <tr>
                <th>NAME</th>
                <td>{{ $name }}</td>
            </tr>
            <tr>
                <th>FROM DATE</th>
                <td>{{ $from_date }}</td>
            </tr>
            <tr>
                <th>TO DATE</th>
                <td>{{ $to_date }}</td>
            </tr>
            <tr>
                <th>LEAVE TYPE</th>
                <td>{{ $leave_type }}</td>
            </tr>
            <tr>
                <th>PURPOSE</th>
                <td>
                    @if($purpose)
                    {{ $purpose }}
                    @endif
                </td>
            </tr>
        </table>
        <br>
        <a href="https://hr.artisanbn.com/leave/supervisor-approval">Reject | Approve</a>
    </div>
</body>

</html>