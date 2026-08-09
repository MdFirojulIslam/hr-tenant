<?php

namespace App\Http\Controllers\Mobile\V1\Usage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    private const MAX_SESSION_SECONDS = 12 * 60 * 60;
    private const MIN_SESSION_SECONDS = 5;

    public function storeSession(Request $request)
    {
        $employeeId = Auth::user()?->EID;
        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee identity required.',
            ], 403);
        }

        if (!Schema::hasTable('mobile_usage_sessions')) {
            return response()->json([
                'success' => false,
                'message' => 'Usage tracking is not available yet.',
            ], 503);
        }

        $validated = $request->validate([
            'client_session_id' => ['nullable', 'uuid'],
            'session_started_at' => ['required', 'date'],
            'session_ended_at' => ['required', 'date', 'after:session_started_at'],
        ]);

        try {
            $started = Carbon::parse($validated['session_started_at']);
            $ended = Carbon::parse($validated['session_ended_at']);
            $duration = $started->diffInSeconds($ended);

            if ($duration < self::MIN_SESSION_SECONDS) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session too short; ignored.',
                    'data' => null,
                ]);
            }

            if ($duration > self::MAX_SESSION_SECONDS) {
                $duration = self::MAX_SESSION_SECONDS;
                $ended = (clone $started)->addSeconds($duration);
            }

            $clientId = $validated['client_session_id'] ?? null;
            if ($clientId) {
                $existing = MobileUsageSession::where('client_session_id', $clientId)->first();
                if ($existing) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Session already recorded.',
                        'data' => $existing,
                    ]);
                }
            }

            $session = MobileUsageSession::create([
                'employee_id' => $employeeId,
                'user_id' => Auth::id(),
                'usage_date' => $started->toDateString(),
                'session_started_at' => $started,
                'session_ended_at' => $ended,
                'duration_seconds' => $duration,
                'client_session_id' => $clientId,
                'created_by' => $employeeId,
                'updated_by' => $employeeId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session recorded.',
                'data' => $session,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to record usage session.',
            ], 500);
        }
    }
}
