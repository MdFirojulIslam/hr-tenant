<?php

namespace App\Http\Controllers\Planner;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TasksAssignee;
use App\Models\TasksChecklists;
use App\Models\TasksResult;
use App\Models\employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TasksController extends Controller
{
    /**
     * Return a normalized employee payload for assignee display.
     */
    private function employeePayload($id)
    {
        if (!$id) {
            return null;
        }
        $emp = employee::select('id', 'Full_Name', 'Employee_Id')->find($id);
        if (!$emp) {
            return null;
        }
        return [
            'id' => $emp->id,
            'Full_Name' => $emp->Full_Name,
            'Employee_Id' => $emp->Employee_Id,
        ];
    }

    /**
     * Serialize a single task with its assignees, checklists and read states.
     */
    private function serializeTask(Task $task, $currentEid)
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
            if ((int) $a->assigned_to === (int) $currentEid) {
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
            'checklist_progress' => [
                'completed' => $completedCount,
                'total' => $totalCount,
            ],
            'my_read_status' => $myReadStatus,
        ];
    }

    private function taskAccessible($task, $eid, $isAdmin = false)
    {
        if ($isAdmin) {
            return true;
        }

        $isAssignee = $task->assignees->contains(function ($a) use ($eid) {
            return (int) $a->assigned_to === (int) $eid;
        });
        $isCreator = (int) $task->created_by === (int) $eid;

        if ($isAssignee || $isCreator) {
            return true;
        }

        return $task->privacy === 'Public';
    }

    /**
     * GET /planner/tasks  -> list tasks based on view/filters.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $eid = (int) $request->input('eid', $user->EID);
        $view = $request->input('view', 'assign'); // assign | my
        $privacy = $request->input('privacy');     // Private | Shared | all
        $status = $request->input('status');       // All | Not started | ...
        $employeeSearch = $request->input('employee');

        $currentEid = (int) $user->EID;
        $isAdmin = in_array((int) $user->role_id, [1, 2]);

        $query = Task::with(['assignees', 'checklists']);

        if ($view === 'my' || !$isAdmin) {
            $query->where(function ($q) use ($currentEid) {
                $q->whereHas('assignees', function ($qa) use ($currentEid) {
                    $qa->where('assigned_to', $currentEid);
                })->orWhere('created_by', $currentEid);
            });
        }

        if ($privacy && $privacy !== 'all') {
            $query->where('privacy', $privacy);
        }

        if ($status && $status !== 'All' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($employeeSearch) {
            $query->whereHas('assignees', function ($q) use ($employeeSearch) {
                $q->whereHas('employee', function ($qe) use ($employeeSearch) {
                    $qe->where('Full_Name', 'LIKE', "%{$employeeSearch}%")
                        ->orWhere('Employee_Id', 'LIKE', "%{$employeeSearch}%");
                });
            });
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        $data = $tasks->map(function ($task) use ($currentEid) {
            return $this->serializeTask($task, $currentEid);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /planner/tasks/{id} -> single task.
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();
        $currentEid = (int) $user->EID;
        $isAdmin = in_array((int) $user->role_id, [1, 2]);

        $task = Task::with(['assignees', 'checklists'])->find($id);

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        if (!$this->taskAccessible($task, $currentEid, $isAdmin)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $this->markRead($task, $currentEid, $user);

        return response()->json([
            'success' => true,
            'data' => $this->serializeTask($task, $currentEid),
        ]);
    }

    /**
     * Mark the current user's assignment row as read.
     */
    private function markRead(Task $task, $eid, $user)
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

    /**
     * POST /planner/tasks -> create task.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $eid = (int) $user->EID;

        $validated = $request->validate([
            'task_name' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'new_due_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:' . implode(',', Task::STATUSES),
            'repeat' => 'nullable|in:' . implode(',', Task::REPEATS),
            'bucket' => 'nullable|in:' . implode(',', Task::BUCKETS),
            'priority' => 'nullable|in:' . implode(',', Task::PRIORITIES),
            'privacy' => 'nullable|in:' . implode(',', Task::PRIVACIES),
            'notes' => 'nullable|string',
            'assignees' => 'nullable|array',
            'assignees.*' => 'integer',
            'checklists' => 'nullable|array',
            'checklists.*' => 'string',
        ]);

        try {
            DB::beginTransaction();

            $task = Task::create([
                'task_name' => $validated['task_name'] ?? '',
                'start_date' => $validated['start_date'] ?? now()->format('Y-m-d'),
                'due_date' => $validated['due_date'] ?? now()->addDays(7)->format('Y-m-d'),
                'new_due_date' => $validated['new_due_date'] ?? null,
                'status' => $validated['status'] ?? Task::STATUS_NOT_STARTED,
                'repeat' => $validated['repeat'] ?? 'Do not repeat',
                'bucket' => $validated['bucket'] ?? 'No bucket',
                'priority' => $validated['priority'] ?? 'Medium',
                'privacy' => $validated['privacy'] ?? 'Private',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $eid,
                'updated_by' => $eid,
            ]);

            if (!empty($validated['assignees'])) {
                $this->syncAssignees($task->id, $validated['assignees'], $eid);
            }

            if (!empty($validated['checklists'])) {
                foreach ($validated['checklists'] as $name) {
                    TasksChecklists::create([
                        'task_id' => $task->id,
                        'checklist_name' => $name,
                        'completed_status' => false,
                        'created_by' => $eid,
                        'updated_by' => $eid,
                    ]);
                }
            }

            DB::commit();

            $task->load(['assignees', 'checklists']);

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $this->serializeTask($task, $eid),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * PATCH /planner/tasks/{id} -> update task fields.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $eid = (int) $user->EID;

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $validated = $request->validate([
            'task_name' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'new_due_date' => 'nullable|date',
            'status' => 'nullable|in:' . implode(',', Task::STATUSES),
            'repeat' => 'nullable|in:' . implode(',', Task::REPEATS),
            'bucket' => 'nullable|in:' . implode(',', Task::BUCKETS),
            'priority' => 'nullable|in:' . implode(',', Task::PRIORITIES),
            'privacy' => 'nullable|in:' . implode(',', Task::PRIVACIES),
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $statusChanged = isset($validated['status']) && $validated['status'] !== $task->status;

            foreach ($validated as $field => $value) {
                $task->{$field} = $value;
            }
            $task->updated_by = $eid;
            $task->save();

            if ($statusChanged) {
                TasksResult::create([
                    'task_id' => $task->id,
                    'current_status' => $task->status,
                    'executed_date' => now()->format('Y-m-d'),
                    'executed_by' => $eid,
                    'updated_by' => $eid,
                ]);
            }

            DB::commit();

            $task->load(['assignees', 'checklists']);

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => $this->serializeTask($task, $eid),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /planner/tasks/{id} -> delete task.
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        $eid = (int) $user->EID;

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $isAdmin = in_array((int) $user->role_id, [1, 2]);
        $isCreator = (int) $task->created_by === (int) $eid;

        if (!$isAdmin && !$isCreator) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();

            TasksAssignee::where('task_id', $id)->delete();
            TasksChecklists::where('task_id', $id)->delete();
            TasksResult::where('task_id', $id)->delete();
            $task->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Sync assignees for a task using full replacement semantics.
     */
    private function syncAssignees($taskId, array $employees, $eid)
    {
        $existing = TasksAssignee::where('task_id', $taskId)->get();

        $incoming = collect($employees)->map(fn ($v) => (int) $v)->unique()->values();

        foreach ($existing as $row) {
            if (!$incoming->contains((int) $row->assigned_to)) {
                $row->delete();
            }
        }

        foreach ($incoming as $empId) {
            $exists = TasksAssignee::where('task_id', $taskId)
                ->where('assigned_to', $empId)
                ->exists();

            if (!$exists) {
                TasksAssignee::create([
                    'task_id' => $taskId,
                    'assigned_to' => $empId,
                    'assigned_by' => $eid,
                    'updated_by' => $eid,
                    'read_status' => false,
                ]);
            }
        }
    }

    /**
     * POST /planner/tasks/{id}/assignees -> set assignee list (full replacement).
     */
    public function storeAssignees(Request $request, $id)
    {
        $user = Auth::user();
        $eid = (int) $user->EID;

        $validated = $request->validate([
            'assignees' => 'required|array',
            'assignees.*' => 'integer',
        ]);

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        try {
            DB::beginTransaction();
            $this->syncAssignees($id, $validated['assignees'], $eid);
            DB::commit();

            $task->load('assignees');

            return response()->json([
                'success' => true,
                'message' => 'Assignees updated successfully',
                'data' => $task->assignees->map(function ($a) use ($eid) {
                    return [
                        'id' => $a->id,
                        'assigned_to' => $a->assigned_to,
                        'assignee' => $this->employeePayload($a->assigned_to),
                        'read_status' => (bool) $a->read_status,
                        'is_current_user' => $a->assigned_to === $eid,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /planner/tasks/{id}/assignees/{userId} -> remove single assignee.
     */
    public function destroyAssignee(Request $request, $id, $userId)
    {
        $user = Auth::user();
        $eid = (int) $user->EID;

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        try {
            DB::beginTransaction();

            TasksAssignee::where('task_id', $id)
                ->where('assigned_to', $userId)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assignee removed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /planner/tasks/{id}/checklists -> add a checklist item.
     */
    public function storeChecklist(Request $request, $id)
    {
        $user = Auth::user();
        $eid = (int) $user->EID;

        $validated = $request->validate([
            'checklist_name' => 'required|string',
        ]);

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $checklist = TasksChecklists::create([
            'task_id' => $id,
            'checklist_name' => $validated['checklist_name'],
            'completed_status' => false,
            'created_by' => $eid,
            'updated_by' => $eid,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checklist added successfully',
            'data' => [
                'id' => $checklist->id,
                'checklist_name' => $checklist->checklist_name,
                'completed_status' => false,
                'completed_by' => null,
                'created_by' => $eid,
            ],
        ], 201);
    }

    /**
     * PATCH /planner/tasks/{id}/checklists/{checklistId}
     */
    public function updateChecklist(Request $request, $id, $checklistId)
    {
        $user = Auth::user();
        $eid = (int) $user->EID;

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
                $checklist->completed_by = $eid;
            } else {
                $checklist->completed_by = null;
            }
        }

        $checklist->updated_by = $eid;
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

    /**
     * DELETE /planner/tasks/{id}/checklists/{checklistId}
     */
    public function destroyChecklist(Request $request, $id, $checklistId)
    {
        $checklist = TasksChecklists::where('task_id', $id)->find($checklistId);
        if (!$checklist) {
            return response()->json(['success' => false, 'message' => 'Checklist not found'], 404);
        }

        $checklist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checklist removed successfully',
        ]);
    }

    /**
     * POST /planner/tasks/{id}/read -> mark current user's assignment read.
     */
    public function markReadStatus(Request $request, $id)
    {
        $user = Auth::user();
        $eid = (int) $user->EID;

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $this->markRead($task, $eid, $user);

        return response()->json([
            'success' => true,
            'message' => 'Read status updated',
        ]);
    }

    /**
     * GET /planner/tasks/employees -> list employees for the assignee selector.
     * (Placed before /planner/tasks/{id} route to avoid conflict.)
     */
    public function employees(Request $request)
    {
        $search = $request->input('search');

        $query = employee::select('id', 'Full_Name', 'Employee_Id')->orderBy('Full_Name', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('Full_Name', 'LIKE', "%{$search}%")
                    ->orWhere('Employee_Id', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit(200)->get(),
        ]);
    }
}
