<!DOCTYPE html>
<html>

<head>
    <title>Leave {{ $status }} - {{ $name }}</title>

</head>

<body>
    <div>
        <p><i>Dear Concern,</i><br>
        {{ $name }}'s {{ $leave_type }} leave request from {{ $from_date }} to {{ $to_date }} has been <b>{{ $status }}</b> by the Supervisor.</p>
    </div>
</body>

</html>