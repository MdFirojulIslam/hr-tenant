<?php

namespace App\Support;

use App\Models\OutletVisitedVisit;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * A scheduled outlet visit occurrence is (assignment_id + scheduled_date).
 *
 * Completing one date must never mark another date of the same assignment.
 */
class OutletVisitOccurrence
{
    public static function dateOf(?OutletVisitedVisit $visit): ?string
    {
        if ($visit === null) {
            return null;
        }

        $scheduled = $visit->scheduled_date ?? null;
        if ($scheduled !== null && $scheduled !== '') {
            if ($scheduled instanceof CarbonInterface) {
                return $scheduled->toDateString();
            }

            return Carbon::parse($scheduled, AppTimezone::NAME)->toDateString();
        }

        $at = $visit->check_in_at ?? $visit->visited_at;
        if ($at === null || $at === '') {
            return null;
        }

        $carbon = $at instanceof CarbonInterface
            ? Carbon::instance($at)
            : Carbon::parse($at);

        return $carbon->timezone(AppTimezone::NAME)->toDateString();
    }

    /**
     * @param  Collection<int, OutletVisitedVisit>|iterable<OutletVisitedVisit>|null  $visits
     */
    public static function forDate($visits, string $date): ?OutletVisitedVisit
    {
        if ($visits === null) {
            return null;
        }

        foreach ($visits as $visit) {
            if (! $visit instanceof OutletVisitedVisit) {
                continue;
            }
            if (self::dateOf($visit) === $date) {
                return $visit;
            }
        }

        return null;
    }
}
