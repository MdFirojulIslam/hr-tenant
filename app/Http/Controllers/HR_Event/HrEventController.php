<?php

namespace App\Http\Controllers\HR_Event;

use App\Http\Controllers\Controller;
use App\Models\HrActionEvent;
use App\Models\official;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;

class HrEventController extends Controller
{
    /**
     * POST /api/create_event
     *
     * Registers a new HR action / event against an employee.
     */
    public function storeEvent(Request $request)
    {
        $actor = $this->currentEmployeeId();

        if ($actor === null) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate($this->payloadRules());

        $mismatch = $this->employeeDepartmentMismatch($validated);

        if ($mismatch !== null) {
            return response()->json(['success' => false, 'message' => $mismatch], 422);
        }

        try {
            $event = HrActionEvent::create([
                'employee_id' => (int) $validated['employee_id'],
                'event_name' => $this->clean($validated['event_name']),
                'event_details' => $this->clean($validated['event_details']),
                'event_date' => $validated['event_date'],
                'new_event_date' => $validated['new_event_date'] ?? null,
                'remarks' => $this->clean($validated['remarks'] ?? null),
                'created_by' => $actor,
                'updated_by' => $actor,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to save the event. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'data' => $this->formatRow($event->load($this->withRelations())),
        ], 201);
    }

    /**
     * GET /api/show_event
     *
     * Paginated list of HR events, optionally filtered by department and employee.
     * Also returns the department list so the page can bootstrap in one request.
     */
    public function showEvent(Request $request)
    {
        $actor = $this->currentEmployeeId();

        if ($actor === null) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(5, min(100, $perPage));

        $departmentId = $request->input('department_id');
        $employeeId = $request->input('employee_id');

        $query = HrActionEvent::query()
            ->with($this->withRelations())
            ->orderByDesc('event_date')
            ->orderByDesc('id');

        if ($departmentId !== null && $departmentId !== '' && $departmentId !== '0') {
            $query->whereHas(
                'employee.official',
                fn(Builder $q) => $q->where('Department_Id', (int) $departmentId)
            );
        }

        if ($employeeId !== null && $employeeId !== '' && $employeeId !== '0') {
            $query->where('employee_id', (int) $employeeId);
        }

        if ($request->filled('event_name')) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->query('event_name')) . '%';
            $query->where('event_name', 'like', $term);
        }

        $paginator = $query->paginate($perPage)->withQueryString();
        $paginator->setCollection(
            $paginator->getCollection()->map(fn(HrActionEvent $event) => $this->formatRow($event))
        );

        return response()->json($paginator);
    }

    /**
     * PUT /api/update_event
     *
     * Updates an existing HR event. The record id travels in the payload because
     * the project route (/update_event) is a fixed action-style path.
     */
    public function updateEvent(Request $request)
    {
        $actor = $this->currentEmployeeId();

        if ($actor === null) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate(array_merge($this->payloadRules(), [
            'id' => ['required', 'integer'],
        ]));

        $event = HrActionEvent::find($validated['id']);

        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Event not found.'], 404);
        }

        $mismatch = $this->employeeDepartmentMismatch($validated);

        if ($mismatch !== null) {
            return response()->json(['success' => false, 'message' => $mismatch], 422);
        }

        try {
            $event->fill([
                'employee_id' => (int) $validated['employee_id'],
                'event_name' => $this->clean($validated['event_name']),
                'event_details' => $this->clean($validated['event_details']),
                'event_date' => $validated['event_date'],
                'new_event_date' => $validated['new_event_date'] ?? null,
                'remarks' => $this->clean($validated['remarks'] ?? null),
            ]);
            $event->updated_by = $actor;
            $event->save();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update the event. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data' => $this->formatRow($event->fresh($this->withRelations())),
        ]);
    }

    /**
     * Validation rules shared by store and update.
     *
     * @return array<string, array<int, string>>
     */
    private function payloadRules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id'),
            ],
            'event_name' => ['required', 'string', 'max:255'],
            'event_details' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'new_event_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Eager-loaded relations. Keeps the list query free of N+1 lookups.
     *
     * @return array<int, string>
     */
    private function withRelations(): array
    {
        return [
            'employee:id,Full_Name,Employee_Id',
            'employee.empImg:EID,img_url',
            'employee.official:EID,Department_Id,Designation_Id,Status',
            'employee.official.department:id,Name',
            'employee.official.designation:id,Name',
            'creator:id,Full_Name',
            'updater:id,Full_Name',
        ];
    }

    /**
     * Normalise a text field: trim it and collapse empty strings to null so
     * optional columns never hold whitespace-only junk.
     */
    private function clean(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return ($value === null || $value === '') ? null : $value;
    }

    /**
     * The authenticated user, expressed as the employees.id used by
     * created_by / updated_by across this project.
     */
    private function currentEmployeeId(): ?int
    {
        $user = Auth::user();

        if (!$user || !$user->EID) {
            return null;
        }

        return (int) $user->EID;
    }

    /**
     * Guard the department -> employee relationship: the employee must actually
     * belong to the department that was submitted alongside them.
     *
     * @param  array<string, mixed>  $validated
     */
    private function employeeDepartmentMismatch(array $validated): ?string
    {
        if (empty($validated['department_id'])) {
            return null;
        }

        $departmentId = (int) $validated['department_id'];

        $belongs = official::query()
            ->where('EID', (int) $validated['employee_id'])
            ->where('Department_Id', $departmentId)
            ->exists();

        if (!$belongs) {
            return 'The selected employee does not belong to the selected department.';
        }

        return null;
    }

    /**
     * Shape a single record for the frontend.
     *
     * @return array<string, mixed>
     */
    private function formatRow(HrActionEvent $event): array
    {
        $event->loadMissing($this->withRelations());

        $employee = $event->employee;
        $official = $employee?->official;
        $department = $official?->department;
        $designation = $official?->designation;

        return [
            'id' => (int) $event->id,
            'employee_id' => (int) $event->employee_id,
            'employee_name' => $employee?->Full_Name,
            'employee_code' => $employee?->Employee_Id,
            'department_id' => $official && $official->Department_Id ? (int) $official->Department_Id : null,
            'department_name' => $department?->Name,
            'designation_name' => $designation?->Name,
            'employee_image' => $employee?->empImg?->img_url,
            'event_name' => $event->event_name,
            'event_details' => $event->event_details,
            'event_date' => $event->event_date?->format('Y-m-d'),
            'new_event_date' => $event->new_event_date?->format('Y-m-d'),
            'remarks' => $event->remarks,
            'created_at' => optional($event->created_at)->toDateTimeString(),
            'created_by' => $event->created_by,
            'created_by_name' => $event->creator?->Full_Name,
            'updated_at' => optional($event->updated_at)->toDateTimeString(),
            'updated_by' => $event->updated_by,
            'updated_by_name' => $event->updater?->Full_Name,
        ];
    }
}
