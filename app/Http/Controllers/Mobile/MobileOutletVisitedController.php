<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\OutletVisitAssignment;
use App\Models\OutletVisitedVisit;
use App\Support\AppTimezone;
use App\Support\GeoDistance;
use App\Support\OutletVisitOccurrence;
use App\Support\OutletVisitTodayGate;
use App\Support\OutletVisitWeekCycle;
use App\Support\ReverseGeocoder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Mobile Scheduled Outlet Visit API.
 * Week 1–4 rotation is config-anchored (mobile.outlet_visit_week1_anchor).
 * Each occurrence is assignment_id + scheduled_date.
 */
class MobileOutletVisitedController extends Controller
{
    public function tasks(Request $request)
    {
        try {
            $employeeId = Auth::user()->EID;
            if (! $employeeId) {
                return response()->json(['success' => false, 'message' => 'Employee not linked.'], 403);
            }

            $now = AppTimezone::now();
            $year = (int) $request->query('year', $now->year);
            $month = (int) $request->query('month', $now->month);
            if ($month < 1 || $month > 12 || $year < 2020) {
                return response()->json(['success' => false, 'message' => 'Invalid year/month.'], 422);
            }

            $assignments = $this->loadAssignments($employeeId, $year, $month);
            $dates = $this->buildMonthDates($assignments, $year, $month);
            $occurrences = collect($dates)->flatMap(fn ($d) => $d['tasks']);
            $todayPlan = collect($dates)->firstWhere('is_today', true);

            $visited = $occurrences->where('status', 'visited')->count();
            $pending = $occurrences->whereIn('status', ['pending', 'checked_in'])->count();

            $weeks = [];
            for ($w = 1; $w <= 4; $w++) {
                $weekItems = $occurrences->where('week_no', $w)->values();
                $weeks[] = [
                    'week_no' => $w,
                    'assigned' => $weekItems->count(),
                    'visited' => $weekItems->where('status', 'visited')->count(),
                    'pending' => $weekItems->whereIn('status', ['pending', 'checked_in'])->count(),
                    'tasks' => $weekItems,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    'cycle_configured' => true,
                    'week1_anchor' => OutletVisitWeekCycle::ANCHOR_DATE,
                    'active_week_no' => OutletVisitWeekCycle::weekNo($now),
                    'today' => $now->toDateString(),
                    'today_weekday' => OutletVisitWeekCycle::weekdayName($now),
                    'summary' => [
                        'assigned' => $occurrences->count(),
                        'visited' => $visited,
                        'pending' => $pending,
                    ],
                    'weeks' => $weeks,
                    'dates' => $dates,
                    'today_plan' => [
                        'date' => $todayPlan['date'] ?? $now->toDateString(),
                        'weekday' => $todayPlan['weekday'] ?? ucfirst(OutletVisitWeekCycle::weekdayName($now)),
                        'is_today' => true,
                        'visitable' => (bool) ($todayPlan['visitable'] ?? false),
                        'assigned' => $todayPlan['assigned'] ?? 0,
                        'tasks' => $todayPlan['tasks'] ?? [],
                    ],
                    'delivery' => [
                        'assigned' => 0,
                        'visited' => 0,
                        'pending' => 0,
                        'tasks' => [],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $this->friendlyFailureMessage($e, 'Unable to load outlet visit tasks.'),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $employeeId = Auth::user()->EID;
        $assignment = $this->ownedAssignment((int) $id, $employeeId);
        if (! $assignment) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $now = AppTimezone::now();
        $date = (string) $request->query('date', $now->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['success' => false, 'message' => 'Invalid date.'], 422);
        }

        $scheduled = Carbon::parse($date, AppTimezone::NAME);

        return response()->json([
            'success' => true,
            'data' => $this->formatTask(
                $assignment,
                true,
                (int) $scheduled->year,
                (int) $scheduled->month,
                null,
                $date
            ),
        ]);
    }

    public function checkIn(Request $request, $id)
    {
        $employeeId = Auth::user()->EID;
        $assignment = $this->ownedAssignment((int) $id, $employeeId);
        if (! $assignment) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $today = AppTimezone::now()->toDateString();
        $gate = $this->todayVisitGate($assignment, $today);
        if ($gate !== null) {
            return $gate;
        }

        $existing = OutletVisitOccurrence::forDate($assignment->visits, $today);
        if ($existing && ($this->hasCheckOut($existing) || $this->isLegacyCompleted($existing))) {
            return response()->json([
                'success' => false,
                'message' => 'This outlet visit is already completed.',
                'data' => $this->formatTask($assignment, true, null, null, null, $today),
            ], 422);
        }

        if ($existing && $this->hasCheckIn($existing)) {
            return response()->json([
                'success' => false,
                'message' => 'Already checked in.',
                'data' => $this->formatTask($assignment, true, null, null, null, $today),
            ], 422);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];
        $locationName = ReverseGeocoder::lookup($lat, $lng);
        $at = AppTimezone::now();

        try {
            $visit = DB::transaction(function () use ($assignment, $employeeId, $validated, $lat, $lng, $locationName, $at, $today) {
                $payload = [
                    'assignment_id' => $assignment->id,
                    'employee_id' => $employeeId,
                    'distributor_id' => $assignment->distributor_id,
                    'outlet_code' => $assignment->outlet_code,
                    'outlet_name' => $assignment->outlet_name,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'remarks' => $validated['remarks'] ?? null,
                    'visited_at' => $at,
                    'created_by' => $employeeId,
                    'updated_by' => $employeeId,
                ];
                if (Schema::hasColumn('outlet_visited_visits', 'scheduled_date')) {
                    $payload['scheduled_date'] = $today;
                }
                if (Schema::hasColumn('outlet_visited_visits', 'location_name')) {
                    $payload['location_name'] = $locationName;
                }
                if (Schema::hasColumn('outlet_visited_visits', 'check_in_at')) {
                    $payload['check_in_at'] = $at;
                    $payload['check_in_latitude'] = $lat;
                    $payload['check_in_longitude'] = $lng;
                    $payload['check_in_location_name'] = $locationName;
                }

                return OutletVisitedVisit::create($payload);
            });
        } catch (\Throwable $e) {
            $fresh = $this->ownedAssignment((int) $id, $employeeId);
            $existingAgain = OutletVisitOccurrence::forDate($fresh?->visits, $today);
            if ($existingAgain && $this->hasCheckIn($existingAgain)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already checked in.',
                    'data' => $this->formatTask($fresh, true, null, null, null, $today),
                ], 422);
            }
            report($e);

            return response()->json([
                'success' => false,
                'message' => $this->friendlyFailureMessage($e, 'Unable to check in.'),
            ], 500);
        }

        $assignment->setRelation(
            'visits',
            $assignment->visits->push($visit)->unique('id')->values()
        );

        return response()->json([
            'success' => true,
            'message' => 'Checked in.',
            'data' => $this->formatTask($assignment, true, null, null, null, $today),
        ], 201);
    }

    public function checkOut(Request $request, $id)
    {
        $employeeId = Auth::user()->EID;
        $assignment = $this->ownedAssignment((int) $id, $employeeId);
        if (! $assignment) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $today = AppTimezone::now()->toDateString();
        $gate = $this->todayVisitGate($assignment, $today);
        if ($gate !== null) {
            return $gate;
        }

        $visit = OutletVisitOccurrence::forDate($assignment->visits, $today);
        if (! $visit || ! $this->hasCheckIn($visit)) {
            return response()->json([
                'success' => false,
                'message' => 'Check in before checking out.',
            ], 422);
        }

        if ($this->hasCheckOut($visit)) {
            return response()->json([
                'success' => false,
                'message' => 'Already checked out.',
                'data' => $this->formatTask($assignment, true, null, null, null, $today),
            ], 422);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $inLat = (float) ($visit->check_in_latitude ?? $visit->latitude);
        $inLng = (float) ($visit->check_in_longitude ?? $visit->longitude);
        $outLat = (float) $validated['latitude'];
        $outLng = (float) $validated['longitude'];
        $meters = GeoDistance::meters($inLat, $inLng, $outLat, $outLng);

        if ($meters > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Check-out rejected. You must be within 100 meters of the check-in location.',
                'data' => [
                    'distance_meters' => round($meters, 1),
                    'max_meters' => 100,
                ],
            ], 422);
        }

        $locationName = ReverseGeocoder::lookup($outLat, $outLng);
        $at = AppTimezone::now();

        $visit->check_out_at = $at;
        $visit->check_out_latitude = $outLat;
        $visit->check_out_longitude = $outLng;
        $visit->check_out_location_name = $locationName;
        if (! empty($validated['remarks'])) {
            $visit->remarks = $validated['remarks'];
        }
        $visit->updated_by = $employeeId;
        $visit->save();

        $assignment->setRelation(
            'visits',
            $assignment->visits->map(fn ($v) => $v->id === $visit->id ? $visit->fresh() : $v)
        );

        return response()->json([
            'success' => true,
            'message' => 'Checked out.',
            'data' => $this->formatTask($assignment, true, null, null, null, $today),
        ]);
    }

    public function submitVisit(Request $request, $id)
    {
        return $this->checkIn($request, $id);
    }

    public function history(Request $request)
    {
        try {
            $employeeId = Auth::user()->EID;
            $year = (int) $request->query('year', AppTimezone::now()->year);
            $month = (int) $request->query('month', AppTimezone::now()->month);
            $week = $request->query('week_no');

            $query = OutletVisitedVisit::query()
                ->where('employee_id', $employeeId)
                ->where(function ($q) use ($year, $month) {
                    $q->where(function ($inner) use ($year, $month) {
                        $inner->whereYear('visited_at', $year)->whereMonth('visited_at', $month);
                    })->orWhere(function ($inner) use ($year, $month) {
                        if (Schema::hasColumn('outlet_visited_visits', 'check_in_at')) {
                            $inner->whereYear('check_in_at', $year)->whereMonth('check_in_at', $month);
                        }
                    })->orWhere(function ($inner) use ($year, $month) {
                        if (Schema::hasColumn('outlet_visited_visits', 'scheduled_date')) {
                            $inner->whereYear('scheduled_date', $year)->whereMonth('scheduled_date', $month);
                        }
                    });
                })
                ->whereHas('assignment', function ($q) use ($week) {
                    if ($week !== null && $week !== '') {
                        $q->whereHas('weekSchedule', fn ($s) => $s->where('week_no', (int) $week));
                    }
                })
                ->with(['assignment.weekSchedule'])
                ->orderByDesc('check_in_at')
                ->orderByDesc('visited_at')
                ->orderByDesc('id');

            $rows = $query->limit(200)->get()->map(function (OutletVisitedVisit $v) {
                $a = $v->assignment;
                $status = 'visited';
                if ($this->hasCheckIn($v) && ! $this->hasCheckOut($v)) {
                    $status = 'checked_in';
                }

                return [
                    'visit_id' => $v->id,
                    'assignment_id' => $v->assignment_id,
                    'scheduled_date' => OutletVisitOccurrence::dateOf($v),
                    'outlet_code' => $v->outlet_code ?? $a?->outlet_code,
                    'outlet_name' => $v->outlet_name ?? $a?->outlet_name,
                    'activity_type' => 'visit',
                    'week_no' => (int) ($a?->weekSchedule?->week_no ?? 0),
                    'visited_at' => AppTimezone::toIso8601($v->visited_at ?? $v->check_in_at),
                    'check_in_at' => AppTimezone::toIso8601($v->check_in_at ?? $v->visited_at),
                    'check_out_at' => AppTimezone::toIso8601($v->check_out_at),
                    'check_in_location_name' => $v->check_in_location_name ?: $this->visitLocationName($v),
                    'check_out_location_name' => $v->check_out_location_name,
                    'status' => $status,
                    'remarks' => $v->remarks,
                    'image_url' => $this->publicUrl($v->image_path),
                    'location_name' => $this->visitLocationName($v),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    'visits' => $rows,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $this->friendlyFailureMessage($e, 'Unable to load outlet visit history.'),
            ], 500);
        }
    }

    private function todayVisitGate(OutletVisitAssignment $assignment, string $scheduledDate)
    {
        $deny = OutletVisitTodayGate::denyReason(
            (bool) $assignment->is_active,
            (int) ($assignment->weekSchedule?->week_no ?? 0),
            (string) ($assignment->weekSchedule?->day_name ?? ''),
            $scheduledDate,
            AppTimezone::now()
        );
        if ($deny === null) {
            return null;
        }

        return response()->json(['success' => false, 'message' => $deny], 422);
    }

    private function buildMonthDates($assignments, int $year, int $month): array
    {
        $configured = OutletVisitWeekCycle::isConfigured();
        $today = OutletVisitWeekCycle::today();
        $cursor = Carbon::create($year, $month, 1, 0, 0, 0, AppTimezone::NAME);
        $end = $cursor->copy()->endOfMonth();
        $dates = [];

        while ($cursor->lte($end)) {
            $weekNo = OutletVisitWeekCycle::weekNo($cursor);
            $weekday = OutletVisitWeekCycle::weekdayName($cursor);
            $isToday = $cursor->isSameDay($today);
            $scheduledDate = $cursor->toDateString();
            $dayTasks = $assignments
                ->filter(function (OutletVisitAssignment $a) use ($weekNo, $weekday) {
                    $w = (int) ($a->weekSchedule?->week_no ?? 0);
                    $d = strtolower((string) ($a->weekSchedule?->day_name ?? ''));

                    return $w === (int) $weekNo && $d === strtolower($weekday);
                })
                ->values()
                ->map(fn ($a) => $this->formatTask(
                    $a,
                    false,
                    $year,
                    $month,
                    $isToday && $configured,
                    $scheduledDate
                ));

            $dates[] = [
                'date' => $scheduledDate,
                'weekday' => ucfirst($weekday),
                'week_no' => $weekNo,
                'is_today' => $isToday,
                'visitable' => $isToday && $configured,
                'assigned' => $dayTasks->count(),
                'tasks' => $dayTasks,
            ];
            $cursor->addDay();
        }

        return $dates;
    }

    private function friendlyFailureMessage(\Throwable $e, string $fallback): string
    {
        $msg = $e->getMessage();

        if ($e instanceof \Illuminate\Database\QueryException
            || str_contains($msg, "doesn't exist")
            || str_contains($msg, 'Base table or view not found')
            || str_contains($msg, 'Unknown column')) {
            return 'Outlet Visit database tables are missing or incomplete. Need outlet_week_schedules, outlet_visit_assignments, and outlet_visited_visits (FK to outlet_visit_assignments).';
        }

        if (str_contains($msg, 'Call to undefined relationship')
            || str_contains($msg, 'OutletVisitAssignment')
            || str_contains($msg, 'Class "App\\Models\\OutletVisit')) {
            return 'Outlet Visit models are incomplete on the server. Redeploy OutletVisitAssignment, OutletWeekSchedule, and OutletVisitedVisit.';
        }

        return $fallback;
    }

    private function loadAssignments(int $employeeId, int $year, int $month)
    {
        return OutletVisitAssignment::query()
            ->with(['visits', 'weekSchedule'])
            ->where('employee_id', $employeeId)
            ->where(function ($q) use ($year, $month) {
                $q->where('is_active', true)
                    ->orWhereHas('visits', function ($v) use ($year, $month) {
                        $v->where(function ($inner) use ($year, $month) {
                            $inner->whereYear('visited_at', $year)->whereMonth('visited_at', $month);
                        })->orWhere(function ($inner) use ($year, $month) {
                            if (Schema::hasColumn('outlet_visited_visits', 'scheduled_date')) {
                                $inner->whereYear('scheduled_date', $year)->whereMonth('scheduled_date', $month);
                            }
                        });
                    });
            })
            ->orderBy('outlet_week_schedules_id')
            ->orderBy('outlet_name')
            ->orderBy('id')
            ->get();
    }

    private function ownedAssignment(int $id, $employeeId): ?OutletVisitAssignment
    {
        if (! $employeeId) {
            return null;
        }

        return OutletVisitAssignment::query()
            ->with(['visits', 'weekSchedule'])
            ->where('id', $id)
            ->where('employee_id', $employeeId)
            ->first();
    }

    private function formatTask(
        OutletVisitAssignment $a,
        bool $detailed = false,
        ?int $cycleYear = null,
        ?int $cycleMonth = null,
        ?bool $visitableOverride = null,
        ?string $scheduledDate = null
    ): array {
        $date = $scheduledDate ?: AppTimezone::now()->toDateString();
        $visit = OutletVisitOccurrence::forDate($a->visits, $date);
        $status = 'pending';
        if ($visit) {
            if ($this->hasCheckOut($visit) || $this->isLegacyCompleted($visit)) {
                $status = 'visited';
            } elseif ($this->hasCheckIn($visit)) {
                $status = 'checked_in';
            }
        }

        $weekNo = (int) ($a->weekSchedule?->week_no ?? 0);
        $dayName = $a->weekSchedule?->day_name;
        $now = AppTimezone::now();
        $scheduled = Carbon::parse($date, AppTimezone::NAME);
        $year = $cycleYear ?? (int) $scheduled->year;
        $month = $cycleMonth ?? (int) $scheduled->month;
        $configured = OutletVisitWeekCycle::isConfigured();
        $isToday = $scheduled->isSameDay($now);
        $activeWeek = OutletVisitWeekCycle::weekNo($now);
        $todayWeekday = OutletVisitWeekCycle::weekdayName($now);
        $matchesToday = $configured
            && $isToday
            && $weekNo === (int) $activeWeek
            && strtolower((string) $dayName) === strtolower($todayWeekday);
        $visitable = $visitableOverride ?? ($matchesToday && $status !== 'visited' && (bool) $a->is_active);

        $data = [
            'assignment_id' => $a->id,
            'scheduled_date' => $date,
            'occurrence_key' => $a->id.'_'.$date,
            'distributor_id' => (int) $a->distributor_id,
            'outlets_id' => 0,
            'outlet_code' => $a->outlet_code,
            'outlet_name' => $a->outlet_name,
            'outlet_address' => null,
            'latitude' => null,
            'longitude' => null,
            'activity_type' => 'visit',
            'week_no' => $weekNo,
            'day_name' => $dayName,
            'cycle_year' => $year,
            'cycle_month' => $month,
            'status' => $status,
            'visited_at' => AppTimezone::toIso8601($visit?->visited_at ?? $visit?->check_in_at),
            'check_in_at' => AppTimezone::toIso8601($visit?->check_in_at ?? ($status !== 'pending' ? $visit?->visited_at : null)),
            'check_out_at' => AppTimezone::toIso8601($visit?->check_out_at),
            'check_in_location_name' => $visit?->check_in_location_name ?: ($visit ? $this->visitLocationName($visit) : null),
            'check_out_location_name' => $visit?->check_out_location_name,
            'is_active' => (bool) $a->is_active,
            'visitable' => $visitable && $status !== 'visited' && $isToday,
            'can_check_in' => $visitable && $status === 'pending' && $isToday,
            'can_check_out' => $visitable && $status === 'checked_in' && $isToday,
            'visit_location_name' => $visit ? $this->visitLocationName($visit) : null,
        ];

        if ($detailed) {
            $data['remarks'] = $visit?->remarks;
            $data['image_url'] = $visit ? $this->publicUrl($visit->image_path) : null;
        }

        return $data;
    }

    private function hasCheckIn(?OutletVisitedVisit $visit): bool
    {
        return $visit !== null && ! empty($visit->check_in_at);
    }

    private function hasCheckOut(?OutletVisitedVisit $visit): bool
    {
        return $visit !== null && ! empty($visit->check_out_at);
    }

    private function isLegacyCompleted(?OutletVisitedVisit $visit): bool
    {
        return $visit !== null
            && empty($visit->check_in_at)
            && ! empty($visit->visited_at);
    }

    private function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return url(Storage::disk('public')->url($path));
    }

    private function visitLocationName(OutletVisitedVisit $visit): string
    {
        foreach (['check_in_location_name', 'location_name'] as $field) {
            $stored = trim((string) ($visit->{$field} ?? ''));
            if ($stored !== '') {
                return $stored;
            }
        }

        return class_exists(ReverseGeocoder::class)
            ? ReverseGeocoder::UNAVAILABLE
            : 'Location unavailable';
    }
}
