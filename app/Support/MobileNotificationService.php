<?php

namespace App\Support;

use App\Models\MobileNotification;
use App\Models\user_has_page;
use Illuminate\Support\Facades\Schema;

class MobileNotificationService
{
    public static function notifyOnce(
        int $eid,
        string $type,
        string $businessKey,
        string $title,
        string $body,
        ?string $route = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): ?MobileNotification {
        try {
            if (! Schema::hasTable('mobile_notifications')) {
                return null;
            }

            $existing = MobileNotification::where('eid', $eid)
                ->where('type', $type)
                ->where('business_key', $businessKey)
                ->first();
            if ($existing) {
                return null;
            }
            return MobileNotification::create([
                'eid' => $eid,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'business_key' => $businessKey,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'route' => $route,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function hasPage(int $eid, int $pageId): bool
    {
        $permissions = user_has_page::where('EID', $eid)->pluck('Page_Id');
        foreach ($permissions as $permission) {
            $decoded = json_decode($permission, true);
            if (is_array($decoded) && in_array($pageId, array_map('intval', $decoded), true)) {
                return true;
            }
        }

        return false;
    }

    public static function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }
}
