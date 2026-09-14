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

  @if ($payslip)
    @php
      $totalEarnings = (float) $payslip->basic_salary
        + (float) $payslip->house_rent
        + (float) $payslip->conveyance
        + (float) $payslip->medical_allowance
        + (float) $payslip->travel_allowance
        + (float) $payslip->daily_allowance
        + (float) $payslip->mobile_bill
        + (float) $payslip->other_allowance
        + (float) $payslip->earning_adjustment
        + (float) $payslip->bonus_amounts;

      $totalDeductions = (float) $payslip->provident_fund
        + (float) $payslip->income_tax
        + (float) $payslip->revenue_stamp
        + (float) $payslip->monthly_loan_repayments
        + (float) $payslip->deduction_adjustment;

      $netPayable = $totalEarnings - $totalDeductions;
    @endphp
    <div class="card mb-4 p-3 payslip">

      <div class="payslip-header">
        <h3>Salary Payslip</h3>
        <div>{{ $monthName }} {{ $year }}</div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <div><b>Employee ID:</b> ABNB{{ $payslip->Employee_Id }}</div>
          <div><b>Full Name:</b> {{ $payslip->Full_Name }}</div>
          <div><b>Department:</b> {{ $payslip->departmentName }}</div>
          <div><b>Designation:</b> {{ $payslip->designationName }}</div>
        </div>

        <div class="col-md-6">
          <div><b>Salary Date:</b> {{ $payslip->salary_date }}</div>
          <div><b>Payment Method:</b> {{ $payslip->payment_method }}</div>
          <div><b>Bank Account:</b> {{ $payslip->bank_account_no }}</div>
          <div><b>Status:</b> {{ $payslip->salary_status }}</div>
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
                      <td class="text-end">{{ number_format((float) $payslip->basic_salary, 2) }}</td>
                    </tr>
                    <tr>
                      <td>House Rent</td>
                      <td class="text-end">{{ number_format((float) $payslip->house_rent, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Conveyance</td>
                      <td class="text-end">{{ number_format((float) $payslip->conveyance, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Medical Allowance</td>
                      <td class="text-end">{{ number_format((float) $payslip->medical_allowance, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Travel Allowance</td>
                      <td class="text-end">{{ number_format((float) $payslip->travel_allowance, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Daily Allowance</td>
                      <td class="text-end">{{ number_format((float) $payslip->daily_allowance, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Mobile Bill</td>
                      <td class="text-end">{{ number_format((float) $payslip->mobile_bill, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Other Allowance</td>
                      <td class="text-end">{{ number_format((float) $payslip->other_allowance, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Earning Adjustment</td>
                      <td class="text-end">{{ number_format((float) $payslip->earning_adjustment, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Bonus</td>
                      <td class="text-end">{{ number_format((float) $payslip->bonus_amounts, 2) }}</td>
                    </tr>
                    <tr class="fw-bold">
                      <td>Total Earnings</td>
                      <td class="text-end">{{ number_format($totalEarnings, 2) }}</td>
                    </tr>
                  </table>
                </td>

                <td>
                  <table class="table mb-0">
                    <tr>
                      <td>Provident Fund</td>
                      <td class="text-end">{{ number_format((float) $payslip->provident_fund, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Income Tax</td>
                      <td class="text-end">{{ number_format((float) $payslip->income_tax, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Revenue Stamp</td>
                      <td class="text-end">{{ number_format((float) $payslip->revenue_stamp, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Loan Repayment</td>
                      <td class="text-end">{{ number_format((float) $payslip->monthly_loan_repayments, 2) }}</td>
                    </tr>
                    <tr>
                      <td>Deduction Adjustment</td>
                      <td class="text-end">{{ number_format((float) $payslip->deduction_adjustment, 2) }}</td>
                    </tr>
                    <tr class="fw-bold">
                      <td>Total Deductions</td>
                      <td class="text-end">{{ number_format($totalDeductions, 2) }}</td>
                    </tr>
                  </table>
                  <div class="net-pay mt-3">Net Pay: <span class="amount">{{ number_format($netPayable, 2) }}</span></div>
                </td>
              </tr>
            </tbody>
          </table>

        </div>
      </div>

    </div>
  @else
    <div class="card mb-4 p-3 payslip">
      <div class="payslip-header">
        <h3>Salary Payslip</h3>
        <div>{{ $monthName }} {{ $year }}</div>
      </div>
      <div class="text-center">No payslip found for this employee.</div>
    </div>
  @endif

</body>

</html>