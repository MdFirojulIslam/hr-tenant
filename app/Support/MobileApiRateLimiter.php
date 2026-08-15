<?php

namespace App\Support;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Raises the shared `api` limiter for Sanctum mobile routes so a normal
 * app session does not hit Laravel's default 60/min bucket.
 *
 * Merge register() into AppServiceProvider::boot() on the live server.
 */
class MobileApiRateLimiter
{
    public static function register(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $by = (string) ($request->user()?->id ?: $request->ip());
            $mobileLimit = (int) config('mobile.api_rate_limit_per_minute', 180);
            if ($mobileLimit < 60) {
                $mobileLimit = 60;
            }

            if ($request->is('api/mobile/*')) {
                return Limit::perMinute($mobileLimit)->by('mobile:'.$by);
            }

            return Limit::perMinute(60)->by($by);
        });
    }
}
