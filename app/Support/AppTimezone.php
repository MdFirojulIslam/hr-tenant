<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Application wall-clock timezone is Asia/Dhaka.
 *
 * Datetime columns store naive local timestamps. Serializing with
 * Carbon::toIso8601String() uses APP_TIMEZONE; Dart then treats the offset
 * as UTC and UI clocks appear ~6 hours behind. Always label the stored
 * wall-clock as Asia/Dhaka without converting the components.
 */
class AppTimezone
{
    public const NAME = 'Asia/Dhaka';

    public static function now(): CarbonInterface
    {
        return now(self::NAME);
    }

    public static function toIso8601(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $carbon = $value instanceof CarbonInterface
            ? $value->copy()
            : Carbon::parse($value);

        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $carbon->format('Y-m-d H:i:s'),
            self::NAME
        )->toIso8601String();
    }
}
