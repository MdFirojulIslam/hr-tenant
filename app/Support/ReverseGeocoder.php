<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Shared OpenCage reverse-geocoding used by Attendance, Outlet Visit, and MI.
 */
class ReverseGeocoder
{
    public const UNAVAILABLE = 'Location unavailable';

    public static function lookup(
        float $latitude,
        float $longitude,
        string $fallback = self::UNAVAILABLE
    ): string {
        $apiKey = env('OPEN_CAGE_API_KEY');
        if (empty($apiKey)) {
            return $fallback;
        }

        try {
            $response = Http::timeout(8)->get('https://api.opencagedata.com/geocode/v1/json', [
                'q' => "{$latitude},{$longitude}",
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return $fallback;
            }

            $json = $response->json();
            $short = self::shortAddress(data_get($json, 'results.0.components'));
            if ($short !== null) {
                return $short;
            }

            $formatted = data_get($json, 'results.0.formatted');
            if (is_string($formatted) && trim($formatted) !== '') {
                return trim($formatted);
            }
        } catch (Throwable $e) {
            // Keep caller fallback when geocoding fails.
        }

        return $fallback;
    }

    /**
     * Prefer a compact "Mirpur, Dhaka" style label when OpenCage components exist.
     */
    private static function shortAddress(mixed $components): ?string
    {
        if (! is_array($components)) {
            return null;
        }

        $locality = self::firstComponent($components, [
            'neighbourhood',
            'suburb',
            'city_district',
            'village',
            'town',
            'municipality',
        ]);
        $city = self::firstComponent($components, [
            'city',
            'county',
            'state_district',
            'state',
        ]);

        if ($locality !== null && $city !== null && strcasecmp($locality, $city) !== 0) {
            return $locality.', '.$city;
        }
        if ($locality !== null) {
            return $locality;
        }
        if ($city !== null) {
            return $city;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $components
     * @param  list<string>  $keys
     */
    private static function firstComponent(array $components, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $components[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
