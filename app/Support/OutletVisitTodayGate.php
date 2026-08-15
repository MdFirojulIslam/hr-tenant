<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Server-side check-in/out authorization for a scheduled occurrence.
 *
 * Check-in always uses Asia/Dhaka "today". A crafted request for tomorrow
 * or another assignment date is rejected even if the Flutter UI is bypassed.
 */
class OutletVisitTodayGate
{
    public const DENY_MESSAGE = 'You can only visit outlets assigned for today.';

    /**
     * @return string|null  Deny message, or null when the occurrence is actionable today.
     */
    public static function denyReason(
        bool $isActive,
        int $assignmentWeekNo,
        string $assignmentDayName,
        string $requestedDate,
        ?CarbonInterface $now = null
    ): ?string {
        if (! $isActive) {
            return 'Assignment is inactive.';
        }

        if (! OutletVisitWeekCycle::isConfigured()) {
            return 'Scheduled Outlet Visit week rotation is unavailable.';
        }

        $now = $now ?? OutletVisitWeekCycle::today();
        $today = $now->copy()->timezone(OutletVisitWeekCycle::TIMEZONE)->toDateString();

        if ($requestedDate !== $today) {
            return self::DENY_MESSAGE;
        }

        $activeWeek = OutletVisitWeekCycle::weekNo($now);
        $weekday = strtolower(OutletVisitWeekCycle::weekdayName($now));
        $dayName = strtolower(trim($assignmentDayName));

        if ($assignmentWeekNo !== $activeWeek || $dayName !== $weekday) {
            return self::DENY_MESSAGE;
        }

        return null;
    }
}
