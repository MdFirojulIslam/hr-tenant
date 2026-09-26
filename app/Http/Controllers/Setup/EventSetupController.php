<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Models\employee;
use App\Models\EventSetup;
use App\Models\TaskBoard;
use App\Models\TaskBoardAssignee;
use App\Support\EmployeeHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EventSetupController extends Controller
{
    public function index()
    {
        $rows = EventSetup::with(['creator:id,Full_Name,Employee_Id', 'updater:id,Full_Name,Employee_Id'])
            ->orderByDesc('event_date_from')
            ->orderByDesc('id')
            ->get();

        $departments = department::orderBy('Name')->get(['id', 'Name']);

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn(EventSetup $row) => $this->formatRow($row, $departments)),
            'departments' => $departments,
            'event_types' => EventSetup::TYPES,
        ]);
    }







    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $this->validated($request);
        $departments = $this->resolveDepartments($request);
        if ($departments === null) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least one department, or Select All Departments.',
            ], 422);
        }

        $picture = $this->storePicture($request);

        $actor = (int) $user->EID;
        $row = EventSetup::create([
            'event_header' => $validated['event_header'],
            'event_description' => $validated['event_description'] ?? null,
            'event_type' => $validated['event_type'],
            'picture' => $picture,
            'event_date_from' => $validated['event_date_from'],
            'event_date_to' => $validated['event_date_to'],
            'for_departments' => $departments,
            'created_by' => $actor,
            'updated_by' => $actor,
        ]);

        try {
            \App\Support\MobileNotificationDispatcher::notifyEventCreated($row);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'data' => $this->formatRow($row->load(['creator:id,Full_Name', 'updater:id,Full_Name'])),
        ]);
    }











    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $row = EventSetup::find($id);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Event not found.'], 404);
        }

        $validated = $this->validated($request);
        $departments = $this->resolveDepartments($request);
        if ($departments === null) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least one department, or Select All Departments.',
            ], 422);
        }

        if ($request->hasFile('picture')) {
            $this->deletePicture($row->picture);
            $row->picture = $this->storePicture($request);
        }

        $row->event_header = $validated['event_header'];
        $row->event_description = $validated['event_description'] ?? null;
        $row->event_type = $validated['event_type'];
        $row->event_date_from = $validated['event_date_from'];
        $row->event_date_to = $validated['event_date_to'];
        $row->for_departments = $departments;
        $row->updated_by = (int) $user->EID;
        $row->save();

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data' => $this->formatRow($row->fresh(['creator:id,Full_Name', 'updater:id,Full_Name'])),
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $row = EventSetup::find($id);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Event not found.'], 404);
        }

        $row->deleted_by = (int) $user->EID;
        $row->save();
        $row->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'event_header' => 'required|string|max:255',
            'event_description' => 'nullable|string',
            'event_type' => ['required', 'string', Rule::in(EventSetup::TYPES)],
            'event_date_from' => 'required|date',
            'event_date_to' => 'required|date|after_or_equal:event_date_from',
            'picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:512',
            'for_all_departments' => 'nullable',
            'for_departments' => 'nullable',
        ]);
    }

    /**
     * @return array<int|string>|null
     */
    private function resolveDepartments(Request $request): ?array
    {
        $all = $request->boolean('for_all_departments')
            || $request->input('for_all_departments') === '1'
            || $request->input('for_all_departments') === 'true';

        if ($all) {
            return EventSetup::ALL_DEPARTMENTS;
        }

        $raw = $request->input('for_departments', []);
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : array_filter(explode(',', $raw));
        }

        if (!is_array($raw)) {
            return null;
        }

        foreach ($raw as $item) {
            if (is_string($item) && strtolower($item) === 'all') {
                return EventSetup::ALL_DEPARTMENTS;
            }
        }

        $ids = array_values(array_unique(array_map('intval', $raw)));
        $ids = array_values(array_filter($ids, fn($id) => $id > 0));

        return $ids === [] ? null : $ids;
    }

    private function storePicture(Request $request): ?string
    {
        if (!$request->hasFile('picture')) {
            return null;
        }

        $file = $request->file('picture');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $path = 'event_img/' . date('Y/m') . '/' . Str::uuid() . '.' . $ext;
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    private function deletePicture(?string $path): void
    {
        if (!$path) {
            return;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    private function formatRow(EventSetup $row, $departments = null): array
    {
        $departments = $departments ?? department::orderBy('Name')->get(['id', 'Name']);
        $map = $departments->keyBy('id');

        $deptIds = $row->for_departments ?? [];
        $all = $row->isForAllDepartments();
        $names = [];
        if (!$all) {
            foreach ($deptIds as $id) {
                $dept = $map->get((int) $id);
                if ($dept) {
                    $names[] = $dept->Name;
                }
            }
        }

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
            'for_departments' => $all ? EventSetup::ALL_DEPARTMENTS : array_map('intval', $deptIds),
            'for_all_departments' => $all,
            'department_names' => $all ? ['All Departments'] : $names,
            'created_at' => optional($row->created_at)->toDateTimeString(),
            'created_by' => $row->created_by,
            'created_by_name' => $row->creator?->Full_Name,
            'updated_at' => optional($row->updated_at)->toDateTimeString(),
            'updated_by' => $row->updated_by,
            'updated_by_name' => $row->updater?->Full_Name,
        ];
    }

    // ------------------------------------------------------------------
    // Task Board
    // ------------------------------------------------------------------

    public function taskBoardEmployees()
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Administrators (role_id == 1) may assign tasks to any employee.
        if ($this->isTaskBoardAdmin($user)) {
            $employees = employee::select('id', 'Full_Name', 'Employee_Id')
                ->orderBy('Full_Name')
                ->get();

            return response()->json(['success' => true, 'data' => $employees]);
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

    public function taskBoardList(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $actor = (int) $user->EID;
        $subIds = EmployeeHierarchy::directSubordinateIds($actor);

        $query = TaskBoard::with([
            'assignees.employee:id,Full_Name,Employee_Id',
            'employee:id,Full_Name,Employee_Id',
            'assigner:id,Full_Name,Employee_Id',
            'updater:id,Full_Name,Employee_Id',
        ])->orderByDesc('task_assigned_at')->orderByDesc('id');

        $query->where(function ($q) use ($actor, $subIds) {
            $q->where('assigned_by', $actor);
            if ($subIds !== []) {
                $q->orWhereHas('assignees', fn($a) => $a->whereIn('employee_id', $subIds));
            }
        });

        if ($request->filled('eid')) {
            $eid = (int) $request->query('eid');
            $query->where(function ($q) use ($eid) {
                $q->where('assigned_to', $eid)
                    ->orWhereHas('assignees', fn($a) => $a->where('employee_id', $eid));
            });
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
            'data' => $rows->map(fn(TaskBoard $row) => $this->formatTaskBoardRow($row)),
        ]);
    }

    public function taskBoardStore(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $this->validateTaskBoard($request);
        $assigneeIds = $this->resolveAssigneeIds($validated['assigned_to']);

        if (!$this->isTaskBoardAdmin($user) && !$this->assertAssigneesUnderSupervision((int) $user->EID, $assigneeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'You may only assign tasks to employees under your supervision.',
            ], 403);
        }

        $actor = (int) $user->EID;
        $status = $validated['status'] ?? TaskBoard::STATUS_ASSIGNED;

        $row = DB::transaction(function () use ($assigneeIds, $actor, $validated, $status) {
            $task = TaskBoard::create([
                'assigned_to' => $assigneeIds[0],
                'assigned_by' => $actor,
                'task_name' => $validated['task_name'],
                'description' => $validated['description'] ?? null,
                'status' => $status,
                'task_assigned_at' => $validated['task_assigned_at'],
                'task_finished_at' => $status === TaskBoard::STATUS_COMPLETE ? now() : null,
                'remarks' => $validated['remarks'] ?? null,
                'updated_by' => $actor,
            ]);

            foreach ($assigneeIds as $employeeId) {
                TaskBoardAssignee::create([
                    'task_board_id' => $task->id,
                    'employee_id' => $employeeId,
                ]);
            }

            return $task;
        });

        $row->load([
            'assignees.employee:id,Full_Name,Employee_Id',
            'employee:id,Full_Name,Employee_Id',
            'assigner:id,Full_Name,Employee_Id',
            'updater:id,Full_Name,Employee_Id',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task assigned successfully.',
            'data' => $this->formatTaskBoardRow($row),
        ]);
    }

    public function taskBoardUpdate(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $row = TaskBoard::find($id);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $actor = (int) $user->EID;
        if ((int) $row->assigned_by !== $actor) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update tasks that you assigned.',
            ], 403);
        }

        $validated = $this->validateTaskBoard($request);
        $assigneeIds = $this->resolveAssigneeIds($validated['assigned_to']);

        if (!$this->isTaskBoardAdmin($user) && !$this->assertAssigneesUnderSupervision($actor, $assigneeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'You may only assign tasks to employees under your supervision.',
            ], 403);
        }

        $status = $validated['status'] ?? $row->status;

        $row = DB::transaction(function () use ($row, $assigneeIds, $actor, $validated, $status) {
            $row->assigned_to = $assigneeIds[0];
            $row->task_name = $validated['task_name'];
            $row->description = $validated['description'] ?? null;
            $row->status = $status;
            $row->task_assigned_at = $validated['task_assigned_at'];
            $row->remarks = $validated['remarks'] ?? null;
            $row->updated_by = $actor;

            if ($status === TaskBoard::STATUS_COMPLETE && empty($row->task_finished_at)) {
                $row->task_finished_at = now();
            } elseif (!empty($validated['task_finished_at'])) {
                $row->task_finished_at = $validated['task_finished_at'];
            }

            $row->save();

            $row->assignees()->whereNotIn('employee_id', $assigneeIds)->delete();

            foreach ($assigneeIds as $employeeId) {
                $row->assignees()->firstOrCreate(['employee_id' => $employeeId]);
            }

            return $row;
        });

        $row->load([
            'assignees.employee:id,Full_Name,Employee_Id',
            'employee:id,Full_Name,Employee_Id',
            'assigner:id,Full_Name,Employee_Id',
            'updater:id,Full_Name,Employee_Id',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data' => $this->formatTaskBoardRow($row),
        ]);
    }

    public function taskBoardDelete($id)
    {
        $user = Auth::user();
        if (!$user || !$user->EID) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $row = TaskBoard::find($id);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        if ((int) $row->assigned_by !== (int) $user->EID) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete tasks that you assigned.',
            ], 403);
        }

        DB::transaction(function () use ($row) {
            $row->assignees()->delete();
            $row->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }

    private function validateTaskBoard(Request $request): array
    {
        return $request->validate([
            'task_name' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'required|array|min:1',
            'assigned_to.*' => 'required|integer|distinct|exists:employees,id',
            'status' => ['sometimes', 'string', Rule::in(TaskBoard::STATUSES)],
            'task_assigned_at' => 'required|date',
            'task_finished_at' => 'nullable|date',
            'remarks' => 'nullable|string|max:5000',
        ]);
    }

    private function resolveAssigneeIds($assignedTo): array
    {
        $ids = [];
        foreach ($assignedTo as $id) {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    private function isTaskBoardAdmin($user): bool
    {
        return (int) ($user->role_id ?? 0) === 1;
    }

    private function assertAssigneesUnderSupervision(int $actorEid, array $assigneeIds): bool
    {
        if ($assigneeIds === []) {
            return false;
        }

        $subIds = EmployeeHierarchy::directSubordinateIds($actorEid);
        if ($subIds === []) {
            return false;
        }

        foreach ($assigneeIds as $id) {
            if (!in_array($id, $subIds, true)) {
                return false;
            }
        }

        return true;
    }

    private function formatTaskBoardRow(TaskBoard $row): array
    {
        $assignees = $row->assignees
            ->filter(fn(TaskBoardAssignee $a) => $a->employee)
            ->values();

        return [
            'id' => $row->id,
            'assigned_to' => $row->assigned_to,
            'employee_name' => $row->employee?->Full_Name,
            'employee_code' => $row->employee?->Employee_Id,
            'assignees' => $assignees->map(fn(TaskBoardAssignee $a) => [
                'id' => $a->employee->id,
                'Full_Name' => $a->employee->Full_Name,
                'Employee_Id' => $a->employee->Employee_Id,
            ])->all(),
            'assignee_names' => $assignees->map(fn(TaskBoardAssignee $a) => $a->employee->Full_Name)->all(),
            'assigned_by' => $row->assigned_by,
            'assigned_by_name' => $row->assigner?->Full_Name,
            'task_name' => $row->task_name,
            'description' => $row->description,
            'status' => $row->status,
            'task_assigned_at' => optional($row->task_assigned_at)->toDateTimeString(),
            'task_finished_at' => optional($row->task_finished_at)->toDateTimeString(),
            'remarks' => $row->remarks,
            'created_at' => optional($row->created_at)->toDateTimeString(),
            'updated_at' => optional($row->updated_at)->toDateTimeString(),
            'updated_by' => $row->updated_by,
            'updated_by_name' => $row->updater?->Full_Name,
        ];
    }
}
