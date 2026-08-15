<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Rolling idle session for ArtisanHRIS-Mobile Sanctum tokens.
 *
 * Policy: last_used_at (or created_at if never used) + idle days must be >= now.
 * Uses Sanctum authenticateAccessTokensUsing so the check runs BEFORE last_used_at
 * is refreshed on the current request.
 */
class MobileTokenIdlePolicy
{
    public const TOKEN_NAME = 'ArtisanHRIS-Mobile';

    /**
     * @param  mixed  $accessToken  Sanctum personal access token model
     */
    public static function isWithinIdleWindow(
        $accessToken,
        ?CarbonInterface $now = null,
        ?int $idleDays = null
    ): bool {
        if (!$accessToken || !is_object($accessToken)) {
            return false;
        }

        $name = (string) ($accessToken->name ?? '');
        if ($name !== self::TOKEN_NAME) {
            return true;
        }

        $days = $idleDays ?? (int) config('mobile.session_idle_days', 3);
        if ($days < 1) {
            $days = 3;
        }

        $now = $now ?? now();
        $lastActive = $accessToken->last_used_at ?? $accessToken->created_at;
        if (!$lastActive) {
            return true;
        }

        return $lastActive->gt($now->copy()->subDays($days));
    }
}
