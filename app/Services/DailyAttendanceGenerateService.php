<?php


namespace App\Services;

use App\Models\app_attendance;
use App\Models\attendence;
use App\Models\AttendanceSummary;
use Carbon\Carbon;


class DailyAttendanceGenerateService
{
  public function DailyAttendanceUpdate()
  {

    $today = now()->toDateString();
    $currentAttendanceDate = $today;

    $fiveDatePeriod = [];

    $dayCount = 5;

    $lastAttendanceFetchDate = null;

    while ($dayCount--) {
      $fiveDatePeriod[] = $currentAttendanceDate;
      $currentAttendanceDate = Carbon::parse($currentAttendanceDate)->subDay()->toDateString();
      if ($dayCount == 1) {
        $lastAttendanceFetchDate = $currentAttendanceDate;
      }
    }

    $attendanceData = [];

    foreach ($fiveDatePeriod as $dateInfo) {
      $appAttendanceInfo = app_attendance::whereDate('created_at', $dateInfo)
        ->whereIn('status', ['in', 'out'])
        ->selectRaw('EID as employee_id, DATE(created_at) as date, TIME(MIN(CASE WHEN status="in" THEN created_at END)) as in_time, TIME(MAX(CASE WHEN status="out" THEN created_at END)) as out_time')
        ->groupByRaw('EID, DATE(created_at)')
        ->get();
      $machineAttendanceInfo = attendence::whereDate('Date', $dateInfo)
        ->whereIn('Status', ['P', 'L'])
        ->selectRaw('EID as employee_id, DATE as date, MIN(Time_In) as in_time, MAX(Time_Out) as out_time')
        ->groupByRaw('EID, DATE')
        ->get();
      $mergedAttendance = $appAttendanceInfo->concat($machineAttendanceInfo);
      foreach ($mergedAttendance as $record) {
        $attendanceData[$dateInfo][] = [
          'employee_id' => $record->employee_id,
          'in_time' => $record->in_time,
          'out_time' => $record->out_time,
        ];
      }
    }

    $fetchAttendanceSummary = AttendanceSummary::whereBetween('date', [$lastAttendanceFetchDate, $today])
      ->where('employee_id', '=', 254)
      ->get()
      ->keyBy(function ($item) {
        return $item->employee_id . '_' . $item->date;
      });

    foreach ($attendanceData as $date => $records) {
      foreach ($records as $record) {
        $key = $record['employee_id'] . '_' . $date;
        $existing = $fetchAttendanceSummary[$key] ?? null;
        if ($existing && is_null($existing->in_time)) {
          $status = 'Present';
          $inTime = Carbon::parse($record['in_time']);
          $outTime = Carbon::parse($record['out_time']);


          $rosterIn = $existing->rostered_in_time
            ? Carbon::parse($existing->rostered_in_time)
            : null;
          $rosterOut = $existing->rostered_out_time
            ? Carbon::parse($existing->rostered_out_time)
            : null;

          if ($rosterIn && $inTime->diffInMinutes($rosterIn, false) < -15) {
            $status = 'Late';
          }
          if ($rosterOut && $outTime->diffInMinutes($rosterOut, false) > 15) {
            $status = 'Early Leave';
          }

          $existing->update([
            'in_time'  => $record['in_time'],
            'out_time' => $record['out_time'],
            'status'   => $status
          ]);
        }
      }
    }
  }
}
