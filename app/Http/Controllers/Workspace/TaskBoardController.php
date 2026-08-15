<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\employee;
use App\Models\TaskBoard;
use App\Support\EmployeeHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskBoardController extends Controller
{
    /**
     * Employees the current user may assign tasks to.
     */
    public function subordinates()
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $ids = EmployeeHierarchy::directSubordinateIds((int) $user->EID);
        if ($ids === []) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $employees = employee::select('id', 'Full_Name', 'Employee_Id')
            ->whereIn('id', $ids)
            ->orderBy('Full_Name')
            ->get();

        return response()->json(['success' => true, 'data' => $employees]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $query = TaskBoard::with([
            'employee:id,Full_Name,Employee_Id',
            'assigner:id,Full_Name,Employee_Id',
            'creator:id,Full_Name,Employee_Id',
            'updater:id,Full_Name,Employee_Id',
        ])->orderByDesc('task_assigned_at')->orderByDesc('id');

        $subIds = EmployeeHierarchy::directSubordinateIds((int) $user->EID);
        $query->where(function ($q) use ($user, $subIds) {
            $q->where('assigned_by', $user->EID);
            if ($subIds !== []) {
                $q->orWhereIn('eid', $subIds);
            }
        });

        if ($request->filled('eid')) {
            $query->where('eid', (int) $request->query('eid'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('q')) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $request->query('q')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('task_name', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('task_assigned_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('task_assigned_at', '<=', $request->query('to'));
        }

        $rows = $query->limit(500)->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn (TaskBoard $row) => $this->formatRow($row)),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'eid' => 'required|integer|exists:employees,id',
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_assigned_at' => 'required|date',
        ]);

        $eid = (int) $validated['eid'];

        if (!EmployeeHierarchy::isDirectSubordinateOf((int) $user->EID, $eid)) {
            return response()->json([
                'success' => false,
                'message' => 'You may only assign tasks to employees under your supervision.',
            ], 403);
        }

        $actor = (int) $user->EID;

        $row = TaskBoard::create([
            'eid' => $eid,
            'assigned_by' => $actor,
            'task_name' => $validated['task_name'],
            'description' => $validated['description'] ?? null,
            'status' => TaskBoard::STATUS_ASSIGNED,
            'task_assigned_at' => $validated['task_assigned_at'],
            'task_finished_at' => null,
            'remarks' => null,
            'created_by' => $actor,
            'updated_by' => $actor,
        ]);

        $row->load(['employee:id,Full_Name,Employee_Id', 'assigner:id,Full_Name,Employee_Id', 'creator:id,Full_Name,Employee_Id', 'updater:id,Full_Name,Employee_Id']);

        return response()->json([
            'success' => true,
            'message' => 'Task assigned successfully.',
            'data' => $this->formatRow($row),
        ]);
    }

    private function formatRow(TaskBoard $row): array
    {
        return [
            'id' => $row->id,
            'eid' => $row->eid,
            'employee_name' => $row->employee?->Full_Name,
            'employee_code' => $row->employee?->Employee_Id,
            'assigned_by' => $row->assigned_by,
            'assigned_by_name' => $row->assigner?->Full_Name,
            'task_name' => $row->task_name,
            'description' => $row->description,
            'status' => $row->status,
            'task_assigned_at' => optional($row->task_assigned_at)->toDateTimeString(),
            'task_finished_at' => optional($row->task_finished_at)->toDateTimeString(),
            'remarks' => $row->remarks,
            'created_at' => optional($row->created_at)->toDateTimeString(),
            'created_by' => $row->created_by,
            'created_by_name' => $row->creator?->Full_Name,
            'updated_at' => optional($row->updated_at)->toDateTimeString(),
            'updated_by' => $row->updated_by,
            'updated_by_name' => $row->updater?->Full_Name,
        ];
    }
}
