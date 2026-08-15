<?php

namespace App\Http\Middleware;

use App\Models\user_has_page;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobilePagePermission
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        $user = $request->user();

        if (!$user || !$user->EID) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $requiredPageId = config("mobile.permissions.{$permissionKey}");

        // Fallback IDs when live config/mobile.php is behind the app (pre-deploy).
        if (!$requiredPageId) {
            $fallback = [
                'attendance' => 32,
                'leave' => 15,
                'expense' => 28,
                'expense_records' => 29,
                'itinerary' => 47,
                'itinerary_daily' => 48,
                'itinerary_admin' => 50,
                'daily_order_target' => 66,
                'market_intelligence' => 67,
                'outlet_visited' => 70,
                'outlet_visited_admin' => 69,
                'mom' => 71,
                'vehicle_requisition' => 38,
                'task_board_admin' => 72,
                'task_board' => 73,
                'event_setup' => 74,
                // 75 is registered only; mobile Event routes must stay auth-only.
                'events' => 75,
            ];
            $requiredPageId = $fallback[$permissionKey] ?? null;
        }

        if (!$requiredPageId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid permission configuration.',
            ], 500);
        }

        $permissions = user_has_page::where('EID', $user->EID)->pluck('Page_Id');
        $pageIds = [];

        foreach ($permissions as $permission) {
            $decoded = json_decode($permission, true);
            if (is_array($decoded)) {
                $pageIds = array_merge($pageIds, $decoded);
            }
        }

        if (!in_array((int) $requiredPageId, array_map('intval', $pageIds), true)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this feature.',
            ], 403);
        }

        return $next($request);
    }
}
