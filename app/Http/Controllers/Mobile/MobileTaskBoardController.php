<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TasksAssignee;
use App\Models\TasksChecklists;
use App\Models\TasksResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileTaskBoardController extends Controller
{
    /**
     * Tasks assigned to the authenticated employee.
     * Identity always comes from Auth::user()->EID — never from the client.
     */
    public function index(Request $request)
    {
        $currentEid = (int) Auth::user()->EID;

        $query = Task::with(['assignees', 'checklists'])
            ->where(function ($q) use ($currentEid) {
                $q->whereHas('assignees', function ($qa) use ($currentEid) {
                    $qa->where('assigned_to', $currentEid);
                })->orWhere('created_by', $currentEid);
            });

        if ($request->filled('status') && $request->query('status') !== 'All') {
            $query->where('status', $request->query('status'));
        }

        $scope = $request->query('scope');
        if ($scope === 'assigned') {
            $query->where('status', Task::STATUS_NOT_STARTED);
        } elseif ($scope === 'history') {
            $query->whereIn('status', [Task::STATUS_NOT_STARTED, Task::STATUS_IN_PROGRESS]);
        }

        if ($request->filled('bucket') && $request->query('bucket') !== 'All') {
            $query->where('bucket', $request->query('bucket'));
        }

        if ($request->filled('priority') && $request->query('priority') !== 'All') {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->filled('privacy') && $request->query('privacy') !== 'All') {
            $query->where('privacy', $request->query('privacy'));
        }

        if ($request->filled('q')) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $request->query('q')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('task_name', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        if ($scope === 'history') {
            $query->orderByDesc('updated_at')->orderByDesc('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $limit = min(max((int) $request->query('limit', 50), 1), 100);
        $rows = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn(Task $row) => $this->serializeTask($row, $currentEid)),
        ]);
    }

    public function show($id)
    {
        $currentEid = (int) Auth::user()->EID;

        $task = Task::with(['assignees', 'checklists', 'results'])->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.',
            ], 404);
        }

        if (!$this->taskAccessible($task, $currentEid)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this task.',
            ], 403);
        }

        $this->markRead($task, $currentEid);

        return response()->json([
            'success' => true,
            'data' => $this->serializeTask($task, $currentEid),
        ]);
    }

    /**
     * Employee updates own task fields.
     * Only allows fields the employee is permitted to edit.
     */
    public function update(Request $request, $id)
    {
        $currentEid = (int) Auth::user()->EID;

        $task = Task::find($id);
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.',
            ], 404);
        }

        if (!$this->taskAccessible($task, $currentEid)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this task.',
            ], 403);
        }

        $isAssignee = $task->assignees->contains(function ($a) use ($currentEid) {
            return (int) $a->assigned_to === $currentEid;
        });
        $isCreator = (int) $task->created_by === $currentEid;

        $allowedFields = ['status', 'bucket', 'priority', 'notes', 'due_date', 'new_due_date', 'repeat', 'privacy'];
        $creatorOnlyFields = ['task_name', 'start_date', 'privacy'];

        $validated = $request->validate([
            'task_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'new_due_date' => 'nullable|date',
            'status' => ['nullable', 'string', Rule::in(Task::STATUSES)],
            'repeat' => ['nullable', 'string', Rule::in(Task::REPEATS)],
            'bucket' => ['nullable', 'string', Rule::in(Task::BUCKETS)],
            'priority' => ['nullable', 'string', Rule::in(Task::PRIORITIES)],
            'privacy' => ['nullable', 'string', Rule::in(Task::PRIVACIES)],
            'notes' => 'nullable|string',
        ]);

        $filtered = [];
        foreach ($validated as $field => $value) {
            if ($value === null) {
                continue;
            }
            if (in_array($field, $creatorOnlyFields, true) && !$isCreator) {
                continue;
            }
            if (!in_array($field, $allowedFields, true) && !$isCreator) {
                continue;
            }
            $filtered[$field] = $value;
        }

        if (empty($filtered)) {
            return response()->json([
                'success' => false,
                'message' => 'No updatable fields provided.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $statusChanged = isset($filtered['status']) && $filtered['status'] !== $task->status;

            foreach ($filtered as $field => $value) {
                $task->{$field} = $value;
            }
            $task->updated_by = $currentEid;
            $task->save();

            if ($statusChanged) {
                TasksResult::create([
                    'task_id' => $task->id,
                    'current_status' => $task->status,
                    'executed_date' => now()->format('Y-m-d'),
                    'executed_by' => $currentEid,
                    'updated_by' => $currentEid,
                ]);
            }

            DB::commit();

            $task->load(['assignees', 'checklists', 'results']);

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully.',
                'data' => $this->serializeTask($task, $currentEid),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function employees(Request $request)
    {
        $search = $request->input('search');

        $query = \App\Models\employee::select('id', 'Full_Name', 'Employee_Id')->orderBy('Full_Name', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('Full_Name', 'LIKE', "%{$search}%")
                    ->orWhere('Employee_Id', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function updateChecklist(Request $request, $id, $checklistId)
    {
        $currentEid = (int) Auth::user()->EID;

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        if (!$this->taskAccessible($task, $currentEid)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this task.'], 403);
        }

        $validated = $request->validate([
            'checklist_name' => 'nullable|string',
            'completed_status' => 'nullable|boolean',
        ]);

        $checklist = TasksChecklists::where('task_id', $id)->find($checklistId);
        if (!$checklist) {
            return response()->json(['success' => false, 'message' => 'Checklist not found'], 404);
        }

        if (array_key_exists('checklist_name', $validated)) {
            $checklist->checklist_name = $validated['checklist_name'];
        }

        if (array_key_exists('completed_status', $validated)) {
            $checklist->completed_status = $validated['completed_status'] ? true : false;
            if ($checklist->completed_status) {
                $checklist->completed_by = $currentEid;
            } else {
                $checklist->completed_by = null;
            }
        }

        $checklist->updated_by = $currentEid;
        $checklist->save();

        return response()->json([
            'success' => true,
            'message' => 'Checklist updated successfully',
            'data' => [
                'id' => $checklist->id,
                'checklist_name' => $checklist->checklist_name,
                'completed_status' => (bool) $checklist->completed_status,
                'completed_by' => $checklist->completed_by,
                'created_by' => $checklist->created_by,
            ],
        ]);
    }

    public function markReadStatus(Request $request, $id)
    {
        $currentEid = (int) Auth::user()->EID;

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $this->markRead($task, $currentEid);

        return response()->json([
            'success' => true,
            'message' => 'Read status updated',
        ]);
    }

    private function taskAccessible(Task $task, int $eid): bool
    {
        $isAssignee = $task->assignees->contains(function ($a) use ($eid) {
            return (int) $a->assigned_to === $eid;
        });
        $isCreator = (int) $task->created_by === $eid;

        if ($isAssignee || $isCreator) {
            return true;
        }

        return $task->privacy === 'Public';
    }

    private function markRead(Task $task, int $eid)
    {
        $assignee = TasksAssignee::where('task_id', $task->id)
            ->where('assigned_to', $eid)
            ->first();

        if ($assignee && !$assignee->read_status) {
            $assignee->read_status = true;
            $assignee->updated_by = $eid;
            $assignee->save();
        }
    }

    private function employeePayload($id)
    {
        if (!$id) {
            return null;
        }
        $emp = \App\Models\employee::select('id', 'Full_Name', 'Employee_Id')->find($id);
        if (!$emp) {
            return null;
        }
        return [
            'id' => $emp->id,
            'Full_Name' => $emp->Full_Name,
            'Employee_Id' => $emp->Employee_Id,
        ];
    }

    private function serializeTask(Task $task, int $currentEid): array
    {
        $assignees = $task->assignees->map(function ($a) use ($currentEid) {
            $emp = $this->employeePayload($a->assigned_to);
            return [
                'id' => $a->id,
                'assigned_to' => $a->assigned_to,
                'assigned_by' => $a->assigned_by,
                'employee' => $emp,
                'read_status' => (bool) $a->read_status,
                'is_current_user' => $a->assigned_to === $currentEid,
            ];
        });

        $myReadStatus = false;
        foreach ($task->assignees as $a) {
            if ((int) $a->assigned_to === $currentEid) {
                $myReadStatus = (bool) $a->read_status;
            }
        }

        $checklists = $task->checklists->map(function ($c) {
            return [
                'id' => $c->id,
                'checklist_name' => $c->checklist_name,
                'completed_status' => (bool) $c->completed_status,
                'completed_by' => $c->completed_by,
                'created_by' => $c->created_by,
            ];
        });

        $completedCount = $checklists->where('completed_status', true)->count();
        $totalCount = $checklists->count();

        $results = $task->results->map(function ($r) {
            return [
                'id' => $r->id,
                'current_status' => $r->current_status,
                'executed_date' => $r->executed_date ? $r->executed_date->format('Y-m-d') : null,
                'executed_by' => $r->executed_by,
                'updated_by' => $r->updated_by,
            ];
        });

        return [
            'id' => $task->id,
            'task_name' => $task->task_name,
            'start_date' => $task->start_date ? $task->start_date->format('Y-m-d') : null,
            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
            'new_due_date' => $task->new_due_date ? $task->new_due_date->format('Y-m-d') : null,
            'status' => $task->status,
            'repeat' => $task->repeat,
            'bucket' => $task->bucket,
            'priority' => $task->priority,
            'privacy' => $task->privacy,
            'notes' => $task->notes,
            'created_by' => $task->created_by,
            'updated_by' => $task->updated_by,
            'created_by_info' => $this->employeePayload($task->created_by),
            'assignees' => $assignees,
            'checklists' => $checklists,
            'results' => $results,
            'checklist_progress' => [
                'completed' => $completedCount,
                'total' => $totalCount,
            ],
            'my_read_status' => $myReadStatus,
        ];
    }
}
