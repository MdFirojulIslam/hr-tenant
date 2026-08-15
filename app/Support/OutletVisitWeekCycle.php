<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Scheduled Outlet Visit 4-week rotation.
 *
 * Single business-rule anchor: 01/08/2026 = Week 1.
 * Active week = floor(elapsed whole days from that date / 7) mod 4, then + 1.
 * Calendar month and PHP/ISO week-of-year numbers are never used.
 */
class OutletVisitWeekCycle
{
    public const TIMEZONE = 'Asia/Dhaka';

    /** 01/08/2026 — first day of Week 1. Do not duplicate this date elsewhere. */
    public const ANCHOR_DATE = '2026-08-01';

    public static function anchor(): CarbonInterface
    {
        $raw = self::ANCHOR_DATE;
        try {
            if (function_exists('app') && app()->bound('config')) {
                $configured = config('mobile.outlet_visit_week1_anchor');
                if (is_string($configured) && trim($configured) !== '') {
                    $raw = trim($configured);
                }
            }
        } catch (\Throwable $e) {
            $raw = self::ANCHOR_DATE;
        }

        return Carbon::parse($raw, self::TIMEZONE)->startOfDay();
    }

    public static function isConfigured(): bool
    {
        return true;
    }

    /**
     * Active rotation week (1–4) for $when.
     */
    public static function weekNo(?CarbonInterface $when = null): int
    {
        $anchor = self::anchor();
        $date = ($when ?? Carbon::now(self::TIMEZONE))->copy()->timezone(self::TIMEZONE)->startOfDay();
        $days = (int) $anchor->diffInDays($date, false);
        $weekIndex = intdiv($days, 7);

        return (($weekIndex % 4) + 4) % 4 + 1;
    }

    public static function weekdayName(?CarbonInterface $when = null): string
    {
        $date = ($when ?? Carbon::now(self::TIMEZONE))->copy()->timezone(self::TIMEZONE);

        return strtolower($date->format('l'));
    }

    public static function today(): CarbonInterface
    {
        return Carbon::now(self::TIMEZONE)->startOfDay();
    }
}
