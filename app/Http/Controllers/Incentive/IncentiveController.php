<?php

namespace App\Http\Controllers\Incentive;

use App\Http\Controllers\Controller;
use App\Models\Incentive;
use App\Models\IncentiveDetails;
use App\Models\employee;
use App\Models\department;
use App\Models\User;
use App\Exports\IncentiveExport;
use App\Exports\IncentiveTemplateExport;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\SimpleType\Jc;

class IncentiveController extends Controller
{
    public function index()
    {
        $incentives = Incentive::withCount('details')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($incentives);
    }

    public function departmentListIncentive(Request $request)
    {
        $response = department::select('id as department_id', 'Name as department_name')->get();
        return response()->json($response);
    }

    public function show($id)
    {
        $incentive = Incentive::findOrFail($id);

        $details = DB::table('incentive_details')
            ->join('employees', 'employees.id', '=', 'incentive_details.employee_id')
            ->join('departments', 'departments.id', '=', 'incentive_details.department_id')
            ->leftJoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftJoin('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->where('incentive_details.incentive_id', $id)
            ->select(
                'incentive_details.id as detail_id',
                'incentive_details.incentive_id',
                'incentive_details.employee_id',
                'incentive_details.department_id',
                'incentive_details.amounts',
                'incentive_details.remarks',
                'incentive_details.created_by',
                'incentive_details.updated_by',
                'employees.Full_Name as employee_name',
                'employees.Employee_Id as emp_code',
                'departments.Name as department_name',
                'designations.Name as designation_name'
            )
            ->orderBy('departments.Name', 'asc')
            ->orderBy('employees.Full_Name', 'asc')
            ->get();

        $incentive->setRelation('details', $details);

        $departments = DB::table('incentive_details')
            ->join('departments', 'departments.id', '=', 'incentive_details.department_id')
            ->select('departments.id as department_id', 'departments.Name as department_name')
            ->where('incentive_details.incentive_id', $id)
            ->distinct()
            ->get();

        return response()->json([
            'incentive' => $incentive,
            'departments' => $departments,
        ]);
    }

    public function getEmployeesByDepartments($deptIds)
    {
        $departmentIds = array_map('intval', explode(',', $deptIds));

        $employees = employee::select(
            'employees.id',
            'employees.Full_Name',
            'employees.Employee_Id',
            'departments.id as department_id',
            'departments.Name as department_name',
            'designations.Name as designation_name'
        )
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftJoin('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->whereIn('departments.id', $departmentIds)
            ->where('officials.Status', '=', 'N')
            ->orderBy('departments.Name', 'asc')
            ->orderBy('employees.Full_Name', 'asc')
            ->get();

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $request->validate([
            'incentive_periods' => 'required|string',
            'details' => 'required|array|min:1',
            'details.*.employee_id' => 'required|integer',
            'details.*.department_id' => 'required|integer',
            'details.*.amounts' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $userId = Auth::user()->EID;
            $totalAmount = collect($request->details)->sum('amounts');

            $incentive = Incentive::create([
                'incentive_periods' => $request->incentive_periods,
                'incentive_amount' => $totalAmount,
                'status' => 'Pending',
                'created_by' => $userId,
            ]);

            foreach ($request->details as $detail) {
                IncentiveDetails::create([
                    'incentive_id' => $incentive->id,
                    'employee_id' => $detail['employee_id'],
                    'department_id' => $detail['department_id'],
                    'amounts' => $detail['amounts'],
                    'remarks' => $detail['remarks'] ?? null,
                    'created_by' => $userId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Incentive created successfully',
                'incentive_id' => $incentive->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error while creating incentive: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'incentive_periods' => 'required|string',
            'details' => 'required|array|min:1',
            'details.*.employee_id' => 'required|integer',
            'details.*.department_id' => 'required|integer',
            'details.*.amounts' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $incentive = Incentive::findOrFail($id);
            $userId = Auth::user()->EID;
            $totalAmount = collect($request->details)->sum('amounts');

            $incentive->update([
                'incentive_periods' => $request->incentive_periods,
                'incentive_amount' => $totalAmount,
                'updated_by' => $userId,
            ]);

            $submittedEmployeeIds = collect($request->details)->pluck('employee_id')->toArray();

            IncentiveDetails::where('incentive_id', $id)
                ->whereNotIn('employee_id', $submittedEmployeeIds)
                ->delete();

            foreach ($request->details as $detail) {
                $existing = IncentiveDetails::where('incentive_id', $id)
                    ->where('employee_id', $detail['employee_id'])
                    ->first();

                if ($existing) {
                    $existing->update([
                        'department_id' => $detail['department_id'],
                        'amounts' => $detail['amounts'],
                        'remarks' => $detail['remarks'] ?? null,
                        'updated_by' => $userId,
                    ]);
                } else {
                    IncentiveDetails::create([
                        'incentive_id' => $incentive->id,
                        'employee_id' => $detail['employee_id'],
                        'department_id' => $detail['department_id'],
                        'amounts' => $detail['amounts'],
                        'remarks' => $detail['remarks'] ?? null,
                        'created_by' => $userId,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Incentive updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error while updating incentive: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function export($id, $format)
    {
        $incentive = Incentive::withCount('details')->findOrFail($id);

        $details = DB::table('incentive_details')
            ->join('employees', 'employees.id', '=', 'incentive_details.employee_id')
            ->join('departments', 'departments.id', '=', 'incentive_details.department_id')
            ->leftJoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftJoin('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->where('incentive_details.incentive_id', $id)
            ->select(
                DB::raw("CONCAT('ABNB', employees.Employee_Id) as employee_id"),
                'employees.Full_Name as employee_name',
                'departments.Name as department_name',
                'designations.Name as designation_name',
                'incentive_details.amounts',
                'incentive_details.remarks'
            )
            ->orderBy('departments.Name', 'asc')
            ->orderBy('employees.Full_Name', 'asc')
            ->get();

        $totalAmount = $details->sum('amounts');
        $totalEmployees = $details->count();
        $createdByUser = User::find($incentive->created_by);

        $fileName = 'Incentive_Report_' . str_replace(' ', '_', $incentive->incentive_periods);

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('payroll.incentive-report', compact(
                'incentive',
                'details',
                'totalAmount',
                'totalEmployees',
                'createdByUser'
            ));

            return $pdf->download($fileName . '.pdf');
        }

        if ($format === 'xlsx') {
            return Excel::download(
                new IncentiveExport(compact('incentive', 'details', 'totalAmount', 'totalEmployees')),
                $fileName . '.xlsx'
            );
        }

        if ($format === 'word') {
            return $this->generateWordReport($incentive, $details, $totalAmount, $totalEmployees, $createdByUser, $fileName);
        }

        return response()->json(['message' => 'Invalid format'], 400);
    }

    private function generateWordReport($incentive, $details, $totalAmount, $totalEmployees, $createdByUser, $fileName)
    {
        $phpWord = new PhpWord();

        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'orientation' => 'portrait',
            'marginTop' => 1000,
            'marginBottom' => 800,
            'marginLeft' => 800,
            'marginRight' => 800,
        ]);

        $section->addText(
            'Artisan Business Network Bangladesh',
            ['bold' => true, 'size' => 16],
            ['alignment' => Jc::CENTER]
        );

        $section->addText(
            'Employee Incentive Report',
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER]
        );

        $section->addTextBreak(1);

        $infoData = [
            ['Incentive Period:', $incentive->incentive_periods],
            ['Export Date:', now()->format('d M Y')],
            ['Status:', $incentive->status],
            ['Total Employees:', $totalEmployees],
        ];

        foreach ($infoData as $row) {
            $section->addText(
                $row[0] . ' ' . $row[1],
                ['bold' => false, 'size' => 10]
            );
        }

        $section->addTextBreak(1);

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ];

        $phpWord->addTableStyle('incentiveTable', $tableStyle);

        $table = $section->addTable('incentiveTable');

        $headers = ['SL', 'Employee ID', 'Employee Name', 'Department', 'Designation', 'Amount', 'Remarks'];
        $headerWidths = [600, 1400, 2500, 2000, 2000, 1400, 1500];

        $table->addRow();
        foreach ($headers as $i => $header) {
            $table->addCell($headerWidths[$i])->addText($header, ['bold' => true, 'size' => 9]);
        }

        foreach ($details as $i => $detail) {
            $table->addRow();
            $table->addCell($headerWidths[0])->addText($i + 1, ['size' => 9]);
            $table->addCell($headerWidths[1])->addText($detail->employee_id, ['size' => 9]);
            $table->addCell($headerWidths[2])->addText($detail->employee_name, ['size' => 9]);
            $table->addCell($headerWidths[3])->addText($detail->department_name, ['size' => 9]);
            $table->addCell($headerWidths[4])->addText($detail->designation_name ?? '-', ['size' => 9]);
            $table->addCell($headerWidths[5])->addText(number_format($detail->amounts, 2), ['size' => 9]);
            $table->addCell($headerWidths[6])->addText($detail->remarks ?? '-', ['size' => 9]);
        }

        $table->addRow();
        $table->addCell(9900, ['gridSpan' => 5])->addText('Total Incentive Amount', ['bold' => true, 'size' => 10]);
        $table->addCell(1400)->addText(number_format($totalAmount, 2), ['bold' => true, 'size' => 10]);
        $table->addCell(1500);

        $section->addTextBreak(2);

        $section->addText(
            'Prepared By: ' . ($createdByUser ? $createdByUser->name : '-'),
            ['bold' => true, 'size' => 10]
        );

        $section->addTextBreak(2);

        $signatureTable = $section->addTable([
            'borderSize' => 0,
            'width' => 9000,
            'unit' => TblWidth::TWIP
        ]);

        $signatureTable->addRow();
        $signatureTable->addCell(4500)->addText('_______________________', ['size' => 10]);
        $signatureTable->addCell(4500)->addText('_______________________', ['size' => 10]);

        $signatureTable->addRow();
        $signatureTable->addCell(4500)->addText('Prepared by', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        $signatureTable->addCell(4500)->addText('Approved by', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);

        while (ob_get_level()) {
            ob_end_clean();
        }

        $filePath = storage_path('app/' . $fileName . '.docx');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function downloadTemplate(Request $request)
    {
        $period = $request->query('period', '');
        $deptIds = $request->query('deptIds', '');

        $query = employee::select(
            'employees.id',
            'employees.Full_Name',
            'employees.Employee_Id',
            'departments.id as department_id',
            'departments.Name as department_name',
            'designations.Name as designation_name'
        )
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftJoin('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('officials.Status', '=', 'N');

        if ($deptIds) {
            $departmentIds = array_map('intval', explode(',', $deptIds));
            $query->whereIn('departments.id', $departmentIds);
        }

        $employees = $query
            ->orderBy('departments.Name', 'asc')
            ->orderBy('employees.Full_Name', 'asc')
            ->get();

        if ($employees->isEmpty()) {
            return response()->json(['message' => 'No employees found.'], 404);
        }

        $fileName = 'Incentive_Template' . ($period ? '_' . str_replace(' ', '_', $period) : '');

        return Excel::download(
            new IncentiveTemplateExport($employees, $period),
            $fileName . '.xlsx'
        );
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:10240',
        ]);

        try {
            $path = $request->file('file')->getRealPath();
            $spreadsheet = SpreadsheetIOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return response()->json([
                    'message' => 'Excel file is empty.'
                ], 422);
            }

            $headerRowIndex = null;
            $normalizedRows = array_values($rows);

            foreach ($normalizedRows as $rowIndex => $row) {
                $lowered = array_map(function ($cell) {
                    return strtolower(trim((string) $cell));
                }, $row);

                if (in_array('employee id', $lowered)) {
                    $headerRowIndex = $rowIndex;
                    break;
                }
            }

            if ($headerRowIndex === null) {
                return response()->json([
                    'message' => 'Missing required column: Employee ID.'
                ], 422);
            }

            $headers = array_map('strtolower', array_map('trim', $normalizedRows[$headerRowIndex]));

            $employeeIdIndex = array_search('employee id', $headers);
            $amountIndex = array_search('incentive amount', $headers);
            $remarksIndex = array_search('remarks', $headers);

            if ($amountIndex === false) {
                return response()->json([
                    'message' => 'Missing required column: Incentive Amount.'
                ], 422);
            }

            $dataRows = array_slice($normalizedRows, $headerRowIndex + 1);

            $results = [];
            $seenIds = [];
            $invalidIds = [];
            $duplicateIds = [];
            $empIdsToFetch = [];

            foreach ($dataRows as $index => $row) {
                if (empty(array_filter($row))) {
                    continue;
                }

                $rawEmpId = trim((string) ($row[$employeeIdIndex] ?? ''));
                $empId = str_replace('ABNB', '', $rawEmpId);

                if (empty($empId)) {
                    $invalidIds[] = $rawEmpId ?: "Row " . ($index + $headerRowIndex + 2);
                    continue;
                }

                if (in_array($empId, $seenIds)) {
                    $duplicateIds[] = $empId;
                    continue;
                }
                $seenIds[] = $empId;

                $amount = $row[$amountIndex] ?? '';
                $remark = $row[$remarksIndex] ?? '';

                if (!empty($amount) && !is_numeric($amount)) {
                    $invalidIds[] = $rawEmpId . ' (invalid amount)';
                    continue;
                }

                $results[] = [
                    'raw_emp_id' => $empId,
                    'incentive_amount' => $amount !== '' ? (float) $amount : '',
                    'remarks' => is_string($remark) ? $remark : '',
                ];

                $empIdsToFetch[] = $empId;
            }

            $errors = [];
            if (!empty($invalidIds)) {
                $errors[] = 'Invalid Employee IDs: ' . implode(', ', array_slice($invalidIds, 0, 10)) . (count($invalidIds) > 10 ? '...' : '');
            }
            if (!empty($duplicateIds)) {
                $errors[] = 'Duplicate Employee IDs: ' . implode(', ', array_slice($duplicateIds, 0, 10)) . (count($duplicateIds) > 10 ? '...' : '');
            }

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => implode(' | ', $errors),
                    'data' => [],
                ], 422);
            }

            $employeeMap = employee::select(
                'employees.id',
                'employees.Full_Name',
                'employees.Employee_Id',
                'departments.id as department_id',
                'departments.Name as department_name',
                'designations.Name as designation_name'
            )
                ->join('officials', 'officials.EID', '=', 'employees.id')
                ->join('departments', 'officials.Department_Id', '=', 'departments.id')
                ->leftJoin('designations', 'officials.Designation_Id', '=', 'designations.id')
                ->whereIn('employees.Employee_Id', $empIdsToFetch)
                ->where('officials.Status', '=', 'N')
                ->get()
                ->keyBy('Employee_Id');

            $finalResults = [];
            foreach ($results as $item) {
                $emp = $employeeMap->get($item['raw_emp_id']);
                if (!$emp) {
                    $invalidIds[] = $item['raw_emp_id'];
                    continue;
                }

                $finalResults[] = [
                    'id' => $emp->id,
                    'Employee_Id' => $emp->Employee_Id,
                    'Full_Name' => $emp->Full_Name,
                    'department_id' => $emp->department_id,
                    'department_name' => $emp->department_name,
                    'designation_name' => $emp->designation_name,
                    'incentive_amount' => $item['incentive_amount'],
                    'remarks' => $item['remarks'],
                ];
            }

            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found or inactive: ' . implode(', ', array_slice($invalidIds, 0, 10)) . (count($invalidIds) > 10 ? '...' : ''),
                    'data' => $finalResults,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $finalResults,
                'total' => count($finalResults),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process Excel file.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
