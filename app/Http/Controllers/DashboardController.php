<?php




namespace App\Http\Controllers;




use App\Models\app_attendance;
use App\Models\department;
use App\Models\leave;
use App\Models\official;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\attendence;
use App\Models\EmpInfoDocumentation;
use App\Models\holiday;
use Illuminate\Support\Facades\App;
use DateTime;
use App\Models\emplooyee_expense;
use Override;


class DashboardController extends Controller
{
    public function index()
    {
        $currentYear = now()->year;
        $department = department::count();
        $employee_act =  official::where('Status', 'N')->count();
        $pendingLeave = leave::where('Status', 'Pending')->whereYear('From_Date', $currentYear)->count();
        $employee_inact = official::where('Status', 'Y')->count();




        $deptWiseLeave = $this->deptWiseLeave();
        $deptWiseEmp = $this->deptWiseEmp();
        $averageAge = $this->averageAge();
        $divisionWiseEmpCount = $this->divisionWiseEmpCount();
        $averageTenure = $this->averageTenure();
        $turnoverData = $this->getTurnoverData();
        $expenseDate = $this->getUsersExpense();
        $genderData = $this->getGenderDiversity();
        $getMonthlyAttendanceChart = $this->getMonthlyAttendanceChart();




        return [
            'department' => $department,
            'employee_act' => $employee_act,
            'pending_leave' => $pendingLeave,
            'employee_inact' => $employee_inact,
            'dept_wise_leave' => $deptWiseLeave,
            'dept_wise_Emp' => $deptWiseEmp,
            'average_age' => $averageAge,
            'division_wise_Emp' => $divisionWiseEmpCount,
            'average_tenure' => $averageTenure,
            'turnover_data' => $turnoverData,
            'expense_date' => $expenseDate,
            'gender_data' => $genderData,
            'attendace_data' => $getMonthlyAttendanceChart,
        ];
    }




    public function deptWiseLeave()
    {
        $deptWiseLeave = leave::select('departments.Name as department_name', DB::raw('count(leaves.id) as leave_count'))
            ->join('employees', 'leaves.employee_id', '=', 'employees.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->groupBy('departments.id', 'departments.name')
            ->get();




        return $deptWiseLeave;
    }




    public function deptWiseEmp()
    {
        $deptWiseEmp = Department::selectRaw('COUNT(officials.id) as employee_count, departments.Name')
            ->join('officials', 'officials.Department_Id', '=', 'departments.id')
            ->groupBy('departments.id', 'departments.Name')
            ->where('officials.Status', 'N')
            ->get();




        return $deptWiseEmp;
    }




    public function averageAge()
    {
        $averageAge = DB::table('employees')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->where('officials.Status', 'N')
            ->whereNotNull('employees.DOB')
            ->selectRaw('AVG(TIMESTAMPDIFF(YEAR, employees.DOB, CURDATE())) as average_age')
            ->value('average_age');

        $averageAge = round($averageAge);


        return $averageAge;
    }




    public function divisionWiseEmpCount()
    {
        $divisionWiseEmpCount = Department::selectRaw('COUNT(officials.id) as employee_count, departments.Division')
            ->join('officials', 'officials.Department_Id', '=', 'departments.id')
            ->groupBy('departments.Division')
            ->where('officials.Status', 'N')
            ->get();


        return $divisionWiseEmpCount;
    }


    public function averageTenure()
    {
        $averageTenure = DB::table('employees')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->where('officials.Status', 'N')
            ->whereNotNull('officials.DOJ')
            ->selectRaw('AVG(TIMESTAMPDIFF(MONTH, officials.DOJ, CURDATE()) / 12) as avg_years')
            ->value('avg_years');


        // Optional: round the result
        $averageTenure = round($averageTenure, 2);


        return $averageTenure;
    }


    public function getTurnoverData()
    {
        $turnoverData = [];
        $currentYear = now()->year;


        for ($year = 2023; $year <= $currentYear; $year++) {
            $monthlyCounts = [];


            for ($month = 1; $month <= 12; $month++) {
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
                $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();


                $count = DB::table('employees')
                    ->join('officials', 'officials.EID', '=', 'employees.id')
                    ->where('DOJ', '<=', $endOfMonth)
                    ->where(function ($query) use ($startOfMonth) {
                        $query->whereNull('DOL')
                            ->orWhere('DOL', '>=', $startOfMonth);
                    })
                    ->count();


                $monthlyCounts[] = $count;
            }


            $averageMonthlyHeadcount = count($monthlyCounts) > 0 ? round(array_sum($monthlyCounts) / count($monthlyCounts), 2) : 0;


            $leftCount = DB::table('employees')
                ->join('officials', 'officials.EID', '=', 'employees.id')
                ->whereYear('DOL', $year)
                ->count();


            $turnoverRate = $averageMonthlyHeadcount > 0
                ? round(($leftCount / $averageMonthlyHeadcount) * 100, 2)
                : 0;


            $turnoverData[] = [
                'year' => $year,
                'left' => $leftCount,
                'average_headcount' => $averageMonthlyHeadcount,
                'turnover_rate' => $turnoverRate
            ];
        }
        return $turnoverData;
    }








    //TADA TOTAL VALUE MONTH WISE
    public function getUsersExpense()
    {
        DB::statement("SET SQL_MODE=''");




        $year = now()->year;




        $expenses = DB::table('emplooyee_expenses')
            ->selectRaw('MONTH(date) as month, SUM(Amount) as total_expense')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy('month')
            ->get();




        // Format results with month names (optional)
        $formattedExpenses = $expenses->map(function ($item) {
            return [
                'month' => \Carbon\Carbon::create()->month($item->month)->format('F'),
                'total_expense' => number_format($item->total_expense, 2, '.', '')
            ];
        });
        return $formattedExpenses;
    }




    public function getGenderDiversity()
    {
        $genderCounts = DB::table('employees')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->where('officials.Status', 'N')
            ->whereIn('employees.Gender', ['M', 'F'])
            ->select('employees.Gender', DB::raw('COUNT(*) as count'))
            ->groupBy('employees.Gender')
            ->pluck('count', 'Gender');




        $total = $genderCounts->sum();




        $genderRatio = $genderCounts->map(function ($count) use ($total) {
            return round(($count / $total) * 100, 2); // Percentage
        });
        return $genderRatio;
    }








    public function getMonthlyAttendanceChart()
    {
        $startDate = Carbon::now()->startOfYear(); // Jan 1
        $endDate = Carbon::now()->endOfYear();     // Dec 31


        $monthlyData = DB::table('attendences')
            ->selectRaw('MONTH(attendences.Date) as month, attendences.Status, COUNT(*) as count')
            ->join('employees', 'attendences.EID', '=', 'employees.id')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->whereBetween('attendences.Date', [$startDate, $endDate])
            ->where('officials.Job_Location_Id', 1)
            ->groupBy(DB::raw('MONTH(attendences.Date)'), 'attendences.Status')
            ->orderBy('month')
            ->get();




        // Initialize data
        $data = [
            'months' => [],
            'present' => [],
            'absent' => [],
            'leave' => [],
        ];




        foreach ($monthlyData as $row) {
            $month = (int) $row->month;
            $data['months'][$month] = Carbon::create()->month($month)->format('F');
            if ($row->Status === 'P' || $row->Status === 'L') {
                $data['present'][$month] = $row->count;
            } elseif ($row->Status === 'A') {
                $data['absent'][$month] = $row->count;
            } elseif ($row->Status === 'C' || $row->Status === 'E' || $row->Status === 'S') {
                $data['leave'][$month] = $row->count;
            }
        }




        return $data;
    }




    public function departmentList()
    {
        $departments = Department::select('id', 'Name')->get();
        $departments->prepend([
            'id' => 0,
            'Name' => 'All Departments'
        ]);
        return $departments;
    }




    public function departmentFilter(Request $request)
    {
        $department = $request->query('department_id');
        $date = $request->query('date');
        $departmentTotal = department::where('id', $department)
            ->count();

        $startOfMonth = now()->startOfMonth()->toDateString();


        // $endOfMonth = now()->endOfMonth()->toDateString();




        $activeEmployee = official::where('Status', 'N')
            ->where('Department_Id', $department)
            ->count();
        $inactiveEmployee = official::where('Status', 'Y')
            ->where('Department_Id', $department)
            ->count();

        $employessDepartmentWise = official::where('Department_Id', $department)
            ->where('Status', 'N')
            ->select('EID')
            ->get()
            ->toArray();

        $averageTenure = official::where('officials.Status', 'N')
            ->whereIn('EID', $employessDepartmentWise)
            ->whereNotNull('DOJ')
            ->selectRaw("AVG(TIMESTAMPDIFF(MONTH, DOJ, '$date') / 12) as avg_years")
            ->value('avg_years');

        $averageTenure = round($averageTenure, 2);

        $averageAge = DB::table('employees')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->where('officials.Status', 'N')
            ->whereIn('employees.id', $employessDepartmentWise)
            ->whereNotNull('employees.DOB')
            ->selectRaw("AVG(TIMESTAMPDIFF(YEAR, employees.DOB, '$date')) as average_age")
            ->value('average_age');

        $averageAge = round($averageAge);

        $totalExpense = emplooyee_expense::whereIn('EID', $employessDepartmentWise)
            ->whereDate('Date', '>=', $startOfMonth)
            ->whereDate('Date', '<=', $date)
            ->sum('Amount');

        $gulshanTotalOfficeEmployee = official::where('Job_Location_Id', 1)
            ->whereIn('EID', $employessDepartmentWise)
            ->where('Status', 'N')
            ->count();


        $warehouseTotalEmployee = official::where('Job_Location_Id', 2)
            ->where('Status', 'N')
            ->whereIn('EID', $employessDepartmentWise)
            ->count();

        $fieldforceTotalEmployee = official::where('Job_Location_Id', 3)
            ->where('Status', 'N')
            ->whereIn('EID', $employessDepartmentWise)
            ->count();

        $presentMachineAtt = attendence::whereIn('Status', ['P', 'L'])
            ->whereDate('Date', $date)
            ->whereIn('EID', $employessDepartmentWise)
            ->distinct('EID')
            ->count('EID');

        $lateMachineEmp = attendence::where('Status', 'L')
            ->whereDate('Date', $date)
            ->whereIn('EID', $employessDepartmentWise)
            ->distinct('EID')
            ->count('EID');

        $presentAppAtt = app_attendance::where('Status', 'In')
            ->whereIn('EID', $employessDepartmentWise)
            ->whereDate('created_at', $date)
            ->count();

        $active_employees = official::where('Status', 'N')
            ->whereIn('EID', $employessDepartmentWise)
            ->count();

        $totalLeaveCount = leave::where('status', 'Approved')
            ->whereDate('from_date', '>=', $date)
            ->whereDate('to_date', '<=', $date)
            ->whereIn('employee_id', $employessDepartmentWise)
            ->distinct('employee_id')
            ->count();

        $totalPendingLeaveCount = leave::whereIN('status', ['Pending', 'Approved (SV)'])
            ->whereDate('from_date', '>=', $date)
            ->whereDate('to_date', '<=', $date)
            ->whereIn('employee_id', $employessDepartmentWise)
            ->distinct('employee_id')
            ->count();

        // $inLeave = $totalLeaveCount + $totalPendingLeaveCount;

        $appattendanceTime = app_attendance::whereDate('created_at', $date)
            ->whereIn('EID', $employessDepartmentWise)
            ->selectRaw('EID,TIME(MAX(CASE WHEN status = "in" THEN created_at END)) as in_time,TIME(MAX(CASE WHEN status = "out" THEN created_at END)) as out_time')
            ->groupBy('EID')
            ->get()
            ->toArray();

        $matchineAttendanceTime = attendence::whereDate('Date', $date)
            ->whereIn('EID', $employessDepartmentWise)
            ->selectRaw('EID, MIN(Time_In) as in_time, MAX(Time_Out) as out_time')
            ->groupBy('EID')
            ->get()
            ->toArray();

        $totalAppHours = 0;
        $appLate = 0;

        foreach ($appattendanceTime as $key => $time) {
            $in = new DateTime($time['in_time']);
            $out = new DateTime($time['out_time']);
            $lateDeadline = new DateTime('10:15:00');
            if ($in > $lateDeadline) {
                $appLate++;
            }
            $singleWorkTime = $out->diff($in);
            $singleEmployeeHours = $singleWorkTime->h + ($singleWorkTime->i / 60);
            $totalAppHours += $singleEmployeeHours;
        }

        $totalMachineHours = 0;
        foreach ($matchineAttendanceTime as $key => $time) {
            $in = new DateTime($time['in_time']);
            $out = new DateTime($time['out_time']);
            $singleWorkTime = $out->diff($in);
            $singleEmployeeHours = $singleWorkTime->h + ($singleWorkTime->i / 60);
            $totalMachineHours += $singleEmployeeHours;
        }
        $averageAppHours = $presentAppAtt > 0 ? round($totalAppHours / $presentAppAtt, 2) : 0;
        $averageMachineHours = $presentMachineAtt > 0 ? round($totalMachineHours / $presentMachineAtt, 2) : 0;

        $totalPresent = $presentMachineAtt + $presentAppAtt;
        $avgPresent = $active_employees > 0 ? ($totalPresent / $active_employees) * 100 : 0;

        $totalAbsent = $active_employees - ($totalPresent + $totalLeaveCount);
        $avgAbsent = $active_employees > 0 ? ($totalAbsent / $active_employees) * 100 : 0;

        $totalLate = $appLate + $lateMachineEmp;
        $avgLate = $totalPresent > 0 ? ($totalLate / $totalPresent) * 100 : 0;

        $avgWorkHours = $totalPresent > 0 ? ($totalAppHours + $totalMachineHours) / $totalPresent : 0;


        $result = [
            'active_employee' => $activeEmployee,
            'averageTenure' => $averageTenure,
            'averageAge' => $averageAge,
            'inactive_employee' => $inactiveEmployee,
            'total_present' => $totalPresent,
            'avg_present' => round($avgPresent, 2),
            'total_absent' => $totalAbsent,
            'avg_absent' => round($avgAbsent, 2),
            'total_late' => $totalLate,
            'avg_late' => round($avgLate, 2),
            'total_leave' => $totalLeaveCount,
            // 'avg_leave' => round($avgLeave, 2),
            'total_pending_leave' => $totalPendingLeaveCount,
            'department_total' => $departmentTotal,
            // 'avg_pending_leave' => round($avgPendingLeave, 2),
            'average_work_hours' => round($avgWorkHours, 2),
            'total_expense' => $totalExpense,
            'gulshan_office_total_employee' => $gulshanTotalOfficeEmployee,
            'warehouse_total_employee' => $warehouseTotalEmployee,
            'field_force_total_employee' => $fieldforceTotalEmployee
        ];


        return $result;
    }


    public function turnover()
    {
        $currentDate = now()->toDateString();
        $firstDayOfYear = Carbon::now()->startOfYear()->toDateString();



        $startEmployee = official::whereDate('DOJ', '<=', $firstDayOfYear)
            ->count('EID');
        $endEmployeeBeforStartYear = official::whereDate('DOL', '<=', $firstDayOfYear)
            ->count();
        $endEmployeeCurrentYear = official::whereDate('DOL', '>=', $firstDayOfYear)
            ->count();
        $newEmployee = official::whereDate('DOJ', '>=', $firstDayOfYear)
            ->count('EID');
        $beginingEmployee = 0;


        if ($startEmployee > $endEmployeeBeforStartYear) {
            $beginingEmployee = $startEmployee - $endEmployeeBeforStartYear;
        }
        $currentEmployee = ($beginingEmployee + $newEmployee) - $endEmployeeCurrentYear;
        $avgEmployees = ($beginingEmployee + $currentEmployee) / 2;
        $employeeTurnover = ($endEmployeeCurrentYear / $avgEmployees) * 100;
        $result = round($employeeTurnover, 3);


        return $result;
    }


    public function employeeExpense()
    {
        $startOfMonth = now()->startOfMonth()->toDateString();


        $endOfMonth = now()->endOfMonth()->toDateString();


        $result = emplooyee_expense::whereDate('Date', '>=', $startOfMonth)
            ->whereDate('Date', '<=', $endOfMonth)
            ->sum('Amount');
        return $result;
    }




    public function getAttendanceData(Request $request)
    {
        // $department = $request->query('department_id');
        $date = $request->query('date');

        $startOfMonth = now()->startOfMonth()->toDateString();

        $totalExpense = emplooyee_expense::whereDate('Date', '>=', $startOfMonth)
            ->whereDate('Date', '<=', $date)
            ->sum('Amount');


        $activeEmployee = official::where('Status', 'N')
            ->count();
        $inactiveEmployee = official::where('Status', 'Y')
            ->count();


        $presentMachineAtt = attendence::whereIn('Status', ['P', 'L'])
            ->whereDate('Date', $date)
            ->distinct('EID')
            ->count('EID');

        $averageTenure = official::where('officials.Status', 'N')
            ->whereNotNull('DOJ')
            ->selectRaw("AVG(TIMESTAMPDIFF(MONTH, DOJ, '$date') / 12) as avg_years")
            ->value('avg_years');

        $averageTenure = round($averageTenure, 2);

        $averageAge = DB::table('employees')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->where('officials.Status', 'N')
            ->whereNotNull('employees.DOB')
            ->selectRaw("AVG(TIMESTAMPDIFF(YEAR, employees.DOB, '$date')) as average_age")
            ->value('average_age');

        $averageAge = round($averageAge);

        $departmentTotal = department::count();


        $lateMachineEmp = attendence::where('Status', 'L')
            ->whereDate('Date', $date)
            ->distinct('EID')
            ->count('EID');


        $presentAppAtt = app_attendance::where('Status', 'In')
            ->whereDate('created_at', $date)
            ->count();

        $gulshanTotalOfficeEmployee = official::where('Job_Location_Id', 1)
            ->where('Status', 'N')
            ->count();


        $warehouseTotalEmployee = official::where('Job_Location_Id', 2)
            ->where('Status', 'N')
            ->count();

        $fieldforceTotalEmployee = official::where('Job_Location_Id', 3)
            ->where('Status', 'N')
            ->count();

        $active_employees = official::where('Status', 'N')->count();

        $totalLeaveCount = leave::where('status', 'Approved')
            ->whereDate('from_date', '>=', $date)
            ->whereDate('to_date', '<=', $date)
            ->groupBy('employee_id')
            ->count();

        $totalPendingLeaveCount = leave::whereIN('status', ['Pending', 'Approved (SV)'])
            ->whereDate('from_date', '>=', $date)
            ->whereDate('to_date', '<=', $date)
            ->groupBy('employee_id')
            ->count();


        // $inLeave = $totalLeaveCount + $totalPendingLeaveCount;




        $appattendanceTime = app_attendance::whereDate('created_at', $date)
            ->selectRaw('EID,TIME(MAX(CASE WHEN status = "in" THEN created_at END)) as in_time,TIME(MAX(CASE WHEN status = "out" THEN created_at END)) as out_time')
            ->groupBy('EID')
            ->get()
            ->toArray();




        $matchineAttendanceTime = attendence::whereDate('Date', $date)
            ->selectRaw('EID, MIN(Time_In) as in_time, MAX(Time_Out) as out_time')
            ->groupBy('EID')
            ->get()
            ->toArray();
        $totalAppHours = 0;
        $appLate = 0;




        foreach ($appattendanceTime as $key => $time) {
            $in = new DateTime($time['in_time']);
            $out = new DateTime($time['out_time']);
            $lateDeadline = new DateTime('10:15:00');
            if ($in > $lateDeadline) {
                $appLate++;
            }
            $singleWorkTime = $out->diff($in);
            $singleEmployeeHours = $singleWorkTime->h + ($singleWorkTime->i / 60);
            $totalAppHours += $singleEmployeeHours;
        }




        $totalMachineHours = 0;
        foreach ($matchineAttendanceTime as $key => $time) {
            $in = new DateTime($time['in_time']);
            $out = new DateTime($time['out_time']);
            $singleWorkTime = $out->diff($in);
            $singleEmployeeHours = $singleWorkTime->h + ($singleWorkTime->i / 60);
            $totalMachineHours += $singleEmployeeHours;
        }
        $averageAppHours = $presentAppAtt > 0 ? round($totalAppHours / $presentAppAtt, 2) : 0;
        $averageMachineHours = $presentMachineAtt > 0 ? round($totalMachineHours / $presentMachineAtt, 2) : 0;




        $totalPresent = $presentMachineAtt + $presentAppAtt;
        $avgPresent = $active_employees > 0 ? ($totalPresent / $active_employees) * 100 : 0;




        $totalAbsent = $active_employees - ($totalPresent + $totalLeaveCount);
        $avgAbsent = $active_employees > 0 ? ($totalAbsent / $active_employees) * 100 : 0;




        $totalLate = $appLate + $lateMachineEmp;
        $avgLate = $totalPresent > 0 ? ($totalLate / $totalPresent) * 100 : 0;


        $avgLeave = $active_employees > 0 ? ($totalLeaveCount / $active_employees) * 100 : 0;
        // $avgPendingLeave = $active_employees > 0 ? ($totalPendingLeaveCount / $active_employees) * 100 : 0;


        $avgWorkHours = $totalPresent > 0 ? ($totalAppHours + $totalMachineHours) / $totalPresent : 0;

        $empInfoDocumentation = EmpInfoDocumentation::select(
            'employee_id',
            'completed_percent'
        )->get();

        $totalEmployeeDocumentation = $empInfoDocumentation->pluck('employee_id')->unique()->count();

        $averagePercentageOfDocumentation = round(
            $empInfoDocumentation
                ->pluck('completed_percent')
                ->filter(fn($percentage) => is_numeric($percentage))
                ->avg() ?? 0,
            2
        );


        $result = [
            'active_employee' => $activeEmployee,
            'averageAge' => $averageAge,
            'averageTenure' => $averageTenure,
            'inactive_employee' => $inactiveEmployee,
            'total_present' => $totalPresent,
            'avg_present' => round($avgPresent, 2),
            'total_absent' => $totalAbsent,
            'avg_absent' => round($avgAbsent, 2),
            'total_late' => $totalLate,
            'avg_late' => round($avgLate, 2),
            'total_leave' => $totalLeaveCount,
            'avg_leave' => round($avgLeave, 2),
            'total_pending_leave' => $totalPendingLeaveCount,
            'department_total' => $departmentTotal,
            // 'avg_pending_leave' => round($avgPendingLeave, 2),
            'average_work_hours' => round($avgWorkHours, 2),
            'total_expense' => $totalExpense,
            'gulshan_office_total_employee' => $gulshanTotalOfficeEmployee,
            'warehouse_total_employee' => $warehouseTotalEmployee,
            'field_force_total_employee' => $fieldforceTotalEmployee,
            'totalEmployeeDocumentation' => $totalEmployeeDocumentation,
            'averagePercentageOfDocumentation' => $averagePercentageOfDocumentation
        ];


        return $result;
    }


    // public function getAttendanceTableData(Request $request)
    // {
    //     $currentYear = now()->year;
    //     $currentDate = now()->toDateString();


    //     $firstDate = Carbon::now()->startOfYear();


    //     $fridayCount = Carbon::now()->startOfYear()->diffInWeeks(Carbon::now()->addDay()) + 1;


    //     $holidays = holiday::where('from_date', '>=', $firstDate)
    //         ->where('to_date', '<=', $currentDate)
    //         ->sum('duration');


    //     $totalAppAttendance = app_attendance::whereDate('created_at', '>=', $firstDate)
    //         ->where('Status', 'in')
    //         ->groupBy('EID')
    //         ->count('EID');




    //     $basic_info = official::where('Status', 'N')
    //         ->join('departments', 'officials.Department_Id', '=', 'departments.id')
    //         ->join('employees', 'officials.EID', '=', 'employees.id')
    //         ->select(
    //             'employees.id as employee_id',
    //             'employees.Employee_Id as employee_code',
    //             'employees.Full_Name as emp_name',
    //             'departments.Name as department_name',
    //             'officials.DOJ as date_of_joining'
    //         )
    //         ->get();


    //     $app_attendance = app_attendance::where('Status', 'In')
    //         ->whereYear('created_at', $currentYear)
    //         ->selectRaw('EID, COUNT(*) as present_count')
    //         ->groupBy('EID')
    //         ->pluck('present_count', 'EID');
    //     return $basic_info;
    // }




}
