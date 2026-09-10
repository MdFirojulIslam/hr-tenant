<?php


use App\Http\Controllers\DashboardController;
use App\Models\app_attendance;
use App\Models\attendence;
use App\Models\leave;
use App\Models\minutes_of_meeting;
use App\Models\official;
use App\Models\OutletVisitAssignment;
use App\Models\OutletVisitedVisit;
use App\Models\RandomOutletVisit;
use App\Support\OutletVisitWeekCycle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/dashboard-department-list', [DashboardController::class, 'departmentList']);
Route::get('/dashboard-attendance-data', [DashboardController::class, 'getAttendanceData']);
Route::get('/dashboard-attendance-data-by-dept', [DashboardController::class, 'departmentFilter']);
Route::get('/dashboard-attendance-table-data', [DashboardController::class, 'getAttendanceTableData']);


Route::get('/daily-standup', function (Request $request) {
    $date = $request->query('date');

    $today = Carbon::now()->toDateString();
    $yesterday = Carbon::now()->subDay()->toDateString();
    $tomorrow = Carbon::now()->addDay()->toDateString();

    if ($date) {
        $date = Carbon::parse($date)->toDateString();
        $yesterday = Carbon::parse($date)->subDay()->toDateString();
        $today = Carbon::parse($date)->toDateString();
        $tomorrow = Carbon::parse($date)->addDay()->toDateString();
    }

    $activeEmployees = official::where('Status', 'N')->count();

    $buildStandup = function ($targetDate) use ($activeEmployees) {
        $presentMachine = attendence::whereIn('Status', ['P', 'L'])
            ->whereDate('Date', $targetDate)
            ->distinct('EID')
            ->count('EID');

        $presentApp = app_attendance::where('Status', 'In')
            ->whereDate('created_at', $targetDate)
            ->distinct('EID')
            ->count('EID');

        $totalPresent = $presentMachine + $presentApp;
        $presentPct = $activeEmployees > 0 ? round(($totalPresent / $activeEmployees) * 100, 1) : '';

        $lateMachine = attendence::where('Status', 'L')
            ->whereDate('Date', $targetDate)
            ->distinct('EID')
            ->count('EID');

        $appLate = 0;
        $appAttendanceTime = app_attendance::whereDate('created_at', $targetDate)
            ->where('Status', 'In')
            ->selectRaw('EID, MIN(created_at) as in_time')
            ->groupBy('EID')
            ->get();

        $lateDeadline = new DateTime('10:15:00');
        foreach ($appAttendanceTime as $att) {
            if ($att->in_time) {
                $in = new DateTime(date('H:i:s', strtotime($att->in_time)));
                if ($in > $lateDeadline) {
                    $appLate++;
                }
            }
        }

        $totalLate = $lateMachine + $appLate;
        $latePct = $totalPresent > 0 ? round(($totalLate / $totalPresent) * 100, 1) : '';

        $totalLeave = leave::where('Status', 'Approved')
            ->whereDate('from_date', '<=', $targetDate)
            ->whereDate('to_date', '>=', $targetDate)
            ->distinct('employee_id')
            ->count();
        $leavePct = $activeEmployees > 0 ? round(($totalLeave / $activeEmployees) * 100, 1) : '';

        $cycleWeek = OutletVisitWeekCycle::weekNo(Carbon::parse($targetDate, OutletVisitWeekCycle::TIMEZONE));
        $cycleWeekday = OutletVisitWeekCycle::weekdayName(Carbon::parse($targetDate, OutletVisitWeekCycle::TIMEZONE));

        $scheduledOutlets = OutletVisitAssignment::where('is_active', true)
            ->whereHas('weekSchedule', function ($query) use ($cycleWeek, $cycleWeekday) {
                $query->where('week_no', $cycleWeek)
                    ->where('day_name', $cycleWeekday);
            })
            ->count();

        $visitedOutlets = OutletVisitedVisit::whereDate('scheduled_date', $targetDate)
            ->whereNotNull('check_in_at')
            ->distinct('assignment_id')
            ->count('assignment_id');

        $outletPct = $scheduledOutlets > 0 ? round(($visitedOutlets / $scheduledOutlets) * 100, 1) : '';

        $randomVisits = RandomOutletVisit::whereDate('created_at', $targetDate)
            ->count();
        $randomTowns = RandomOutletVisit::whereDate('created_at', $targetDate)
            ->distinct('town')
            ->count('town');

        $meetings = minutes_of_meeting::whereDate('date', $targetDate)
            ->count();
        $meetingPersons = minutes_of_meeting::whereDate('date', $targetDate)
            ->distinct('eid')
            ->count('eid');

        return [
            'present' => [
                'count' => $totalPresent,
                'percentage' => $presentPct,
            ],
            'late' => [
                'count' => $totalLate,
                'percentage' => $latePct,
            ],
            'on_leave' => [
                'count' => $totalLeave,
                'percentage' => $leavePct,
            ],
            'schedule_outlet_visited' => [
                'visited' => $visitedOutlets,
                'scheduled' => $scheduledOutlets,
                'percentage' => $outletPct,
            ],
            'random_outlet_visit' => [
                'count' => $randomVisits,
                'towns' => $randomTowns,
            ],
            'meeting_minutes' => [
                'meetings' => $meetings,
                'persons' => $meetingPersons,
            ],
        ];
    };

    return response()->json([
        'active_employees' => $activeEmployees,
        'dates' => [
            'yesterday' => $yesterday,
            'today' => $today,
            'tomorrow' => $tomorrow,
        ],
        'yesterday' => $buildStandup($yesterday),
        'today' => $buildStandup($today),
        'tomorrow' => $buildStandup($tomorrow),
    ]);
});
