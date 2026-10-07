<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskBoard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MobileTaskBoardController extends Controller
{
    /**
     * Tasks assigned to the authenticated employee.
     * Identity always comes from Auth::user()->EID — never from the client.
     */
    public function index(Request $request)
    {
        $employeeId = Auth::user()->EID;

        $query = Task::with([
            'assignees:task_id,Full_Name,Employee_Id',
            'employee:id,Full_Name,Employee_Id',
        ])->where('eid', $employeeId);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $scope = $request->query('scope');
        if ($scope === 'assigned') {
            $query->where('status', Task::STATUS_NOT_STARTED);
        } elseif ($scope === 'history') {
            $query->whereIn('status', [Task::STATUS_NOT_STARTED, Task::STATUS_IN_PROGRESS]);
        }

        if ($scope === 'history') {
            $query->orderByDesc('task_finished_at')->orderByDesc('id');
        } else {
            $query->orderByDesc('task_assigned_at')->orderByDesc('id');
        }

        $limit = min(max((int) $request->query('limit', 50), 1), 100);
        $rows = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn(Task $row) => $this->formatRow($row)),
        ]);
    }

    public function show($id)
    {
        $employeeId = Auth::user()->EID;

        $row = TaskBoard::with(['assigner:id,Full_Name,Employee_Id'])
            ->where('id', $id)
            ->where('eid', $employeeId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRow($row),
        ]);
    }

    /**
     * Employee updates own task: Assigned → Complete / Incomplete + remarks.
     * task_finished_at is set on the server.
     */
    public function update(Request $request, $id)
    {
        $employeeId = Auth::user()->EID;

        if ($request->has('eid') || $request->has('employee_id') || $request->has('task_finished_at')) {
            $request->request->remove('eid');
            $request->request->remove('employee_id');
            $request->request->remove('task_finished_at');
        }

        $row = TaskBoard::where('id', $id)->where('eid', $employeeId)->first();
        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.',
            ], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([TaskBoard::STATUS_COMPLETE, TaskBoard::STATUS_INCOMPLETE])],
            'remarks' => 'required|string|max:5000',
        ]);

        if (!TaskBoard::canEmployeeTransition($row->status, $validated['status'])) {
            return response()->json([
                'success' => false,
                'message' => 'This task can no longer be updated.',
            ], 422);
        }

        $row->status = $validated['status'];
        $row->remarks = $validated['remarks'];
        $row->task_finished_at = now();
        $row->updated_by = $employeeId;
        $row->save();

        $row->load(['assigner:id,Full_Name,Employee_Id']);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data' => $this->formatRow($row),
        ]);
    }

    private function formatRow(TaskBoard $row): array
    {
        return [
            'id' => $row->id,
            'eid' => $row->eid,
            'assigned_by' => $row->assigned_by,
            'assigned_by_name' => $row->assigner?->Full_Name,
            'task_name' => $row->task_name,
            'description' => $row->description,
            'status' => $row->status,
            'task_assigned_at' => optional($row->task_assigned_at)->toDateTimeString(),
            'task_finished_at' => optional($row->task_finished_at)->toDateTimeString(),
            'remarks' => $row->remarks,
            'updated_at' => optional($row->updated_at)->toDateTimeString(),
        ];
    }
}
