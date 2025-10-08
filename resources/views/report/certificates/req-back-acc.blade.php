<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>No Objection Certificate</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .date {
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

        p {
            font-size: 14px;
            text-align: justify;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid black;
            padding: 4px;
            text-align: left;
            font-size: 14px;
            text-align: center;
        }

        th {
            background-color: #e6e6e6;
        }
    </style>
</head>

<body>
    <div class="container">
        <p class="date">Date: {{ \Carbon\Carbon::now()->format('d F, Y') }}</p><br>
        <p>
            To<br>
            The Manager <br>
            City Bank limited,
        </p>
        <p><strong>Subject: Request for opening salary bank account for new employee.</strong></p>
        <div>
            <p>Dear Sir, <br>
                I am writing to request your assistance in opening a salary bank account for our new employee.

            <table>
                <tr>
                    <th>EID</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Monthly Salary</th>
                </tr>
                <tr>
                    <td>ABNB{{ $employee->Employee_Id }}</td>
                    <td>{{ $employee->Full_Name }}</td>
                    <td>{{ $employee->official->designation->Name }}</td>
                    <td>{{ number_format($salary, 0, '.', ',') }}/-</td>
                </tr>
            </table>

        </div>

        <div
            <p>This certificate issued at the request of employee, without any responsibility of the
            company or any of its officials.</p>
        </div>

        <br>
        <br>


    </div>
</body>

</html>