<!DOCTYPE html>
<html>

<head>
    <title>Leave Status - {{ $name }}</title>

</head>

<body>
    <div>
        <p><i>Dear {{ $name }},</i><br>
        Your {{ $leave_type }} leave request from {{ $from_date }} to {{ $to_date }} has been <b>{{ $status }}</b>.</p>
    </div>
</body>

</html>