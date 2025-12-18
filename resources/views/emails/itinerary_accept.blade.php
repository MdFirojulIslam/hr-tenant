<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submission Approved</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f7f7f7;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 600px;
      margin: 40px auto;
      background-color: #ffffff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    h2 {
      color: #28a745;
      /* Green for approval */
    }

    p {
      color: #333333;
      line-height: 1.6;
    }

    .footer {
      margin-top: 30px;
      font-size: 12px;
      color: #888888;
      text-align: center;
    }

    .button {
      display: inline-block;
      padding: 10px 20px;
      margin-top: 20px;
      background-color: #28a745;
      color: #ffffff;
      text-decoration: none;
      border-radius: 4px;
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>Submission Approved</h2>
    <p>Dear <strong>{{ $employeeName }}</strong>,</p>

    <p>We are pleased to inform you that your <strong>{{ $submissionType }}</strong> submitted on <strong>{{ $submissionDate }}</strong> has been <strong>approved</strong>.</p>

    @if(!empty($remarks))
    <p><strong>Supervisor's Remarks / Notes:</strong> {{ $remarks }}</p>
    @endif

    <p>Thank you for your timely submission and efforts. No further action is required at this time.</p>

    <p>Regards,<br>
      <strong>{{ $approverName }}</strong><br>
      Supervisor<br>
      {{ $departmentName }}
    </p>

    <div class="footer">
      &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.
    </div>
  </div>
</body>

</html>
