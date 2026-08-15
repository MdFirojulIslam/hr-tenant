<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Single source of truth for expense edit permission.
 *
 * Business rule: an expense belonging to month M is editable until
 * the end of day 3 of month M+1 (server timezone / now() only).
 *
 * Example: July 2026 expenses → editable through 2026-08-03 inclusive;
 * locked from 2026-08-04 onwards.
 */
class ExpenseEditPolicy
{
    /**
     * Whether an expense may be edited at [serverNow].
     *
     * @param  CarbonInterface|string  $expenseDate  Expense Date (defines expense month)
     * @param  int|string|null  $status  0 = pending/submitted; non-zero = locked by approval
     * @param  CarbonInterface|null  $serverNow  Injected for tests; defaults to Laravel now()
     */
    public static function canEdit(
        CarbonInterface|string $expenseDate,
        int|string|null $status = 0,
        ?CarbonInterface $serverNow = null
    ): bool {
        if ((int) ($status ?? 0) !== 0) {
            return false;
        }

        $now = $serverNow
            ? Carbon::instance($serverNow)->copy()
            : now();

        $deadline = self::editDeadline($expenseDate);

        return $now->lte($deadline);
    }

    /**
     * Inclusive edit deadline: end of day 3 of the month after the expense month.
     */
    public static function editDeadline(CarbonInterface|string $expenseDate): Carbon
    {
        $date = $expenseDate instanceof CarbonInterface
            ? Carbon::instance($expenseDate)->copy()
            : Carbon::parse($expenseDate);

        return $date
            ->startOfMonth()
            ->addMonth()
            ->day(3)
            ->endOfDay();
    }
}
