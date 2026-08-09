<?php

namespace App\Http\Controllers\Outlet_Visit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DMS\DMSMobileAppClient;

class OutletVisitSetupController extends Controller
{
    public function __construct(
        private readonly DMSMobileAppClient $dms,
    ) {}

    /**
     * HRIS towns for the setup dropdown (includes DMS_Town_Id mapping).
     */
    public function towns()
    {
        $towns = town::query()
            ->select('id', 'Name', 'DMS_Town_Id')
            ->orderBy('Name')
            ->get()
            ->map(fn($t) => [
                'town_id' => (int) $t->id,
                'town_name' => $t->Name,
                'distributor_id' => (int) $t->DMS_Town_Id,
            ]);

        return response()->json(['success' => true, 'data' => $towns]);
    }

    /**
     * Employees assigned to a town via town_belongs.
     */
    public function employees(Request $request)
    {
        $townId = (int) $request->query('town_id');
        if ($townId < 1) {
            return response()->json(['success' => false, 'message' => 'town_id is required.'], 422);
        }

        $employeeIds = town_belong::query()
            ->where('Town_Id', $townId)
            ->pluck('EID');

        $employees = employee::query()
            ->whereIn('id', $employeeIds)
            ->orderBy('Full_Name')
            ->get(['id', 'Full_Name', 'Employee_Id'])
            ->map(fn($e) => [
                'employee_id' => (int) $e->id,
                'full_name' => $e->Full_Name,
                'employee_code' => $e->Employee_Id,
            ]);

        return response()->json(['success' => true, 'data' => $employees]);
    }

    /**
     * Proxy to DMS outlets for the town's DMS_Town_Id (= distributor_id).
     */
    public function outlets(Request $request)
    {
        $townId = (int) $request->query('town_id');
        if ($townId < 1) {
            return response()->json(['success' => false, 'message' => 'town_id is required.'], 422);
        }

        $town = town::query()->find($townId);
        if (! $town || empty($town->DMS_Town_Id)) {
            return response()->json(['success' => false, 'message' => 'Town not found or missing DMS mapping.'], 404);
        }

        $distributorId = (int) $town->DMS_Town_Id;

        try {
            $result = $this->dms->listOutlets($distributorId, [
                'q' => $request->query('q'),
                'page' => (int) $request->query('page', 1),
                'per_page' => (int) $request->query('per_page', 25),
                'active' => $request->query('active', 1),
            ]);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'meta' => $result['meta'],
                'distributor_id' => $distributorId,
                'town_id' => $townId,
            ]);
        } catch (RuntimeException $e) {
            $status = (int) $e->getCode();
            if ($status < 400 || $status > 599) {
                $status = 502;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        }
    }

    public function assignments(Request $request)
    {
        $v = Validator::make($request->all(), [
            'town_id' => 'required|integer|min:1',
            'employee_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $rows = OutletVisitedAssignment::query()
            ->where('town_id', (int) $request->town_id)
            ->where('employee_id', (int) $request->employee_id)
            ->where('cycle_year', (int) $request->year)
            ->where('cycle_month', (int) $request->month)
            ->where('is_active', true)
            ->orderBy('activity_type')
            ->orderBy('week_no')
            ->orderBy('outlet_name')
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /**
     * Replace active assignments for employee+town+month with the submitted set.
     * Existing visited assignments that are removed are soft-deactivated (not hard-deleted).
     */
    public function saveAssignments(Request $request)
    {
        $v = Validator::make($request->all(), [
            'town_id' => 'required|integer|min:1',
            'employee_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'items' => 'present|array',
            'items.*.outlets_id' => 'required|integer|min:1',
            'items.*.outlet_code' => 'nullable|string|max:100',
            'items.*.outlet_name' => 'nullable|string|max:255',
            'items.*.activity_type' => 'required|in:visit,delivery',
            'items.*.week_no' => 'nullable|integer|min:0|max:4',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $town = town::query()->find((int) $request->town_id);
        if (! $town || empty($town->DMS_Town_Id)) {
            return response()->json(['success' => false, 'message' => 'Town not found or missing DMS mapping.'], 404);
        }

        $distributorId = (int) $town->DMS_Town_Id;
        $employeeId = (int) $request->employee_id;
        $year = (int) $request->year;
        $month = (int) $request->month;
        $actor = Auth::user()?->EID;

        $normalized = [];
        foreach ($request->input('items', []) as $item) {
            $activity = $item['activity_type'];
            $week = $activity === 'delivery' ? 0 : (int) ($item['week_no'] ?? 0);
            if ($activity === 'visit' && ! in_array($week, [1, 2, 3, 4], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visit assignments require week_no 1–4.',
                ], 422);
            }

            $key = implode(':', [
                $distributorId,
                (int) $item['outlets_id'],
                $activity,
                $week,
            ]);
            $normalized[$key] = [
                'employee_id' => $employeeId,
                'town_id' => (int) $town->id,
                'distributor_id' => $distributorId,
                'outlets_id' => (int) $item['outlets_id'],
                'outlet_code' => $item['outlet_code'] ?? null,
                'outlet_name' => $item['outlet_name'] ?? null,
                'cycle_year' => $year,
                'cycle_month' => $month,
                'activity_type' => $activity,
                'week_no' => $week,
                'is_active' => true,
                'updated_by' => $actor,
            ];
        }

        try {
            DB::transaction(function () use ($normalized, $employeeId, $town, $year, $month, $actor) {
                $existing = OutletVisitedAssignment::query()
                    ->where('employee_id', $employeeId)
                    ->where('town_id', (int) $town->id)
                    ->where('cycle_year', $year)
                    ->where('cycle_month', $month)
                    ->get();

                $keepKeys = array_keys($normalized);

                foreach ($existing as $row) {
                    $key = implode(':', [
                        $row->distributor_id,
                        $row->outlets_id,
                        $row->activity_type,
                        $row->week_no,
                    ]);

                    if (! in_array($key, $keepKeys, true)) {
                        if ($row->visit()->exists()) {
                            $row->is_active = false;
                            $row->updated_by = $actor;
                            $row->save();
                        } else {
                            $row->delete();
                        }
                    }
                }

                foreach ($normalized as $data) {
                    OutletVisitedAssignment::withTrashed()->updateOrCreate(
                        [
                            'employee_id' => $data['employee_id'],
                            'distributor_id' => $data['distributor_id'],
                            'outlets_id' => $data['outlets_id'],
                            'cycle_year' => $data['cycle_year'],
                            'cycle_month' => $data['cycle_month'],
                            'activity_type' => $data['activity_type'],
                            'week_no' => $data['week_no'],
                        ],
                        array_merge($data, [
                            'created_by' => $actor,
                            'deleted_at' => null,
                            'is_active' => true,
                        ])
                    );
                }
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to save assignments.',
            ], 500);
        }

        $rows = OutletVisitedAssignment::query()
            ->where('employee_id', $employeeId)
            ->where('town_id', (int) $town->id)
            ->where('cycle_year', $year)
            ->where('cycle_month', $month)
            ->where('is_active', true)
            ->orderBy('week_no')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Assignments saved.',
            'data' => $rows,
        ]);
    }
}
