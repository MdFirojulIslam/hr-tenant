<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Market Intelligence entries are editable only on the submission date
 * (Asia/Dhaka calendar day of created_at). The next day they are locked.
 */
class MiEditPolicy
{
    public const TIMEZONE = AppTimezone::NAME;

    public static function canEdit(mixed $createdAt, ?CarbonInterface $serverNow = null): bool
    {
        $submitted = self::submissionDate($createdAt);
        if ($submitted === null) {
            return false;
        }

        $now = $serverNow
            ? Carbon::instance($serverNow)->timezone(self::TIMEZONE)
            : Carbon::now(self::TIMEZONE);

        return $now->toDateString() === $submitted;
    }

    public static function submissionDate(mixed $createdAt): ?string
    {
        if ($createdAt === null || $createdAt === '') {
            return null;
        }

        $carbon = $createdAt instanceof CarbonInterface
            ? Carbon::instance($createdAt)
            : Carbon::parse($createdAt);

        return $carbon->timezone(self::TIMEZONE)->toDateString();
    }

    public static function lockMessage(): string
    {
        return 'Editing is unavailable after the submission date.';
    }
}
