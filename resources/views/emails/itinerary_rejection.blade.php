<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submission Update</title>
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
      color: #d9534f;
      /* Red for rejection */
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
      background-color: #d9534f;
      color: #ffffff;
      text-decoration: none;
      border-radius: 4px;
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>Submission Update</h2>
    <p>Dear <strong>{{ $employeeName }}</strong>,</p>

    <p>Thank you for submitting your <strong>{{ $submissionType }}</strong> for <strong>{{ $submissionDate }}</strong>.</p>

    <p>After reviewing your submission, I regret to inform you that it <strong>cannot be approved</strong> at this time.</p>

    @if(!empty($remarks))
    <p><strong>Supervisor's Remarks:</strong> {{ $remarks }}</p>
    @endif

    <p>Please review the feedback and make the necessary updates before resubmitting for approval.</p>

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
