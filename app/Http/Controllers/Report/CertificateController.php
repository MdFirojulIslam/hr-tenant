<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\employee;
use App\Models\employee_asset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PHPJasper\PHPJasper;
use NumberFormatter;

class CertificateController extends Controller
{
    function jasperView()
    {
        $input = storage_path('/jasper/report1.jasper');
        $output = storage_path('/jasper/report1');

        $options = [
            'format' => ['pdf'],
            'params' => [],
            'db_connection' => [
                'driver' => env('DB_CONNECTION'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'host' => env('DB_HOST'),
                'database' => env('DB_DATABASE'),
                'port' => env('DB_PORT')
            ]
        ];

        $jasper = new PHPJasper;

        $jasper->process(
            $input,
            $output,
            $options
        )->execute();

        $output_file = $output . ".pdf";


        if (file_exists($output_file)) {

            $headers = [
                'Content-Type' => 'application/pdf'
            ];

            return response()->download($output_file, 'Test File', $headers, 'inline');
        } else {
            abort(404, 'File not found!');
        }
    }


    function convertNumberToWords($number)
    {
        $units = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];
        $words = [
            0 => 'Zero',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety'
        ];

        if ($number == 0) return 'Zero Taka';

        $string = '';
        $place = 0;

        while ($number > 0) {
            $chunk = $number % 1000;
            $number = intval($number / 1000);

            if ($chunk > 0) {
                $chunkWords = '';
                if ($chunk >= 100) {
                    $chunkWords .= $words[intval($chunk / 100)] . ' Hundred ';
                    $chunk %= 100;
                }
                if ($chunk > 0) {
                    if ($chunk < 20) {
                        $chunkWords .= $words[$chunk];
                    } else {
                        $chunkWords .= $words[intval($chunk / 10) * 10];
                        $remainder = $chunk % 10;
                        if ($remainder > 0) {
                            $chunkWords .= '-' . $words[$remainder];
                        }
                    }
                }

                $string = $chunkWords . ' ' . $units[$place] . ' ' . $string;
            }
            $place++;
        }

        return trim($string) . ' Taka';
    }


    public function certificatePrint(Request $request)
    {
        $emp = $request->Employee_Id;

        if ($request->templete == 'noc') {
            $this->getNOC($emp);
            return response()->json(['url' => `/api/getNOC/$emp`]);
        } elseif ($request->templete == 'release-form') {
            $this->getReleaseForm($emp);
        }
    }

    public function getNOC($id)
    {
        $employee = Employee::with('official', 'official.department', 'official.designation')->where('id', $id)->first();
        $pdf = Pdf::loadView('report.certificates.noc', compact('employee'));
        return $pdf->stream($employee->Full_Name . '-noc.pdf');
    }

    public function getReleaseForm($id)
    {
        $employee = Employee::with('official', 'official.department', 'official.designation')->where('id', $id)->first();
        $pdf = Pdf::loadView('report.certificates.release-form', compact('employee'));
        return $pdf->stream($employee->Full_Name . '-release-form.pdf');
    }

    public function getSalaryCertificate(Request  $request, $id)
    {
        $salary = $request->query('salary');
        $inword = $this->convertNumberToWords($salary);
        $employee = Employee::with('official', 'official.department', 'official.designation')->where('id', $id)->first();
        $pdf = Pdf::loadView('report.certificates.salary-certificate', compact('employee', 'salary', 'inword'));
        return $pdf->stream($employee->Full_Name . '-Salary Certificate.pdf');
    }

    public function getSalaryBreakdown($id)
    {
        $employee = Employee::with('official', 'official.department', 'official.designation', 'employee_salary')->where('id', $id)->first();

        $inword = [
            'Basic' => $this->convertNumberToWords($employee->employee_salary->Basic),
            'House_Rent' => $this->convertNumberToWords($employee->employee_salary->House_Rent),
            'Conveyance' => $this->convertNumberToWords($employee->employee_salary->Conveyance),
            'Medical_Allowance' => $this->convertNumberToWords($employee->employee_salary->Medical_Allowance),
            'Travel_Allowance' => $this->convertNumberToWords($employee->employee_salary->Travel_Allowance),
            'Daily_Allowance' => $this->convertNumberToWords($employee->employee_salary->Daily_Allowance),
            'Mobile_Bill' => $this->convertNumberToWords($employee->employee_salary->Mobile_Bill),
            'Total_Payment' => $this->convertNumberToWords($employee->employee_salary->Total_Payment),
        ];

        $pdf = Pdf::loadView('report.certificates.salary-breakdown', compact('employee', 'inword'));
        return $pdf->stream($employee->Full_Name . '-Salary Breakdown.pdf');
    }


    public function getSalaryPayslip($id)
    {
        $employee = Employee::with('official', 'official.department', 'official.designation', 'employee_salary')->where('id', $id)->first();

        $salary = $employee->employee_salary;
        $pdf = Pdf::loadView('report.certificates.salary-payslip', compact('employee', 'salary'));
        return $pdf->stream($employee->Full_Name . '-Salary Payslip.pdf');
    }



    public function reqBankAcc(Request  $request, $id)
    {
        $salary = $request->query('salary');
        $employee = Employee::with('official', 'official.designation')->where('id', $id)->first();
        $pdf = Pdf::loadView('report.certificates.req-back-acc', compact('employee', 'salary'));
        return $pdf->stream($employee->Full_Name . '-Request Account.pdf');
    }

    public function getSeparationForm($id)
    {
        $employee = Employee::with('official', 'official.designation', 'employee_asset', 'employee_asset.asset', 'employee_asset.asset.deviceType')->where('id', $id)->first();
        $assets = employee_asset::with('employee', 'asset', 'asset.deviceType')->where('EID', $id)->get();

        $pdf = Pdf::loadView('report.certificates.separation-form', compact('employee', 'assets'))->setPaper('A4', 'portrait');;
        return $pdf->stream($employee->Full_Name . '-Separation Form.pdf');
    }


    public function getCertificateOfIncome($id)
    {
        $employee = employee::with(['official.designation', 'employee_salary'])->where('id', $id)->firstOrFail();
        $salary = $employee->employee_salary;

        // Define income period
        $period_from = '01-October-2023';
        $period_to = '30-June-2024';

        // Total income calculation
        $total_income =
            ($salary->Basic ?? 0) +
            ($salary->House_Rent ?? 0) +
            ($salary->Conveyance ?? 0) +
            ($salary->Medical_Allowance ?? 0) +
            ($salary->Festival_Bonus ?? 0) +
            ($salary->Overtime ?? 0) +
            ($salary->Food_Allowance ?? 0) +
            ($salary->Travel_Allowance ?? 0) +
            ($salary->Other_Allowance ?? 0);

        $tax_paid = 0.00;

        // Convert number to words
        $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        $tax_in_words = ucfirst($f->format($tax_paid));

        return view('certificate-of-income', compact(
            'employee',
            'salary',
            'period_from',
            'period_to',
            'total_income',
            'tax_paid',
            'tax_in_words'
        ));
    }
}
