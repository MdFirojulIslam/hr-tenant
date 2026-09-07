<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Attendance Roster Report</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 11px;
    }

    h3 {
      text-align: center;
      margin: 0 0 4px 0;
    }

    .subtitle {
      text-align: center;
      margin: 0 0 14px 0;
      font-size: 12px;
      color: #444;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #000;
      padding: 4px 5px;
      text-align: center;
    }

    th {
      background: #eaeaea;
      font-size: 10px;
      text-transform: uppercase;
    }

    td.left {
      text-align: left;
    }
  </style>
</head>

<body>
  <h3>Daily Attendance Roster Report</h3>
  <p class="subtitle">
    Period: {{ $fromDate ?? '-' }} @if($toDate && $toDate !== $fromDate) to {{ $toDate }} @endif
    &nbsp;|&nbsp; Records: {{ collect($data)->count() }}
  </p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Employee ID</th>
        <th>Employee Name</th>
        <th>Department</th>
        <th>Date</th>
        <th>Day</th>
        <th>Status</th>
        <th>In</th>
        <th>Out</th>
        <th>In Diff</th>
        <th>Out Diff</th>
        <th>Working</th>
        <th>Rostered In</th>
        <th>Rostered Out</th>
        <th>Rostered Status</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data as $index => $row)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>ABNB{{ $row->official_id }}</td>
        <td class="left">{{ $row->employee_name }}</td>
        <td class="left">{{ $row->department_name }}</td>
        <td>{{ $row->date }}</td>
        <td>{{ $row->day_name }}</td>
        <td>{{ $row->attendance_status }}</td>
        <td>{{ $row->fmt_in }}</td>
        <td>{{ $row->fmt_out }}</td>
        <td>{{ $row->in_diff }}</td>
        <td>{{ $row->out_diff }}</td>
        <td>{{ $row->working_hours }}</td>
        <td>{{ $row->fmt_rostered_in }}</td>
        <td>{{ $row->fmt_rostered_out }}</td>
        <td>{{ $row->rostered_status }}</td>
        <td class="left">{{ $row->remarks }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>

</html>