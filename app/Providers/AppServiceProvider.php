<?php

namespace App\Providers;

use App\Support\MobileApiRateLimiter;
use App\Support\MobileTokenIdlePolicy;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Run after RouteServiceProvider so the mobile `api` limiter is not overwritten.
        $this->app->booted(function () {
            MobileApiRateLimiter::register();
        });

        // Rolling 3-day idle for ArtisanHRIS-Mobile tokens (last_used_at based).
        // Runs inside Sanctum before last_used_at is refreshed on this request.
        Sanctum::authenticateAccessTokensUsing(function ($accessToken, bool $isValid) {
            if (!$isValid) {
                return false;
            }

            if (!MobileTokenIdlePolicy::isWithinIdleWindow($accessToken)) {
                // Drop idle mobile tokens so they cannot be reused.
                if (($accessToken->name ?? null) === MobileTokenIdlePolicy::TOKEN_NAME) {
                    $accessToken->delete();
                }

                return false;
            }

            return true;
        });
    }
}
