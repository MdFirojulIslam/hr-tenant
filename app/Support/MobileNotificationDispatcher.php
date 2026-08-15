<?php

namespace App\Support;

use App\Models\app_attendance;
use App\Models\AttendanceSummary;
use App\Models\emplooyee_expense;
use App\Models\EventSetup;
use App\Models\holiday;
use App\Models\MobileNotification;
use App\Models\OutletVisitAssignment;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

class MobileNotificationDispatcher
{
    public static function notifyLeaveApproved(int $eid, int $leaveId): void
    {
        if ($eid <= 0 || $leaveId <= 0) {
            return;
        }

        MobileNotificationService::notifyOnce(
            $eid,
            MobileNotification::TYPE_LEAVE,
            'leave-'.$leaveId,
            'Leave Approved',
            'Your leave request has been approved.',
            'leave',
            'leave',
            $leaveId
        );
    }

    public static function notifyEventCreated(EventSetup $event): void
    {
        self::notifyEligibleEmployeesForEvent($event);
    }

    public function dispatchCheckInReminders(?CarbonInterface $now = null): int
    {
        $now = $now ?? AppTimezone::now();
        if (!self::isWorkingDay($now)) {
            return 0;
        }

        $date = $now->toDateString();
        $created = 0;
        foreach (self::activeEmployeeIds() as $eid) {
            if (!MobileNotificationService::hasPage($eid, (int) config('mobile.permissions.attendance', 32))) {
                continue;
            }
            if (self::hasCheckedIn($eid, $date)) {
                continue;
            }
            $row = MobileNotificationService::notifyOnce(
                $eid,
                MobileNotification::TYPE_CHECK_IN,
                $date,
                'Check-in Reminder',
                "You haven't checked in today. Please remember to submit your attendance.",
                'attendance'
            );
            if ($row) {
                $created++;
            }
        }

        return $created;
    }

    public function dispatchCheckOutReminders(?CarbonInterface $now = null): int
    {
        $now = $now ?? AppTimezone::now();
        if (!self::isWorkingDay($now)) {
            return 0;
        }

        $date = $now->toDateString();
        $created = 0;
        foreach (self::activeEmployeeIds() as $eid) {
            if (!MobileNotificationService::hasPage($eid, (int) config('mobile.permissions.attendance', 32))) {
                continue;
            }
            if (!self::hasCheckedIn($eid, $date) || self::hasCheckedOut($eid, $date)) {
                continue;
            }
            $row = MobileNotificationService::notifyOnce(
                $eid,
                MobileNotification::TYPE_CHECK_OUT,
                $date,
                'Check-out Reminder',
                'Please remember to check out before ending your workday.',
                'attendance'
            );
            if ($row) {
                $created++;
            }
        }

        return $created;
    }

    public function dispatchExpenseReminders(?CarbonInterface $now = null): int
    {
        $now = $now ?? AppTimezone::now();
        if (!self::isWorkingDay($now)) {
            return 0;
        }

        $date = $now->toDateString();
        $created = 0;
        foreach (self::activeEmployeeIds() as $eid) {
            if (!MobileNotificationService::hasPage($eid, (int) config('mobile.permissions.expense', 28))) {
                continue;
            }
            if (self::hasExpenseForDate($eid, $date)) {
                continue;
            }
            $row = MobileNotificationService::notifyOnce(
                $eid,
                MobileNotification::TYPE_EXPENSE,
                $date,
                'Expense Submission Reminder',
                "Today's expense has not been submitted yet. Please submit it before the day ends.",
                'expense'
            );
            if ($row) {
                $created++;
            }
        }

        return $created;
    }

    public function dispatchOutletPending(?CarbonInterface $now = null): int
    {
        $now = $now ?? AppTimezone::now();
        if (!OutletVisitWeekCycle::isConfigured()) {
            return 0;
        }

        $weekNo = OutletVisitWeekCycle::weekNo($now);
        $weekday = OutletVisitWeekCycle::weekdayName($now);
        if (!$weekNo) {
            return 0;
        }

        $date = $now->toDateString();
        $hour = (int) $now->format('G');
        $created = 0;

        foreach (self::activeEmployeeIds() as $eid) {
            if (!MobileNotificationService::hasPage($eid, (int) config('mobile.permissions.outlet_visited', 70))) {
                continue;
            }

            $pending = self::pendingOutletCount($eid, $weekNo, $weekday);
            if ($pending < 1) {
                continue;
            }

            $row = MobileNotificationService::notifyOnce(
                $eid,
                MobileNotification::TYPE_OUTLET,
                $date.'-'.$hour,
                'Scheduled Outlet Visit',
                $pending === 1
                    ? 'You have 1 pending scheduled outlet visit for today.'
                    : 'You have '.$pending.' pending scheduled outlet visits for today.',
                'outlet_visited'
            );
            if ($row) {
                $created++;
            }
        }

        return $created;
    }

    public function dispatchActiveEvents(?CarbonInterface $now = null): int
    {
        $now = $now ?? AppTimezone::now();
        if (!class_exists(EventSetup::class) || !Schema::hasTable('event_setup')) {
            return 0;
        }

        $created = 0;
        $events = EventSetup::query()
            ->where('event_date_from', '<=', $now)
            ->where('event_date_to', '>=', $now)
            ->get();

        foreach ($events as $event) {
            $created += self::notifyEligibleEmployeesForEvent($event);
        }

        return $created;
    }

    private static function notifyEligibleEmployeesForEvent(EventSetup $event): int
    {
        $now = AppTimezone::now();
        if (!$event->isActiveAt($now) && !($event->event_date_from && $event->event_date_from->lte($now) && $event->event_date_to && $event->event_date_to->gte($now))) {
            if (!$event->event_date_from || $event->event_date_from->gt($now)) {
                return 0;
            }
            if ($event->event_date_to && $event->event_date_to->lt($now)) {
                return 0;
            }
        }

        $created = 0;
        $title = 'New Event: '.trim((string) $event->event_header);
        foreach (self::activeEmployeeIds() as $eid) {
            $dept = EmployeeHierarchy::departmentId($eid);
            if (!$event->matchesDepartment($dept)) {
                continue;
            }
            $row = MobileNotificationService::notifyOnce(
                $eid,
                MobileNotification::TYPE_EVENT,
                'event-'.$event->id,
                $title !== 'New Event:' ? $title : 'New Event',
                'A new event is available.',
                'events',
                'event',
                (int) $event->id
            );
            if ($row) {
                $created++;
            }
        }

        return $created;
    }

    public static function isWorkingDay(CarbonInterface $when): bool
    {
        $date = $when->copy()->timezone(AppTimezone::NAME);
        if ($date->isFriday()) {
            return false;
        }

        if (!class_exists(holiday::class) || !Schema::hasTable('holidays')) {
            return true;
        }

        $day = $date->toDateString();

        return !holiday::query()
            ->whereDate('from_date', '<=', $day)
            ->whereDate('to_date', '>=', $day)
            ->exists();
    }

    /**
     * @return list<int>
     */
    public static function activeEmployeeIds(): array
    {
        $query = User::query()->whereNotNull('EID')->where('EID', '>', 0);
        if (Schema::hasColumn('users', 'active')) {
            $query->where('active', 1);
        }

        return $query->pluck('EID')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public static function hasCheckedIn(int $eid, string $date): bool
    {
        if (class_exists(AttendanceSummary::class) && Schema::hasTable('attendance_summaries')) {
            $summary = AttendanceSummary::query()
                ->where('employee_id', $eid)
                ->whereDate('date', $date)
                ->first();
            if ($summary && !empty($summary->in_time)) {
                return true;
            }
        }

        if (!class_exists(app_attendance::class) || !Schema::hasTable('app_attendances')) {
            return false;
        }

        return app_attendance::where('EID', $eid)
            ->whereDate('created_at', $date)
            ->where('status', 'in')
            ->exists();
    }

    public static function hasCheckedOut(int $eid, string $date): bool
    {
        if (class_exists(AttendanceSummary::class) && Schema::hasTable('attendance_summaries')) {
            $summary = AttendanceSummary::query()
                ->where('employee_id', $eid)
                ->whereDate('date', $date)
                ->first();
            if ($summary && !empty($summary->out_time)) {
                return true;
            }
        }

        if (!class_exists(app_attendance::class) || !Schema::hasTable('app_attendances')) {
            return false;
        }

        return app_attendance::where('EID', $eid)
            ->whereDate('created_at', $date)
            ->where('status', 'out')
            ->exists();
    }

    public static function hasExpenseForDate(int $eid, string $date): bool
    {
        if (!class_exists(emplooyee_expense::class)) {
            return false;
        }

        return emplooyee_expense::query()
            ->where('EID', $eid)
            ->whereDate('Date', $date)
            ->exists();
    }

    public static function pendingOutletCount(int $eid, int $weekNo, string $weekday): int
    {
        if (!class_exists(OutletVisitAssignment::class)) {
            return 0;
        }

        $today = AppTimezone::now()->toDateString();

        return OutletVisitAssignment::query()
            ->where('employee_id', $eid)
            ->where('is_active', true)
            ->whereHas('weekSchedule', function ($q) use ($weekNo, $weekday) {
                $q->where('week_no', $weekNo)
                    ->whereRaw('LOWER(day_name) = ?', [strtolower($weekday)]);
            })
            ->where(function ($q) use ($today) {
                $q->whereDoesntHave('visits', function ($v) use ($today) {
                    $v->where(function ($inner) use ($today) {
                        $inner->whereDate('scheduled_date', $today)
                            ->orWhereDate('check_in_at', $today)
                            ->orWhereDate('visited_at', $today);
                    });
                })->orWhereHas('visits', function ($v) use ($today) {
                    $v->where(function ($inner) use ($today) {
                        $inner->whereDate('scheduled_date', $today)
                            ->orWhereDate('check_in_at', $today);
                    })->whereNull('check_out_at')
                        ->whereNotNull('check_in_at');
                });
            })
            ->count();
    }
}
