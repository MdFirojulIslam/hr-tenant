<?php

namespace App\Http\Controllers;

use App\Models\department;
use App\Models\leave;
use App\Models\official;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
}
