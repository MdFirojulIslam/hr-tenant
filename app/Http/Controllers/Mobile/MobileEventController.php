<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\EventSetup;
use App\Support\EmployeeHierarchy;
use Illuminate\Support\Facades\Auth;

class MobileEventController extends Controller
{
    /**
     * Active events for the authenticated employee's department.
     * Eligibility (soft-delete, active window, department) is decided here.
     * Ended / not-yet-started events are omitted — no mobile event history.
     */
    public function index()
    {
        $employeeId = Auth::user()->EID;
        $departmentId = EmployeeHierarchy::departmentId($employeeId ? (int) $employeeId : null);
        $now = now();

        $rows = EventSetup::query()
            ->where('event_date_from', '<=', $now)
            ->where('event_date_to', '>=', $now)
            ->orderBy('event_date_from')
            ->orderBy('id')
            ->get()
            ->filter(fn (EventSetup $row) => $row->matchesDepartment($departmentId))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn (EventSetup $row) => $this->formatRow($row)),
        ]);
    }

    public function show($id)
    {
        $employeeId = Auth::user()->EID;
        $departmentId = EmployeeHierarchy::departmentId($employeeId ? (int) $employeeId : null);
        $now = now();

        $row = EventSetup::find($id);
        if (!$row || !$row->isActiveAt($now) || !$row->matchesDepartment($departmentId)) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRow($row),
        ]);
    }

    private function formatRow(EventSetup $row): array
    {
        $pictureUrl = null;
        if ($row->picture) {
            $pictureUrl = asset('storage/' . ltrim($row->picture, '/'));
        }

        return [
            'id' => $row->id,
            'event_header' => $row->event_header,
            'event_description' => $row->event_description,
            'event_type' => $row->event_type,
            'picture' => $row->picture,
            'picture_url' => $pictureUrl,
            'event_date_from' => optional($row->event_date_from)->toDateTimeString(),
            'event_date_to' => optional($row->event_date_to)->toDateTimeString(),
        ];
    }
}
