<?php

namespace App\Http\Controllers\DMS;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\employee;
use App\Models\OutletWeekSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
            ->join('outlet_week_schedules as ows', 'ows.id', '=', 'ova.outlet_week_schedules_id')
            ->join('employees as e', 'e.id', '=', 'ova.employee_id')
            ->where('ova.is_active', true)
            ->select(
                'ova.distributor_id',
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
                    'employee_name' => $row->employee_name,
                    'weeks' => [],
                    'outlets' => [],
                ];
            }

            $groups[$key]['weeks'][(int) $row->week_no] = true;
            $groups[$key]['outlets'][(string) $row->outlet_code] = true;
        }

        $data = array_map(function ($group) {
            return [
                'distributor_id' => $group['distributor_id'],
                'employee_id' => $group['employee_id'],
                'employee_name' => $group['employee_name'],
                'completed_weeks' => count($group['weeks']),
                'total_weeks' => 4,
                'unique_outlet_count' => count($group['outlets']),
            ];
        }, array_values($groups));

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
}
