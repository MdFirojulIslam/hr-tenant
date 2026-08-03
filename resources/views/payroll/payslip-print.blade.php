<!DOCTYPE html>
<html lang="en">


<head>
  <meta charset="UTF-8">
  <title>Payslip - {{ $monthName }} {{ $year }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">


  <style>
    body {
      padding: 20px;
      font-family: 'Segoe UI', sans-serif;
    }


    .payslip {
      max-width: 900px;
      margin: auto;
      font-size: 12px;
    }


    .payslip-header {
      border-bottom: 2px solid #000;
      margin-bottom: 15px;
      text-align: center;
    }


    .net-pay {
      border-top: 2px solid #000;
      padding-top: 10px;
      text-align: center;
    }


    .amount {
      color: #198754;
      font-weight: bold;
      font-size: 18px;
    }
  </style>
</head>


<body>


  <div class="card mb-4 p-3 payslip">


    <div class="payslip-header">
      <h3>Salary Payslip</h3>
      <div>{{ $monthName }} {{ $year }}</div>
    </div>


    <div class="row mb-3">
      <div class="col-md-6">
        <div><b>Employee ID:</b> {{ $payslip->Employee_Id }}</div>
        <div><b>Full Name:</b> {{ $payslip->Full_Name }}</div>
        <div><b>Department:</b> {{ $payslip->departmentName }}</div>
        <div><b>Designation:</b> {{ $payslip->designationName }}</div>
      </div>


      <div class="col-md-6">
        <div><b>Salary Date:</b> {{ $payslip->salary_date }}</div>
        <div><b>Payment Method:</b> {{ ucfirst(strtolower($payslip->payment_method)) }}</div>
        <div><b>Bank Account:</b> {{ $payslip->bank_account_no }}</div>
        <div>
          <b>Status:</b> {{ ucfirst(strtolower($payslip->salary_status)) }}
        </div>
      </div>
    </div>


    <div class="row">
      <div class="col-12">


        <table class="table table-bordered">
          <thead class="table-light">
            <tr>
              <th class="text-center">Earnings</th>
              <th class="text-center">Deductions</th>
            </tr>
          </thead>


          <tbody>
            <tr>
              <td>
                <table class="table mb-0">
                  <tr>
                    <td>Basic Salary</td>
                    <td class="text-end">{{ $payslip->basic_salary }}</td>
                  </tr>
                  <tr>
                    <td>House Rent</td>
                    <td class="text-end">{{ $payslip->house_rent }}</td>
                  </tr>
                  <tr>
                    <td>Conveyance</td>
                    <td class="text-end">{{ $payslip->conveyance }}</td>
                  </tr>
                  <tr>
                    <td>Medical Allowance</td>
                    <td class="text-end">{{ $payslip->medical_allowance }}</td>
                  </tr>
                  <tr>
                    <td>Mobile Bill</td>
                    <td class="text-end">{{ $payslip->mobile_bill }}</td>
                  </tr>
                  <tr>
                    <td>Bonus</td>
                    <td class="text-end">{{ $payslip->bonus_amounts }}</td>
                  </tr>
                  <tr class="fw-bold">
                    <td>Total Earnings</td>
                    <td class="text-end">{{ $payslip->net_total_earning }}</td>
                  </tr>
                </table>
              </td>


              <td>
                <table class="table mb-0">
                  <tr>
                    <td>Provident Fund</td>
                    <td class="text-end">{{ $payslip->provident_fund }}</td>
                  </tr>
                  <tr>
                    <td>Income Tax</td>
                    <td class="text-end">{{ $payslip->income_tax }}</td>
                  </tr>
                  <tr>
                    <td>Loan Repayment</td>
                    <td class="text-end">{{ $payslip->monthly_loan_repayments }}</td>
                  </tr>
                  <tr class="fw-bold">
                    <td>Total Deductions</td>
                    <td class="text-end">{{ $payslip->net_total_deduction}}</td>
                  </tr>
                </table>
                <div class="net-pay mt-3"> Net Pay : <span class="amount">{{ ($payslip->net_total_earning) - ($payslip->net_total_deduction)}}</span> </div>
              </td>
            </tr>
          </tbody>
        </table>


      </div>
    </div>


  </div>


</body>


</html>
