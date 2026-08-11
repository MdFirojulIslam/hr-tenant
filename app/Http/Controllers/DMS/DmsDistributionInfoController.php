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
        $seenCodes = [];

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

            foreach ($outlets as $outlet) {
                $code = trim((string) $outlet['outlet_code']);

                if (isset($seenCodes[$code])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Outlet "' . $code . '" has been entered more than once. Every outlet must be unique across all weeks.',
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
        }

        if (count($rows) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No outlet data found to save.',
            ], 422);
        }

        $alreadyAssigned = DB::table('outlet_visit_assignments')
            ->where('distributor_id', $distributorId)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->whereIn('outlet_code', array_keys($seenCodes))
            ->pluck('outlet_code')
            ->unique()
            ->values()
            ->all();

        if (count($alreadyAssigned) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'The following outlet(s) are already assigned and cannot be assigned again: ' . implode(', ', $alreadyAssigned) . '.',
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
}
