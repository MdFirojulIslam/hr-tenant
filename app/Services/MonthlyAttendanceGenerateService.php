<?php


namespace App\Services;

use App\Models\employee;
use App\Models\holiday;
use App\Models\roster_schedule;
use Carbon\Carbon;
use App\Models\AttendanceSummary;


class MonthlyAttendanceGenerateService
{
  public function monthlyAttendanceCommonInformation()
  {
    $startOfMonth = now()->startOfMonth()->toDateString();
    $endOfMonth = now()->endOfMonth()->toDateString();

    // $startOfMonth = now()->subMonth()->startOfMonth()->toDateString();
    // $endOfMonth = now()->subMonth()->endOfMonth()->toDateString();

    $employeesInfo = employee::where('officials.Status', 'N')
      ->join('officials', 'officials.EID', '=', 'employees.id')
      ->select('employees.id as employee_id', 'officials.Department_Id as department_id')
      ->get()
      ->toArray();

    $currentDate = $startOfMonth;
    $monthDates = [];
    while ($currentDate <= $endOfMonth) {
      $monthDates[] = [
        'date' => $currentDate,
        'day_name' => Carbon::parse($currentDate)->format('l')
      ];
      $currentDate = Carbon::parse($currentDate)->addDay()->toDateString();
    }

    $holidays = holiday::select('from_date', 'to_date')
      ->where('from_date', '<=', $endOfMonth)
      ->where('to_date', '>=', $startOfMonth)
      ->get();

    $holidayDates = [];

    foreach ($holidays as $holyday) {
      $start = \Carbon\Carbon::parse($holyday->from_date)->max($startOfMonth);
      $end   = \Carbon\Carbon::parse($holyday->to_date)->min($endOfMonth);
      while ($start->lte($end)) {
        $date = $start->format('Y-m-d');
        $holidayDates[$date] = 'Holiday';
        $start->addDay();
      }
    }

    $emploeeMonthDates = [];
    foreach ($employeesInfo as $employee) {
      $employeeData = [
        'employee_id' => $employee['employee_id'],
        'department_id' => $employee['department_id'],
        'dateInfo' => []
      ];
      foreach ($monthDates as $datesInfo) {
        $employeeData['dateInfo'][] = [
          'date' => $datesInfo['date'],
          'day_name' => $datesInfo['day_name'],
          'holiday_status'  => isset($holidayDates[$datesInfo['date']])
            ? 'Holiday'
            : null,
        ];
      }
      $emploeeMonthDates[] = $employeeData;
    }

    $rosterData = [];
    $rosterScheduler = roster_schedule::whereDate('effective_from', '<=', $endOfMonth)
      ->whereDate('effective_to', '>=', $startOfMonth)
      ->get();

    foreach ($rosterScheduler as $roster) {
      $periodStart = Carbon::parse($roster->effective_from)->max($startOfMonth);
      $periodEnd   = Carbon::parse($roster->effective_to)->min($endOfMonth);

      $current = $periodStart->copy();
      while ($current->lte($periodEnd)) {
        if ($roster->day_name == $current->format('l')) {
          $rosterData[$roster->employee_id][$current->toDateString()] = [
            // 'roster_date' => $current->toDateString(),
            'time_in' => $roster->time_in,
            'time_out' => $roster->time_out,
            'roster_day_name' => $roster->day_name,
            'work_from' => $roster->work_from
          ];
        }
        $current->addDay();
      }
    }

    foreach ($emploeeMonthDates as $empKey => $employee) {
      $empId = $employee['employee_id'];
      foreach ($employee['dateInfo'] as $dateKey => $dateInfo) {
        $date = $dateInfo['date'];
        $roster = $rosterData[$empId][$date] ?? null;
        if ($roster) {
          $emploeeMonthDates[$empKey]['dateInfo'][$dateKey]['roster_in_time']  = $roster['time_in'];
          $emploeeMonthDates[$empKey]['dateInfo'][$dateKey]['roster_out_time'] = $roster['time_out'];
          $emploeeMonthDates[$empKey]['dateInfo'][$dateKey]['roster_status']   = $roster['work_from'];
        } else {
          $emploeeMonthDates[$empKey]['dateInfo'][$dateKey]['roster_in_time']  = null;
          $emploeeMonthDates[$empKey]['dateInfo'][$dateKey]['roster_out_time'] = null;
          $emploeeMonthDates[$empKey]['dateInfo'][$dateKey]['roster_status']   = null;
        }
      }
    }


    $insertData = [];

    foreach ($emploeeMonthDates as $employee) {
      $employeeId = $employee['employee_id'];
      $departmentId = $employee['department_id'];
      foreach ($employee['dateInfo'] as $dateInfo) {
        $status = $dateInfo['holiday_status'] ?? ($dateInfo['day_name'] == 'Friday' ? 'Friday' : null);
        $insertData[] = [
          'employee_id' => $employeeId,
          'department_id' => $departmentId,
          'date' => $dateInfo['date'],
          'status' => $status,
          'rostered_in_time' => $dateInfo['roster_in_time'] ?? null,
          'rostered_out_time' => $dateInfo['roster_out_time'] ?? null,
          'rostered_status' => $dateInfo['roster_status'] ?? null,
        ];
      }
    }

    $attendanceSummaries = AttendanceSummary::whereBetween('date', [$startOfMonth, $endOfMonth])->get();

    if ($attendanceSummaries->isEmpty()) {
      foreach (array_chunk($insertData, 500) as $chunk) {
        AttendanceSummary::insert($chunk);
      }
    }
  }
}
