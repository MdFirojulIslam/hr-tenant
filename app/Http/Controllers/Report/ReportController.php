<?php

namespace App\Http\Controllers\Report;

use App\Exports\AttendanceReportExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Http\Controllers\Controller;
use App\Models\app_attendance;
use App\Models\attendence;
use App\Models\emplooyee_expense;
use App\Models\employee;
use App\Models\holiday;
use App\Models\leave;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function leaveReport($params)
    {
        parse_str($params, $queryParams);

        $department = $queryParams['department'] ?? null;
        $employeeId = $queryParams['Employee_Id'] ?? null;
        $fromDate = $queryParams['from_date'] ?? null;
        $toDate = $queryParams['to_date'] ?? null;

        $leavesQuery = leave::select(
            'leaves.id',
            'employees.id as EID',
            'employees.Full_Name',
            'employees.Employee_Id',
            'leaves.From_Date',
            'leaves.To_Date',
            'leaves.Status',
            'leaves.Purpose',
            'leaves.Notification',
            'leave_types.Name as Leave_Type',
            'leave_types.id as leave_id',
            'designations.Name as designation',
            'departments.Name as department',
            'officials.Supervisor_Id',
            DB::raw('DATEDIFF(leaves.To_Date, leaves.From_Date) + 1 as Total_Days')
        )
            ->join('employees', 'leaves.EID', '=', 'employees.id')
            ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id');

        // Apply filters
        if (!empty($department)) {
            $leavesQuery->where('departments.id', '=', $department);
        }

        if (!empty($employeeId)) {
            $leavesQuery->where('leaves.EID', '=', $employeeId);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $leavesQuery->whereBetween('leaves.From_Date', [$fromDate, $toDate]);
        }

        // Fetch the leaves
        $leaves = $leavesQuery->orderby('leaves.From_Date', 'asc')->get();

        // Calculate the subtotal
        $subtotal = $leaves->sum('Total_Days');

        // Pass the data to the PDF
        $pdf = Pdf::loadView('report.leave.leave-report', compact('leaves', 'subtotal'))
            ->setPaper('A4', 'portrait');

        // Return the PDF with the generated name
        return $pdf->stream('report');
    }


    // public function attendanceReport($params)
    // {
    //     // ---- 1. Parse query‑string -------------------------------------------
    //     parse_str($params, $queryParams);

    //     $department = $queryParams['department']   ?? null;
    //     $employeeId = $queryParams['Employee_Id'] ?? null;
    //     $fromDate   = $queryParams['from_date']   ?? null;
    //     $toDate     = $queryParams['to_date']     ?? null;

    //     // ---- 2. Core attendance rows  ----------------------------------------
    //     $attendanceQuery = attendence::selectRaw(
    //         'employees.id as EID,
    //          employees.Full_Name,
    //          employees.Employee_Id,
    //          departments.Name as department,
    //          designations.Name as designation,
    //          DATE(attendences.Date) as date,
    //          DAYNAME(attendences.Date) as weekday,
    //          MIN(attendences.Time_In) as time_in,
    //          MAX(attendences.Time_Out) as time_out'
    //     )
    //         ->join('employees', 'employees.id', '=', 'attendences.EID')
    //         ->join('officials', 'officials.EID', '=', 'employees.id')
    //         ->join('departments', 'officials.Department_Id', '=', 'departments.id')
    //         ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
    //         ->groupBy(
    //             'employees.id',
    //             'employees.Full_Name',
    //             'employees.Employee_Id',
    //             'departments.Name',
    //             'designations.Name',
    //             DB::raw('DATE(attendences.Date)'),
    //             DB::raw('DAYNAME(attendences.Date)')
    //         );

    //     // ---- 3. Optional filters ---------------------------------------------
    //     if ($department) {
    //         $attendanceQuery->where('departments.id', $department);
    //     }
    //     if ($employeeId) {
    //         $attendanceQuery->where('employees.id', $employeeId);
    //     }
    //     if ($fromDate && $toDate) {
    //         $attendanceQuery->whereBetween(DB::raw('DATE(attendences.Date)'), [$fromDate, $toDate]);
    //     }

    //     // ---- 4. Pull approved leave for the same window ----------------------
    //     $leaveData = leave::select(
    //         'leaves.EID',
    //         'leaves.From_Date',
    //         'leaves.To_Date',
    //         'leave_types.Name as leave_type'
    //     )
    //         ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
    //         ->where('leaves.Status', 'Approved')
    //         ->when($employeeId, fn($q) => $q->where('leaves.EID', $employeeId))
    //         ->when(
    //             $fromDate && $toDate,
    //             fn($q) =>
    //             $q->whereDate('leaves.To_Date', '>=', $fromDate)
    //                 ->whereDate('leaves.From_Date', '<=', $toDate)
    //         )
    //         ->get()
    //         ->groupBy('EID');

    //     // ---- 5. Fetch rows & annotate leave‑days -----------------------------
    //     $attendanceRecords = $attendanceQuery
    //         ->orderBy('date', 'asc')
    //         ->get()
    //         ->map(function ($row) use ($leaveData) {
    //             // mark Time‑In/Out as the leave‑type if the date falls inside leave
    //             $employeeLeaves = $leaveData[$row->EID] ?? collect();
    //             $onLeave = $employeeLeaves->first(
    //                 fn($lv) =>
    //                 $row->date >= $lv->From_Date && $row->date <= $lv->To_Date
    //             );
    //             if ($onLeave) {
    //                 $row->time_in  = $onLeave->leave_type;
    //                 $row->time_out = $onLeave->leave_type;
    //             }
    //             return $row;
    //         });


    //     // ---- 6. Render PDF ----------------------------------------------------
    //     $pdf = Pdf::loadView(
    //         'report.attendance.attendance-report',
    //         compact('attendanceRecords')
    //     )
    //         ->setPaper('A4', 'portrait')
    //         ->setOptions(['defaultFont' => 'Noto Sans Bengali']);

    //     return $pdf->stream('attendance-report.pdf');
    // }

    public function attendanceReport($params)
    {
        /* ---------------------------------------------------------------------
     | 0.  Who is asking?
     |-------------------------------------------------------------------- */
        $user = Auth::user();            // the signed‑in user
        $isAdmin = in_array($user->role_id, [1, 2]);

        /* ---------------------------------------------------------------------
     | 1.  Build the *visible* EID list
     |-------------------------------------------------------------------- */
        if ($isAdmin) {
            // HR / Super‑admin → everything is visible
            $visibleEids = Employee::pluck('id');          // Collection<int>
        } else {
            // Regular supervisor → only self + subordinates
            $visibleEids = $this->collectSubordinateIds($user->EID)
                ->prepend($user->EID)      // include myself
                ->values();                // tidy up keys
        }

        /* ---------------------------------------------------------------------
     | 2.  Parse query‑string and keep only allowed IDs
     |-------------------------------------------------------------------- */
        parse_str($params, $qp);

        $department = $qp['department']   ?? null;
        $employeeId = $qp['Employee_Id']  ?? null;   // single employee filter
        $fromDate   = $qp['from_date']    ?? null;
        $toDate     = $qp['to_date']      ?? null;

        // force the employee filter (if present) to remain inside the visible list
        if ($employeeId && ! $visibleEids->contains($employeeId)) {
            // optional: return an error / empty PDF
            return abort(403, 'You have no access to this employee');
        }

        /* ---------------------------------------------------------------------
     | 3.  Core attendance query  (unchanged except for whereIn)
     |-------------------------------------------------------------------- */
        $attendanceQuery = attendence::selectRaw(
            'employees.id as EID,
             employees.Full_Name,
             employees.Employee_Id,
             departments.Name  as department,
             designations.Name as designation,
             DATE(attendences.Date)   as date,
             DAYNAME(attendences.Date) as weekday,
             MIN(attendences.Time_In)  as time_in,
             MAX(attendences.Time_Out) as time_out'
        )
            ->join('employees',    'employees.id',  '=', 'attendences.EID')
            ->join('officials',    'officials.EID', '=', 'employees.id')
            ->join('departments',  'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->whereIn('employees.id', $visibleEids)      // ★★ <-- NEW
            ->groupBy(
                'employees.id',
                'employees.Full_Name',
                'employees.Employee_Id',
                'departments.Name',
                'designations.Name',
                DB::raw('DATE(attendences.Date)'),
                DB::raw('DAYNAME(attendences.Date)')
            );

        // optional filters (all unchanged)
        if ($department) {
            $attendanceQuery->where('departments.id', $department);
        }
        if ($employeeId) {
            $attendanceQuery->where('employees.id',  $employeeId);
        }
        if ($fromDate && $toDate) {
            $attendanceQuery->whereBetween(
                DB::raw('DATE(attendences.Date)'),
                [$fromDate, $toDate]
            );
        }

        /* ---------------------------------------------------------------------
     | 4.  Approved leave window (unchanged)
     |-------------------------------------------------------------------- */
        $leaveData = leave::select(
            'leaves.EID',
            'leaves.From_Date',
            'leaves.To_Date',
            'leave_types.Name as leave_type'
        )
            ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
            ->where('leaves.Status', 'Approved')
            ->whereIn('leaves.EID', $visibleEids)     // keep same visibility
            ->when($employeeId, fn($q) => $q->where('leaves.EID', $employeeId))
            ->when(
                $fromDate && $toDate,
                fn($q) =>
                $q->whereDate('leaves.To_Date', '>=', $fromDate)
                    ->whereDate('leaves.From_Date', '<=', $toDate)
            )
            ->get()
            ->groupBy('EID');

        /* ---------------------------------------------------------------------
     | 5.  Fetch, flag leave days, render
     |-------------------------------------------------------------------- */
        $attendanceRecords = $attendanceQuery->orderBy('date', 'asc')->get()
            ->map(function ($row) use ($leaveData) {
                $lv = $leaveData[$row->EID] ?? collect();
                $hit = $lv->first(
                    fn($l) =>
                    $row->date >= $l->From_Date && $row->date <= $l->To_Date
                );
                if ($hit) {
                    $row->time_in  = $hit->leave_type;
                    $row->time_out = $hit->leave_type;
                }
                return $row;
            });

        $pdf = Pdf::loadView(
            'report.attendance.attendance-report',
            compact('attendanceRecords')
        )
            ->setPaper('A4', 'portrait')
            ->setOptions(['defaultFont' => 'Noto Sans Bengali']);

        return $pdf->stream('attendance-report.pdf');
    }

    /**
     * Return a flat Collection of all subordinate EIDs beneath a supervisor.
     * Mirrors your existing getSubordinates() logic but returns IDs only.
     */
    private function collectSubordinateIds($supervisorId): \Illuminate\Support\Collection
    {
        $ids = collect();
        $queue = collect([$supervisorId]);

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();

            $children = Employee::query()
                ->join('officials', 'officials.EID', '=', 'employees.id')
                ->where('officials.Supervisor_Id', $current)
                ->pluck('employees.id');

            $ids   = $ids->merge($children);
            $queue = $queue->merge($children);   // breadth‑first walk
        }

        return $ids->unique();
    }


    public function appAttendanceReport($params)
    {
        parse_str($params, $queryParams);

        $department = $queryParams['department'] ?? null;
        $employeeId = $queryParams['Employee_Id'] ?? null;
        $fromDate = $queryParams['from_date'] ?? null;
        $toDate = $queryParams['to_date'] ?? null;

        $attendanceQuery = app_attendance::selectRaw(
            'employees.id as EID, 
         employees.Full_Name, 
         employees.Employee_Id, 
         departments.Name as department, 
         designations.Name as designation,
         DATE(app_attendances.updated_at) as date, 
         DAYNAME(app_attendances.updated_at) as weekday,
         MIN(CASE WHEN app_attendances.status = "in" THEN TIME(app_attendances.updated_at) END) as time_in, 
         MAX(CASE WHEN app_attendances.status = "out" THEN TIME(app_attendances.updated_at) END) as time_out, 
         MIN(CASE WHEN app_attendances.status = "in" THEN app_attendances.village END) as in_location, 
         MAX(CASE WHEN app_attendances.status = "out" THEN app_attendances.village END) as out_location, 
         MIN(CASE WHEN app_attendances.status = "in" THEN app_attendances.image_path END) as in_image, 
         MAX(CASE WHEN app_attendances.status = "out" THEN app_attendances.image_path END) as out_image'
        )
            ->join('employees', 'app_attendances.EID', '=', 'employees.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->groupBy(
                'employees.id',
                'employees.Full_Name',
                'employees.Employee_Id',
                'departments.Name',
                'designations.Name',
                DB::raw('DATE(app_attendances.updated_at)'),
                DB::raw('DAYNAME(app_attendances.updated_at)')
            );

        // Apply filters
        if (!empty($department)) {
            $attendanceQuery->where('departments.id', '=', $department);
        }

        if (!empty($employeeId)) {
            $attendanceQuery->where('employees.id', '=', $employeeId);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $attendanceQuery->whereBetween(DB::raw('DATE(app_attendances.updated_at)'), [$fromDate, $toDate]);
        }

        $attendanceRecords = $attendanceQuery->orderBy('date', 'asc')->get();

        $pdf = Pdf::loadView('report.attendance.app-attendance-report', compact('attendanceRecords'))
            ->setPaper('A4', 'portrait');

        // Set the default font to Bangla
        $pdf->setOptions(['defaultFont' => 'Noto Sans Bengali']);

        return $pdf->stream('attendance-report.pdf');
    }


    public function expenseReportData($expense, $id)
    {
        $groupedExpenses = $expense->groupBy('Date')->map(function ($items) use (&$grossTa, &$grossDa, &$grossOe, &$grossTotal) {
            $ta = $items->where('Tr_Type', 'ta');
            $da = $items->where('Tr_Type', 'da');
            $oe = $items->where('Tr_Type', 'oe');

            $taTotal = $ta->sum('Amount');
            $daTotal = $da->sum('Amount');
            $oeTotal = $oe->sum('Amount');
            $totalAmount = $items->sum('Amount');

            $grossTa += $taTotal;
            $grossDa += $daTotal;
            $grossOe += $oeTotal;
            $grossTotal += $totalAmount;

            return [
                'ta' => $ta,
                'ta_total' => $taTotal,
                'da' => $da,
                'da_total' => $daTotal,
                'oe' => $oe,
                'oe_total' => $oeTotal,
                'total_amount' => $totalAmount,
            ];
        });

        $employee = employee::with('official', 'official.designation', 'official.department', 'official.supervisor')->where('id', $id)->first();
        $status = collect($expense)->max('Status');

        $data = [
            'expenses' => $groupedExpenses,
            'employee' => $employee,
            'gross_ta' => $grossTa,
            'gross_da' => $grossDa,
            'gross_oe' => $grossOe,
            'gross_total' => $grossTotal,
            'status' => $status
        ];
        return $data;
    }


    public function getExpenseReport(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');
        $userId = Auth::user()->EID;

        if ($request->query('eid')) {
            $userId = $request->query('eid');
        }

        $expenses = emplooyee_expense::with('town')
            ->where('EID', $userId)
            ->whereMonth('Date', $month + 1)
            ->whereYear('Date', $year)
            ->orderBy('Date', 'asc')
            ->get();

        $data = $this->expenseReportData($expenses, $userId);

        //Filename format for month
        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];
        $monthName = $monthNames[$month + 1];

        $detail = $request->query('detail');

        if ($detail) {
            $pdf = Pdf::loadView('report.expense.expense', $data);
            $fileName = "Expense_Report_{$data['employee']->Full_Name}_{$monthName}_{$year}.pdf";
        } else {
            $pdf = Pdf::loadView('report.expense.expenseSummery', $data);
            $fileName = "Expense_Report_{$data['employee']->Full_Name}_{$monthName}_{$year}.pdf";
        }



        $download = $request->query('down');
        if ($download) {
            return $pdf->download($fileName);
        } else {
            return $pdf->stream($fileName);
        }
    }


    public function holidayReport(Request $request)
    {
        $year = $request->query('year');
        $holiday = holiday::whereYear('From_date', $year)
            ->orderBy('From_Date', 'asc')
            ->get()
            ->map(function ($item) {
                $fromDate = \Carbon\Carbon::parse($item->From_Date);
                $toDate = \Carbon\Carbon::parse($item->To_Date);

                // Generate date range and calculate weekdays
                $dateRange = [];
                while ($fromDate->lte($toDate)) {
                    $dateRange[] = [
                        'date' => $fromDate->toDateString(),
                        'weekday' => $fromDate->format('l'),
                    ];
                    $fromDate->addDay();
                }

                $item->date_range = $dateRange;
                return $item;
            });

        $data = [
            'holidays' => $holiday,
            'year' => $year,
        ];

        $pdf = Pdf::loadView('report.leave.holiday-report', $data);
        $fileName = "Holiday_Report_{$year}.pdf";

        return $pdf->stream($fileName);
    }



    public function downloadAttendanceExcel(Request $request)
    {
        $month = $request->month; 
        $year  = $request->year;  

        return Excel::download(new AttendanceReportExport(8, 2025), 'attendance-report.xlsx');
    }
}
