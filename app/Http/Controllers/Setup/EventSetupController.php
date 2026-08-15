<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Models\EventSetup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'data' => $rows->map(fn (EventSetup $row) => $this->formatRow($row, $departments)),
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
        $ids = array_values(array_filter($ids, fn ($id) => $id > 0));

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
}
