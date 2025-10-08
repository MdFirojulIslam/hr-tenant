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
            margin-top: 20%;
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
        <div class="header">
            <h3>To Whom It May Concern</h3>
        </div>
        <div>
            <p>This is for information that, <strong>{{ $employee->Full_Name }}</strong> S/O,
             {{ $employee->Father_Name }}, bearing Employee ID: ABNB{{ $employee->Employee_Id }} 
             has worked at Artisan Business Network Bangladesh as 
             {{ $employee->official->designation->Name }} in the Department of {{ $employee->official->department->Division }}, 
             from {{ \Carbon\Carbon::parse($employee->official->DOJ)->format('d F, Y') }} 
             to {{ \Carbon\Carbon::parse($employee->official->DOL)->format('d F, Y') }}.</p>
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