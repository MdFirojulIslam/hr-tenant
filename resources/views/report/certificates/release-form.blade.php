<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>No Objection Certificate</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');
        body {
            font-family:  Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .date{
            margin-top: 20%;
        }

        .container {
            padding: 7%;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            margin-top: 2%;
        }
        p{
            font-size: 14px;
            text-align: justify;
        }
    </style>
</head>

<body>
    <div class="container">
        <p class="date">Date: {{ \Carbon\Carbon::now()->format('d F, Y') }}</p>
        <div>
            <p><strong>{{ $employee->Full_Name }}<br>S/O: {{ $employee->Father_Name }}</strong><br>
            {{ $employee->official->designation->Name }}<br>Artisan Business Network Bangladesh</p>
            <p><strong>Subject: Release from Service</strong></p>
            <br>
            Dear @if($employee->Gender == 'M') Mr. @else Ms. @endif {{ $employee->Full_Name }},
            <p>We regretfully inform you that your employment has been released from the service of Artisan Business Network
                Bangladesh with effective from {{ \Carbon\Carbon::parse($employee->official->DOL)->format('d F, Y') }}.
            </p>
            <p>You are requested to contact the Accounts and Finance Department for final settlement of your accounts after 
                obtaining the final clearance. Please note that your release will not however absolve you from the liability
                or any loss or damage that the company might have sustained during the tenure of your service or likely to
                sustain in future for any of your acts, omission or otherwise. 
            </p>
            <p>Thanking you for your service and valuable contribution to Artisan Business Network Bangladesh</p>
        </div>
        <div
            <p><strong>We wish every success of him.</strong></p>
        </div>
        <div>
            <p>Thanks & Regards</p>
        </div>
        <br>
        <br>
        <div>
            <p>
                ______________________<br>
                <strong>Saad Hossain Tapu</strong><br>
                Manager, HRM</p>
        </div>

    </div>
</body>

</html>