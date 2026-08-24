<?php

namespace App\Http\Controllers\DMS;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\employee;
use App\Models\OutletVisitAssignment;
use App\Models\OutletWeekSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DmsDistributionInfoController extends Controller
{
    public function getDistributorInfo(Request $request)
    {


        $response = Http::withHeaders([
            'X-Mobile-App-Key' => config('services.dms.mobile_app_key'),
            'Accept' => 'application/json',
        ])->get(config('services.dms.url') . '/api/v2.0/mobile-app/distributors');
        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to connect distributor from DMS'
            ], $response->status());
        }
        return response()->json($response->json());
        // return response()->json(Distributor::get());
    }

    public function singleEmployee(Request $request)
    {
        $result = employee::select('employees.id as employee_id', 'employees.Full_Name as employee_name')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->where('officials.Status', 'N')
            ->get();

        return response()->json($result);
    }

    public function getOutletCode(Request $request, $distributor_id)
    {
        $query = [
            'per_page' => 100,
            'active' => 1,
        ];

        if ($request->filled('q')) {
            $query['q'] = $request->input('q');
        }

        $response = Http::withHeaders([
            'X-Mobile-App-Key' => config('services.dms.mobile_app_key'),
            'Accept' => 'application/json',
        ])->get(
            config('services.dms.url')
                . '/api/v2.0/mobile-app/distributors/'
                . $distributor_id
                . '/outlets',
            $query
        );

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch outlets from DMS',
                'status' => $response->status(),
            ], $response->status());
        }

        return response()->json($response->json());
    }

    public function outletVisitSetup(Request $request)
    {
        $validated = $request->validate([
            'distributor_id' => 'required|integer',
            'employee_id' => 'required|integer',
            'schedules' => 'required|array',
            'schedules.*.outlet_week_schedules_id' => 'required|integer',
            'schedules.*.week_no' => 'required|integer',
            'schedules.*.day_name' => 'required|string',
            'schedules.*.outlets' => 'nullable|array',
            'schedules.*.outlets.*.outlet_code' => 'required|string',
            'schedules.*.outlets.*.outlet_name' => 'required|string',
        ]);

        $distributorId = $validated['distributor_id'];
        $employeeId = $validated['employee_id'];
        $createdBy = Auth::user()->EID;

        $rows = [];

        foreach ($validated['schedules'] as $schedule) {
            $outlets = $schedule['outlets'] ?? [];

            if (empty($outlets)) {
                continue;
            }

            $scheduleRecord = OutletWeekSchedule::find($schedule['outlet_week_schedules_id']);

            if (
                !$scheduleRecord
                || (int) $scheduleRecord->week_no !== (int) $schedule['week_no']
                || strtolower($scheduleRecord->day_name) !== strtolower($schedule['day_name'])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid week/day schedule provided. Please reload the page and try again.',
                ], 422);
            }

            $scheduleDayLabel = ucfirst(strtolower($scheduleRecord->day_name));
            $seenCodes = [];

            foreach ($outlets as $outlet) {
                $code = trim((string) $outlet['outlet_code']);

                if (isset($seenCodes[$code])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Outlet "' . $code . '" has been entered more than once for Week ' . $scheduleRecord->week_no . ' - ' . $scheduleDayLabel . '. Every outlet must be unique within the same week and day.',
                    ], 422);
                }

                $seenCodes[$code] = true;

                $rows[] = [
                    'outlet_week_schedules_id' => $scheduleRecord->id,
                    'employee_id' => $employeeId,
                    'distributor_id' => $distributorId,
                    'outlet_code' => $outlet['outlet_code'],
                    'outlet_name' => $outlet['outlet_name'],
                    'visit_date' => null,
                    'is_active' => true,
                    'inactivated_on' => null,
                    'inactivated_by' => null,
                    'created_by' => $createdBy,
                    'updated_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $alreadyAssigned = DB::table('outlet_visit_assignments')
                ->where('employee_id', $employeeId)
                ->where('distributor_id', $distributorId)
                ->where('outlet_week_schedules_id', $scheduleRecord->id)
                ->where('is_active', true)
                ->whereIn('outlet_code', array_keys($seenCodes))
                ->pluck('outlet_code')
                ->unique()
                ->values()
                ->all();

            if (count($alreadyAssigned) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'The following outlet(s) are already assigned for Week ' . $scheduleRecord->week_no . ' - ' . $scheduleDayLabel . ' and cannot be assigned again: ' . implode(', ', $alreadyAssigned) . '.',
                ], 422);
            }
        }

        if (count($rows) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No outlet data found to save.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($rows) {
                DB::table('outlet_visit_assignments')->insert($rows);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save outlet visit setup. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => count($rows) . ' outlet visit assignment(s) saved successfully.',
        ]);
    }

    public function outletVisitSetupList(Request $request)
    {
        $rows = DB::table('outlet_visit_assignments as ova')
            ->join('distributors', 'distributors.distributor_id', '=', 'ova.distributor_id')
            ->join('outlet_week_schedules as ows', 'ows.id', '=', 'ova.outlet_week_schedules_id')
            ->join('employees as e', 'e.id', '=', 'ova.employee_id')
            ->join('officials', 'officials.EID', '=', 'ova.employee_id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->where('ova.is_active', true)
            ->select(
                'ova.distributor_id',
                'distributors.distributor_name',
                'departments.Name as department_name',
                'ova.employee_id',
                'e.Full_Name as employee_name',
                'ows.week_no',
                'ova.outlet_code'
            )
            ->get();

        $groups = [];

        foreach ($rows as $row) {
            $key = $row->distributor_id . '_' . $row->employee_id;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'distributor_id' => (int) $row->distributor_id,
                    'employee_id' => (int) $row->employee_id,
                    'department_name' => $row->department_name,
                    'distributor_name' => $row->distributor_name,
                    'employee_name' => $row->employee_name,
                    'weeks' => [],
                    'outlets' => [],
                    'total_outlets' => 0,
                ];
            }

            $groups[$key]['weeks'][(int) $row->week_no] = true;

            // Unique outlet count
            $groups[$key]['outlets'][(string) $row->outlet_code] = true;

            // Total outlet count
            $groups[$key]['total_outlets']++;
        }

        $data = array_map(function ($group) {
            return [
                'distributor_id' => $group['distributor_id'],
                'employee_id' => $group['employee_id'],
                'department_name' => $group['department_name'],
                'distributor_name' => $group['distributor_name'],
                'employee_name' => $group['employee_name'],
                'completed_weeks' => count($group['weeks']),
                'total_weeks' => 4,
                'unique_outlet_count' => count($group['outlets']),
                'total_outlet_count' => $group['total_outlets'],
            ];
        }, array_values($groups));

        usort($data, function ($a, $b) {
            return $a['employee_id'] <=> $b['employee_id'];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function outletVisitSetupDetails(Request $request, $employee_id, $distributor_id)
    {
        $employeeName = employee::where('id', (int) $employee_id)->value('Full_Name');

        $assignments = DB::table('outlet_visit_assignments as ova')
            ->join('outlet_week_schedules as ows', 'ows.id', '=', 'ova.outlet_week_schedules_id')
            ->where('ova.employee_id', (int) $employee_id)
            ->where('ova.distributor_id', (int) $distributor_id)
            ->where('ova.is_active', true)
            ->select('ova.outlet_code', 'ova.outlet_name', 'ows.week_no', 'ows.day_name')
            ->orderBy('ows.week_no')
            ->orderBy('ows.id')
            ->get();

        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];

        $schedules = [];
        for ($week = 1; $week <= 4; $week++) {
            $schedules[$week] = [];
            foreach ($days as $day) {
                $schedules[$week][$day] = [];
            }
        }

        foreach ($assignments as $assignment) {
            $schedules[$assignment->week_no][strtolower($assignment->day_name)][] = [
                'outlet_code' => $assignment->outlet_code,
                'outlet_name' => $assignment->outlet_name,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'distributor_id' => (int) $distributor_id,
                'employee_id' => (int) $employee_id,
                'employee_name' => $employeeName,
                'schedules' => $schedules,
            ],
        ]);
    }

    public function outletVisitSetupUpdate(Request $request, $employee_id, $distributor_id)
    {
        $validated = $request->validate([
            'distributor_id' => 'required|integer',
            'employee_id' => 'required|integer',
            'schedules' => 'required|array',
            'schedules.*.outlet_week_schedules_id' => 'required|integer',
            'schedules.*.week_no' => 'required|integer',
            'schedules.*.day_name' => 'required|string',
            'schedules.*.outlets' => 'nullable|array',
            'schedules.*.outlets.*.outlet_code' => 'required|string',
            'schedules.*.outlets.*.outlet_name' => 'required|string',
        ]);

        if (
            (int) $validated['employee_id'] !== (int) $employee_id
            || (int) $validated['distributor_id'] !== (int) $distributor_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Setup owner mismatch. Please reload the page and try again.',
            ], 422);
        }

        $createdBy = Auth::user()->EID;

        $rows = [];

        foreach ($validated['schedules'] as $schedule) {
            $outlets = $schedule['outlets'] ?? [];

            if (empty($outlets)) {
                continue;
            }

            $scheduleRecord = OutletWeekSchedule::find($schedule['outlet_week_schedules_id']);

            if (
                !$scheduleRecord
                || (int) $scheduleRecord->week_no !== (int) $schedule['week_no']
                || strtolower($scheduleRecord->day_name) !== strtolower($schedule['day_name'])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid week/day schedule provided. Please reload the page and try again.',
                ], 422);
            }

            $scheduleDayLabel = ucfirst(strtolower($scheduleRecord->day_name));
            $seenCodes = [];

            foreach ($outlets as $outlet) {
                $code = trim((string) $outlet['outlet_code']);

                if (isset($seenCodes[$code])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Outlet "' . $code . '" has been entered more than once for Week ' . $scheduleRecord->week_no . ' - ' . $scheduleDayLabel . '. Every outlet must be unique within the same week and day.',
                    ], 422);
                }

                $seenCodes[$code] = true;

                $rows[] = [
                    'outlet_week_schedules_id' => $scheduleRecord->id,
                    'employee_id' => $employee_id,
                    'distributor_id' => $distributor_id,
                    'outlet_code' => $outlet['outlet_code'],
                    'outlet_name' => $outlet['outlet_name'],
                    'visit_date' => null,
                    'is_active' => true,
                    'inactivated_on' => null,
                    'inactivated_by' => null,
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (count($rows) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No outlet data found to save.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($rows, $employee_id, $distributor_id, $createdBy) {
                DB::table('outlet_visit_assignments')
                    ->where('employee_id', $employee_id)
                    ->where('distributor_id', $distributor_id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'inactivated_on' => now(),
                        'inactivated_by' => $createdBy,
                        'updated_by' => $createdBy,
                        'updated_at' => now(),
                    ]);

                DB::table('outlet_visit_assignments')->insert($rows);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update outlet visit setup. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => count($rows) . ' outlet visit assignment(s) updated successfully.',
        ]);
    }

    public function outletVisitSingleOutletDelete(Request $request, $employee_id, $distributor_id)
    {
        $validated = $request->validate([
            'week_number' => 'required|integer',
            'day_name' => 'required|string',
            'outlet_code' => 'required|string',
        ]);

        if (!employee::where('id', (int) $employee_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        }

        if (!Distributor::where('distributor_id', (int) $distributor_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Distributor not found.',
            ], 404);
        }

        $scheduleRecord = OutletWeekSchedule::where('week_no', (int) $validated['week_number'])
            ->whereRaw('LOWER(day_name) = ?', [strtolower($validated['day_name'])])
            ->first();

        if (!$scheduleRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid week/day schedule provided.',
            ], 422);
        }

        $outletCode = trim((string) $validated['outlet_code']);
        $deletedBy = Auth::user()->EID;

        try {
            $deleted = DB::transaction(function () use ($employee_id, $distributor_id, $scheduleRecord, $outletCode, $deletedBy) {
                return DB::table('outlet_visit_assignments')
                    ->where('employee_id', (int) $employee_id)
                    ->where('distributor_id', (int) $distributor_id)
                    ->where('outlet_week_schedules_id', $scheduleRecord->id)
                    ->where('outlet_code', $outletCode)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'inactivated_on' => now(),
                        'inactivated_by' => $deletedBy,
                        'updated_by' => $deletedBy,
                        'updated_at' => now(),
                    ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete the selected outlet assignment. Please try again.',
            ], 500);
        }

        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No matching outlet assignment found for this employee, distributor, week and day. Nothing was deleted.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'The selected outlet assignment has been deleted.',
        ]);
    }

    public function outletVisitSingleWeekDetails(Request $request, $employee_id, $distributor_id, $week_no, $day_name)
    {
        $scheduleRecord = OutletWeekSchedule::where('week_no', (int) $week_no)
            ->whereRaw('LOWER(day_name) = ?', [strtolower($day_name)])
            ->first();

        if (!$scheduleRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid week/day schedule provided.',
            ], 422);
        }

        $outlets = DB::table('outlet_visit_assignments')
            ->where('outlet_week_schedules_id', $scheduleRecord->id)
            ->where('employee_id', (int) $employee_id)
            ->where('distributor_id', (int) $distributor_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['outlet_code', 'outlet_name']);

        return response()->json([
            'success' => true,
            'data' => [
                'distributor_id' => (int) $distributor_id,
                'employee_id' => (int) $employee_id,
                'week_no' => (int) $week_no,
                'day_name' => strtolower($day_name),
                'outlets' => $outlets,
            ],
        ]);
    }

    public function outletVisitSingleWeekSetup(Request $request)
    {
        $validated = $request->validate([
            'distributor_id' => 'required|integer',
            'employee_id' => 'required|integer',
            'week_no' => 'required|integer',
            'day_name' => 'required|string',
            'outlets' => 'nullable|array',
            'outlets.*.outlet_code' => 'required|string',
            'outlets.*.outlet_name' => 'required|string',
        ]);

        $scheduleRecord = OutletWeekSchedule::where('week_no', (int) $validated['week_no'])
            ->whereRaw('LOWER(day_name) = ?', [strtolower($validated['day_name'])])
            ->first();

        if (!$scheduleRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid week/day schedule provided.',
            ], 422);
        }

        $employeeId = $validated['employee_id'];
        $distributorId = $validated['distributor_id'];
        $createdBy = Auth::user()->EID;

        $rows = [];
        $seenCodes = [];

        foreach ($validated['outlets'] ?? [] as $outlet) {
            $code = trim((string) $outlet['outlet_code']);

            if (isset($seenCodes[$code])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Outlet "' . $code . '" has been entered more than once for Week ' . $scheduleRecord->week_no . ' - ' . ucfirst(strtolower($scheduleRecord->day_name)) . '. Every outlet must be unique within the same week and day.',
                ], 422);
            }

            $seenCodes[$code] = true;

            $rows[] = [
                'outlet_week_schedules_id' => $scheduleRecord->id,
                'employee_id' => $employeeId,
                'distributor_id' => $distributorId,
                'outlet_code' => $outlet['outlet_code'],
                'outlet_name' => $outlet['outlet_name'],
                'visit_date' => null,
                'is_active' => true,
                'inactivated_on' => null,
                'inactivated_by' => null,
                'created_by' => $createdBy,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        try {
            DB::transaction(function () use ($rows, $employeeId, $distributorId, $scheduleRecord, $createdBy) {
                DB::table('outlet_visit_assignments')
                    ->where('employee_id', $employeeId)
                    ->where('distributor_id', $distributorId)
                    ->where('outlet_week_schedules_id', $scheduleRecord->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'inactivated_on' => now(),
                        'inactivated_by' => $createdBy,
                        'updated_by' => $createdBy,
                        'updated_at' => now(),
                    ]);

                if (count($rows) > 0) {
                    DB::table('outlet_visit_assignments')->insert($rows);
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save outlet visit setup. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => count($rows) . ' outlet visit assignment(s) saved successfully.',
        ]);
    }

    public function outletVisitReport(Request $request)
    {
        $validated = $request->validate([
            'year'          => 'required|integer|min:2020|max:2100',
            'month'         => 'required|integer|min:1|max:12',
            'date'          => 'nullable|date_format:Y-m-d',
            'employee_id'   => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'download'      => 'nullable|string',
        ]);

        $year      = (int) $validated['year'];
        $month     = (int) $validated['month'];
        $isDownload = isset($validated['download']) && $validated['download'] === 'csv';

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $filterDate = null;
        if (!empty($validated['date'])) {
            $parsed = Carbon::parse($validated['date']);
            if ($parsed->gte($startDate) && $parsed->lte($endDate)) {
                $filterDate = $parsed->format('Y-m-d');
            }
        }

        $weekSchedules = OutletWeekSchedule::all();
        $scheduleLookup = [];
        foreach ($weekSchedules as $ws) {
            $key = strtolower($ws->day_name) . '_' . $ws->week_no;
            $scheduleLookup[$key] = (int) $ws->id;
        }

        $dateScheduleMap = [];
        $occurrenceCounts = [];
        $daysInMonth = $startDate->daysInMonth;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date    = Carbon::create($year, $month, $d);
            $dayName = strtolower($date->englishDayOfWeek);

            if (!isset($occurrenceCounts[$dayName])) {
                $occurrenceCounts[$dayName] = 0;
            }
            $occurrenceCounts[$dayName]++;
            $weekNo = $occurrenceCounts[$dayName];

            $key = $dayName . '_' . $weekNo;
            if (isset($scheduleLookup[$key])) {
                $dateKey = $date->format('Y-m-d');
                if (!isset($dateScheduleMap[$dateKey])) {
                    $dateScheduleMap[$dateKey] = [];
                }
                $dateScheduleMap[$dateKey][] = $scheduleLookup[$key];
            }
        }

        $allScheduleIds = collect($dateScheduleMap)->flatten()->unique()->values()->all();

        $report      = [];
        $allEmployees = [];
        $allDepartments = [];

        if (!empty($allScheduleIds)) {
            $assignments = DB::table('outlet_visit_assignments as ova')
                ->join('outlet_week_schedules as ows', 'ows.id', '=', 'ova.outlet_week_schedules_id')
                ->join('employees as e', 'e.id', '=', 'ova.employee_id')
                ->leftJoin('officials as off', 'off.EID', '=', 'ova.employee_id')
                ->leftJoin('departments as d', 'd.id', '=', 'off.Department_Id')
                ->leftJoin('designations as des', 'des.id', '=', 'off.Designation_Id')
                ->leftJoin('distributors as dist', 'dist.distributor_id', '=', 'ova.distributor_id')
                ->whereIn('ova.outlet_week_schedules_id', $allScheduleIds)
                ->where('ova.is_active', true)
                ->select(
                    'ova.id as assignment_id',
                    'ova.outlet_week_schedules_id',
                    'ova.employee_id',
                    'ova.distributor_id',
                    'ova.outlet_code',
                    'ova.outlet_name',
                    'e.Full_Name as employee_name',
                    'd.id as department_id',
                    'd.Name as department_name',
                    'des.Name as designation_name',
                    'dist.distributor_name'
                )
                ->get();

            $allEmployees = $assignments->unique('employee_id')
                ->map(fn($a) => ['id' => (int) $a->employee_id, 'name' => $a->employee_name ?? '-'])
                ->values()->sortBy('name')->values()->all();

            $allDepartments = $assignments->filter(fn($a) => !empty($a->department_id))
                ->unique('department_id')
                ->map(fn($a) => ['id' => (int) $a->department_id, 'name' => $a->department_name ?? '-'])
                ->values()->sortBy('name')->values()->all();

            $assignmentIds = $assignments->pluck('assignment_id')->all();
            $visits = collect();

            if (!empty($assignmentIds)) {
                $visits = DB::table('outlet_visited_visits')
                    ->whereIn('assignment_id', $assignmentIds)
                    ->where('scheduled_date', '>=', $startDate->format('Y-m-d'))
                    ->where('scheduled_date', '<=', $endDate->format('Y-m-d'))
                    ->get();
            }

            foreach ($assignments as $a) {
                $scheduleId = (int) $a->outlet_week_schedules_id;

                foreach ($dateScheduleMap as $dateStr => $scheduleIds) {
                    if (!in_array($scheduleId, $scheduleIds)) {
                        continue;
                    }

                    if ($filterDate && $dateStr !== $filterDate) {
                        continue;
                    }

                    if (!empty($validated['department_id'])) {
                        if (!$a->department_id || (int) $a->department_id !== (int) $validated['department_id']) {
                            continue;
                        }
                    }

                    if (!empty($validated['employee_id']) && (int) $a->employee_id !== (int) $validated['employee_id']) {
                        continue;
                    }

                    $visit = $visits->first(function ($v) use ($a, $dateStr) {
                        return (int) $v->assignment_id === (int) $a->assignment_id
                            && $v->scheduled_date === $dateStr;
                    });

                    $date = Carbon::parse($dateStr);

                    $report[] = [
                        'date'                     => $date->format('j-M-y'),
                        'date_raw'                 => $dateStr,
                        'day'                      => $date->format('l'),
                        'employee_name'            => $a->employee_name ?? '-',
                        'designation'              => $a->designation_name ?? '-',
                        'department'               => $a->department_name ?? '-',
                        'department_id'            => $a->department_id ? (int) $a->department_id : null,
                        'employee_id'              => (int) $a->employee_id,
                        'distributor_name'         => $a->distributor_name ?? '-',
                        'scheduled_outlet_visit'   => trim(($a->outlet_name ?? '') . ' / ' . ($a->outlet_code ?? '')),
                        'check_in'                 => ($visit && $visit->check_in_at) ? Carbon::parse($visit->check_in_at)->format('h:i A') : '-',
                        'check_out'                => ($visit && $visit->check_out_at) ? Carbon::parse($visit->check_out_at)->format('h:i A') : '-',
                        'check_in_location'        => ($visit && $visit->check_in_location_name) ? $visit->check_in_location_name : '-',
                        'check_in_latitude'        => ($visit && $visit->check_in_latitude !== null) ? (float) $visit->check_in_latitude : null,
                        'check_in_longitude'       => ($visit && $visit->check_in_longitude !== null) ? (float) $visit->check_in_longitude : null,
                        'remarks'        => ($visit && $visit->remarks) ? $visit->remarks : '-',
                        'check_out_location'       => ($visit && $visit->check_out_location_name) ? $visit->check_out_location_name : '-',
                        'picture'                  => ($visit && $visit->image_path) ? url(Storage::disk('public')->url($visit->image_path)) : null,
                        'status'                   => $visit ? 'Visited' : 'Not Visited',
                    ];
                }
            }

            usort($report, function ($a, $b) {
                $c = strcmp($a['date_raw'], $b['date_raw']);
                if ($c !== 0) return $c;
                return strcmp($a['employee_name'], $b['employee_name']);
            });
        }

        if ($isDownload) {
            return $this->buildCsvDownload($report, $year, $month);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'report'      => $report,
                'employees'   => $allEmployees,
                'departments' => $allDepartments,
            ],
        ]);
    }

    private function buildCsvDownload(array $report, int $year, int $month)
    {
        $headers = [
            'Date',
            'Day',
            'Employee Name',
            'Designation',
            'Department',
            'Distributor Name',
            'Scheduled Outlet Visit',
            'Check In',
            'Check Out',
            'Check In Location',
            'Check Out Location',
            'Picture',
            'Status',
        ];

        $callback = function () use ($report, $headers) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);

            foreach ($report as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $row['day'],
                    $row['employee_name'],
                    $row['designation'],
                    $row['department'],
                    $row['distributor_name'],
                    $row['scheduled_outlet_visit'],
                    $row['check_in'],
                    $row['check_out'],
                    $row['check_in_location'],
                    $row['check_out_location'],
                    $row['picture'],
                    $row['status'],
                ]);
            }

            fclose($handle);
        };

        $filename = 'outlet_visit_report_' . $year . '_' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }
}
