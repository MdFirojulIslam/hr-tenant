<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Separation Form</title>
    <style>
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

        h4 {
            margin-bottom: 0px;
        }

        .header-info {
            text-align: center;
        }

        .address {
            font-size: 12px;
            text-align: center;
        }

        p {
            font-size: 14px;
            text-align: justify;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
            font-size: 10px;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .checkbox-container label {
            display: inline-block;
            width: 100%;
        }

        .checkbox-container {
            display: flex;
            flex-direction: column;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header-info">
            <h4>ARTISAN BUSINESS NETWORK BANGLADESH</h4>
            <p class="address">The Legend, Level # A-1, House # 13, Road # 09, Gulshan-1, Dhaka-1212, Tel: +88 02 222239196, info@artisanbn.com, www.artisanbn.com</p>
            <h4>EMPLOYEE SEPARATION FORM</h4>
        </div>

        <table>
            <tr>
                <th colspan="4">Employee Information</th>
            </tr>
            <tr>
                <td>EID</td>
                <td>{{ $employee->Employee_Id }}</td>
                <td>Date Of Join</td>
                <td>{{ $employee->official->DOJ }}</td>
            </tr>
            <tr>
                <td>Name</td>
                <td colspan="3">{{ $employee->Full_Name }}</td>
            </tr>
            <tr>
                <td>Designation</td>
                <td>{{ $employee->official->designation->Name }}</td>
                <td>Resignation Date</td>
                <td>{{ $employee->official->DOL }}</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>
                    <div class="checkbox-container">
                        <label><input type="checkbox"> Full-Time</label>
                        <label><input type="checkbox"> Part-Time</label>
                        <label><input type="checkbox"> Temporary</label>
                    </div>
                </td>
                <td>More details about<br> the separation<br> (if any)</td>
                <td></td>
            </tr>
            <tr>
                <td>Reason for separation</td>
                <td>
                    <div class="checkbox-container">
                        <label><input type="checkbox"> Resignation</label>
                        <label><input type="checkbox"> Termination</label>
                        <label><input type="checkbox"> Retirement</label>
                    </div>
                </td>
                <td>Signature</td>
                <td></td>
            </tr>

            <tr>
                <th colspan="4">Department Clearance (fill up by department head)</th>
            </tr>
            <tr>
                <td>Manager/Supervisor Name</td>
                <td></td>
                <td>Designation</td>
                <td></td>
            </tr>
            <tr>
                <td>Comments/Remarks</td>
                <td></td>
                <td>Would you rehire?</td>
                <td>
                    <div class="checkbox-container">
                        <label><input type="checkbox"> Yes</label>
                        <label><input type="checkbox"> No</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Do you need a replacement?</td>
                <td>
                    <div class="checkbox-container">
                        <label><input type="checkbox"> Yes</label>
                        <label><input type="checkbox"> No</label>
                    </div>
                </td>
                <td>Signature</td>
                <td></td>
            </tr>

            <tr>
                <th colspan="4">HR & Payroll (fill up by HR department)</th>
            </tr>
            <tr>
                <td>Total Amount of Final Payment before deduction as persalary sheet</td>
                <td></td>
                <td>Deduction</td>
                <td>
                    <div class="checkbox-container">
                        <label><input type="checkbox"> ID Card/Business Card</label><br>
                        <label><input type="checkbox"> Advance/Loan/Tax</label><br>
                        <label><input type="checkbox"> Penalty</label><br>
                        <label><input type="checkbox"> Other _________________</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Add any Other Benefits</td>
                <td></td>
                <td>Comments</td>
                <td></td>
            </tr>
            <tr>
                <td>Total Amount of Final Payment (after deduction)</td>
                <td></td>
                <td>Officer Name & Signature</td>
                <td></td>
            </tr>

            <tr>
                <th colspan="4">Separation & Settlement (fill up by Accounts department)</th>
            </tr>
            <tr>
                <td>The final payment will be credited via</td>
                <td>
                    <div class="checkbox-container">
                        <label><input type="checkbox"> Bank Transfer (Salary Account)</label><br>
                        <label><input type="checkbox"> Bank Deposit</label><br>
                        <label><input type="checkbox"> Cash</label>
                        <label><input type="checkbox"> Bank Cheque</label>
                    </div>
                </td>
                <td>Payment Date:</td>
                <td>___/___/_____ </td>
            </tr>
            <tr>
                <td>Comments</td>
                <td></td>
                <td>Officer Name & Signature<br></td>
                <td></td>
            </tr>
            <tr class="page-break">
                <th colspan="4">Information Technology & Communication (fill up by IT Department)</th>
            </tr>
            <tr>
                <td>Remove User Email ({{ $employee->Official_Email }})</td>
                <td><input type="checkbox"> Yes</td>
                <td><input type="checkbox"> No</td>
                <td><input type="checkbox"> N/A</td>
            </tr>
            <tr>
                <td>User Email Backup</td>
                <td><input type="checkbox"> Yes</td>
                <td><input type="checkbox"> No</td>
                <td><input type="checkbox"> N/A</td>
            </tr>
            <tr>
                <td>Remove DMS Account</td>
                <td><input type="checkbox"> Yes</td>
                <td><input type="checkbox"> No</td>
                <td><input type="checkbox"> N/A</td>
            </tr>
            <tr>
                <td>Remove HRIS Account</td>
                <td><input type="checkbox"> Yes</td>
                <td><input type="checkbox"> No</td>
                <td><input type="checkbox"> N/A</td>
            </tr>
            <tr>
                <th colspan="4">Employee Asset Infromation</th>
            </tr>
            @foreach($assets as $item)
            <tr>
                <td>{{ $item->asset->deviceType->Name }} ({{ $item->asset->Device_Name }})</td>
                <td>S/N: {{ $item->asset->Serial_Number }}</td>
                <td><input type="checkbox"> Yes</td>
                <td><input type="checkbox"> No</td>
            </tr>
            @endforeach
            <tr>
                <td>Other Accessories (If any)</td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td style="padding: 10px;">Officer Name</td>
                <td></td>
                <td style="padding: 10px;">Signature</td>
                <td></td>
            </tr>
        </table>
    </div>

</body>

</html>