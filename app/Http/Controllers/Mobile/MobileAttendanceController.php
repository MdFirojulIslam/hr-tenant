<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Expense\EmplooyeeExpenseController;
use App\Models\app_attendance;
use App\Models\AttendanceSummary;
use App\Models\attendence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MobileAttendanceController extends Controller
{
    /**
     * Today's combined attendance status.
     *
     * Source of truth: attendance_summaries (mobile + machine merge).
     * App punches (app_attendances) are used as a fallback until daily sync
     * fills the summary, and for optional location/village display.
     */
    public function today(Request $request)
    {
        $employeeId = Auth::user()->EID;
        $today = now()->toDateString();

        $summary = null;
        if (Schema::hasTable('attendance_summaries')) {
            $summary = AttendanceSummary::query()
                ->where('employee_id', $employeeId)
                ->whereDate('date', $today)
                ->first();
        }

        $appCheckIn = app_attendance::where('EID', $employeeId)
            ->whereDate('created_at', $today)
            ->where('status', 'in')
            ->first();

        $appCheckOut = app_attendance::where('EID', $employeeId)
            ->whereDate('created_at', $today)
            ->where('status', 'out')
            ->first();

        $summaryIn = $summary && !empty($summary->in_time) ? (string) $summary->in_time : null;
        $summaryOut = $summary && !empty($summary->out_time) ? (string) $summary->out_time : null;

        // Combined state: summary first, app punches fill gaps before sync.
        $hasIn = $summaryIn !== null || $appCheckIn !== null;
        $hasOut = $summaryOut !== null || $appCheckOut !== null;

        $currentStatus = 'not_checked_in';
        if ($hasOut) {
            $currentStatus = 'checked_out';
        } elseif ($hasIn) {
            $currentStatus = 'checked_in';
        }

        $source = $this->inferSourceForDate($employeeId, $today, (bool) $appCheckIn || (bool) $appCheckOut);

        $checkIn = null;
        if ($hasIn) {
            $checkIn = [
                'status' => 'in',
                'time' => $summaryIn ?? optional($appCheckIn)->created_at?->format('H:i:s'),
                'village' => optional($appCheckIn)->village,
                'from_summary' => $summaryIn !== null,
                'from_app' => $appCheckIn !== null,
            ];
        }

        $checkOut = null;
        if ($hasOut) {
            $checkOut = [
                'status' => 'out',
                'time' => $summaryOut ?? optional($appCheckOut)->created_at?->format('H:i:s'),
                'village' => optional($appCheckOut)->village,
                'from_summary' => $summaryOut !== null,
                'from_app' => $appCheckOut !== null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today,
                'current_status' => $currentStatus,
                'source' => $source,
                'summary' => $summary ? [
                    'status' => $summary->status,
                    'in_time' => $summaryIn,
                    'out_time' => $summaryOut,
                    'remarks' => $summary->remarks,
                ] : null,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'can_check_in' => !$hasIn,
                'can_check_out' => $hasIn && !$hasOut,
            ],
        ]);
    }

    /**
     * Monthly attendance history from hr_db.attendance_summaries.
     * Identity is always Auth::user()->EID — client cannot request another employee.
     */
    public function history(Request $request)
    {
        $employeeId = Auth::user()->EID;
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid month or year.',
            ], 422);
        }

        if (!Schema::hasTable('attendance_summaries')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    'summary' => $this->emptyCounts(),
                    'records' => [],
                    'table_ready' => false,
                ],
                'message' => 'Attendance summaries table is not available yet.',
            ]);
        }

        $today = now()->toDateString();

        $records = AttendanceSummary::query()
            ->where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereDate('date', '<=', $today)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'employee_id',
                'date',
                'status',
                'in_time',
                'out_time',
                'rostered_in_time',
                'rostered_out_time',
                'rostered_status',
                'remarks',
            ]);

        $appDays = $this->appAttendanceDates($employeeId, $year, $month);
        $machineDays = $this->machineAttendanceDates($employeeId, $year, $month);

        $counts = $this->emptyCounts();
        $payload = [];

        foreach ($records as $row) {
            $key = match (strtolower(trim((string) $row->status))) {
                'present' => 'present',
                'absent' => 'absent',
                'late' => 'late',
                'on leave' => 'on_leave',
                'holiday' => 'holiday',
                'manual' => 'manual',
                'early leave' => 'early_leave',
                'friday' => 'friday',
                default => 'other',
            };
            $counts[$key]++;

            $dateKey = $row->date instanceof \Carbon\Carbon
                ? $row->date->toDateString()
                : (string) $row->date;

            $hasApp = isset($appDays[$dateKey]);
            $hasMachine = isset($machineDays[$dateKey]);
            $source = 'Unknown';
            if ($hasApp && $hasMachine) {
                $source = 'Combined';
            } elseif ($hasApp) {
                $source = 'Mobile';
            } elseif ($hasMachine) {
                $source = 'Machine';
            }

            $payload[] = [
                'id' => $row->id,
                'employee_id' => $row->employee_id,
                'date' => $dateKey,
                'status' => $row->status,
                'in_time' => $row->in_time,
                'out_time' => $row->out_time,
                'rostered_in_time' => $row->rostered_in_time,
                'rostered_out_time' => $row->rostered_out_time,
                'rostered_status' => $row->rostered_status,
                'remarks' => $row->remarks,
                'source' => $source,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'summary' => $counts,
                'records' => $payload,
                'table_ready' => true,
            ],
        ]);
    }

    public function towns($employeeId)
    {
        try {
            $towns = app(EmplooyeeExpenseController::class)->fetchUserTown($employeeId);
            $payload = json_decode($towns->getContent(), true);

            return response()->json([
                'success' => true,
                'data' => is_array($payload) ? $payload : [],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }
    }

    public function checkIn(Request $request)
    {
        return $this->storeAttendance($request, 'in');
    }

    public function checkOut(Request $request)
    {
        return $this->storeAttendance($request, 'out');
    }

    private function storeAttendance(Request $request, string $status)
    {
        $employeeId = Auth::user()->EID;

        $request->validate([
            'image' => 'required|string',
            'location' => 'required|array',
            'location.lat' => 'required|numeric',
            'location.lng' => 'required|numeric',
            'timestamp' => 'required|date',
            'town_id' => 'nullable|integer',
        ]);

        try {
            $date = date('Y-m-d', strtotime($request->timestamp));

            $existing = app_attendance::where('EID', $employeeId)
                ->whereDate('created_at', $date)
                ->where('status', $status)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance already recorded for today.',
                ], 400);
            }

            $summary = null;
            if (Schema::hasTable('attendance_summaries')) {
                $summary = AttendanceSummary::query()
                    ->where('employee_id', $employeeId)
                    ->whereDate('date', $date)
                    ->first();
            }
            $summaryHasIn = $summary && !empty($summary->in_time);
            $summaryHasOut = $summary && !empty($summary->out_time);

            if ($status === 'in' && $summaryHasIn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already checked in for today.',
                ], 400);
            }

            if ($status === 'out' && $summaryHasOut) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already checked out for today.',
                ], 400);
            }

            if ($status === 'out') {
                $hasAppIn = app_attendance::where('EID', $employeeId)
                    ->whereDate('created_at', $date)
                    ->where('status', 'in')
                    ->exists();

                // Scenario C: machine check-in may exist only in summary.
                if (!$hasAppIn && !$summaryHasIn) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please check in first.',
                    ], 400);
                }
            }

            $raw = preg_replace('#^data:image/\w+;base64,#i', '', $request->image);
            $imageData = base64_decode($raw, true);

            if ($imageData === false || strlen($imageData) < 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid attendance photo.',
                ], 422);
            }

            $imageName = 'attendance/' . uniqid('m_', true) . '.png';
            Storage::disk('public')->put($imageName, $imageData);

            $lat = (float) $request->input('location.lat');
            $lng = (float) $request->input('location.lng');
            $locationName = $this->resolveLocationName($lat, $lng);

            $attendance = app_attendance::create([
                'EID' => $employeeId,
                'town_id' => $request->town_id,
                'status' => $status,
                'image_path' => $imageName,
                'latitude' => $lat,
                'longitude' => $lng,
                'village' => $locationName,
            ]);

            return response()->json([
                'success' => true,
                'message' => $status === 'in' ? 'Checked in successfully.' : 'Checked out successfully.',
                'data' => $attendance,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Infer punch origin for display. Not stored on attendance_summaries.
     */
    private function inferSourceForDate(int $employeeId, string $date, bool $hasAppPunch): string
    {
        $hasMachine = false;
        if (Schema::hasTable('attendences')) {
            $hasMachine = attendence::where('EID', $employeeId)
                ->whereDate('Date', $date)
                ->whereNotNull('Time_In')
                ->where('Time_In', '!=', '')
                ->exists();
        }

        if ($hasAppPunch && $hasMachine) {
            return 'Combined';
        }
        if ($hasAppPunch) {
            return 'Mobile';
        }
        if ($hasMachine) {
            return 'Machine';
        }

        return 'Unknown';
    }

    /**
     * @return array<string, bool>
     */
    private function appAttendanceDates(int $employeeId, int $year, int $month): array
    {
        $rows = app_attendance::query()
            ->where('EID', $employeeId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw('DATE(created_at) as d')
            ->distinct()
            ->pluck('d');

        $map = [];
        foreach ($rows as $d) {
            $map[(string) $d] = true;
        }

        return $map;
    }

    /**
     * @return array<string, bool>
     */
    private function machineAttendanceDates(int $employeeId, int $year, int $month): array
    {
        if (!Schema::hasTable('attendences')) {
            return [];
        }

        $rows = attendence::query()
            ->where('EID', $employeeId)
            ->whereYear('Date', $year)
            ->whereMonth('Date', $month)
            ->whereNotNull('Time_In')
            ->where('Time_In', '!=', '')
            ->selectRaw('DATE(`Date`) as d')
            ->distinct()
            ->pluck('d');

        $map = [];
        foreach ($rows as $d) {
            $map[(string) $d] = true;
        }

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private function emptyCounts(): array
    {
        return [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'on_leave' => 0,
            'holiday' => 0,
            'manual' => 0,
            'early_leave' => 0,
            'friday' => 0,
            'other' => 0,
        ];
    }

    private function resolveLocationName(float $latitude, float $longitude): string
    {
        $fallback = round($latitude, 5) . ', ' . round($longitude, 5);
        $apiKey = env('OPEN_CAGE_API_KEY');

        if (empty($apiKey)) {
            return $fallback;
        }

        try {
            $response = Http::timeout(8)->get('https://api.opencagedata.com/geocode/v1/json', [
                'q' => "{$latitude},{$longitude}",
                'key' => $apiKey,
            ]);

            if ($response->successful()) {
                $formatted = data_get($response->json(), 'results.0.formatted');
                if (is_string($formatted) && $formatted !== '') {
                    return $formatted;
                }
            }
        } catch (Throwable $e) {
            // Keep fallback coordinates when geocoding fails.
        }

        return $fallback;
    }
}
