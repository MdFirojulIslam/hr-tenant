<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\minutes_of_meeting;
use App\Models\town_belong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileMomController extends Controller
{
    /**
     * Assigned towns for the authenticated employee.
     */
    public function towns()
    {
        $employeeId = Auth::user()->EID;

        $towns = DB::table('towns')
            ->join('town_belongs', 'town_belongs.Town_Id', '=', 'towns.id')
            ->select('towns.id as town_id', 'towns.Name as town_name')
            ->where('town_belongs.EID', $employeeId)
            ->orderBy('towns.Name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $towns,
        ]);
    }

    /**
     * List MoM records for the authenticated employee (newest first).
     */
    public function index(Request $request)
    {
        $employeeId = Auth::user()->EID;
        $limit = min((int) $request->query('limit', 100), 200);

        $query = minutes_of_meeting::with('townRelation:id,Name')
            ->where('eid', $employeeId)
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        if ($request->filled('month')) {
            $month = (int) $request->query('month');
            $year = (int) $request->query('year', now()->year);
            $query->whereYear('date', $year)->whereMonth('date', $month);
        }

        if ($request->filled('town')) {
            $query->where('town', (int) $request->query('town'));
        }

        $rows = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn ($row) => $this->formatRow($row)),
        ]);
    }

    /**
     * Single MoM record — own entries only.
     */
    public function show($id)
    {
        $employeeId = Auth::user()->EID;

        $row = minutes_of_meeting::with('townRelation:id,Name')
            ->where('id', $id)
            ->where('eid', $employeeId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRow($row),
        ]);
    }

    /**
     * Create a MoM entry. Employee identity is always Auth::user()->EID.
     */
    public function store(Request $request)
    {
        $employeeId = Auth::user()->EID;

        // Never trust client-supplied eid.
        if ($request->has('eid') || $request->has('employee_id')) {
            $request->request->remove('eid');
            $request->request->remove('employee_id');
        }

        $validated = $request->validate([
            'str_time' => 'required|string|max:20',
            'end_time' => 'required|string|max:20',
            'town' => 'required|integer',
            'date' => 'required|date',
            'meeting_with' => 'required|string|max:255',
            'mom' => 'required|string',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:512',
        ]);

        $strTime = $this->normalizeTime($validated['str_time']);
        $endTime = $this->normalizeTime($validated['end_time']);

        if ($strTime === null || $endTime === null) {
            throw ValidationException::withMessages([
                'str_time' => ['Invalid start or end time. Use HH:mm (24h) or h:mm AM/PM.'],
            ]);
        }

        $townId = (int) $validated['town'];
        if (!$this->employeeHasTown($employeeId, $townId)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected town is not assigned to you.',
            ], 422);
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $this->storeAttachment($request->file('file'));
        }

        $row = minutes_of_meeting::create([
            'eid' => $employeeId,
            'str_time' => $strTime,
            'end_time' => $endTime,
            'town' => $townId,
            'date' => $validated['date'],
            'meeting_with' => $validated['meeting_with'],
            'mom' => $validated['mom'],
            'file' => $filePath,
        ]);

        $row->load('townRelation:id,Name');

        return response()->json([
            'success' => true,
            'message' => 'Minutes of Meeting saved.',
            'data' => $this->formatRow($row),
        ], 201);
    }

    /**
     * Whether [townId] is assigned to [employeeId] via town_belongs.
     */
    public function employeeHasTown(int $employeeId, int $townId): bool
    {
        return town_belong::where('EID', $employeeId)
            ->where('Town_Id', $townId)
            ->exists();
    }

    /**
     * Normalize UI / API time strings to H:i:s for MySQL TIME columns.
     */
    public function normalizeTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Already 24h HH:mm or HH:mm:ss
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $value, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            $sec = isset($m[3]) ? (int) $m[3] : 0;

            return sprintf('%02d:%02d:%02d', $h, $min, $sec);
        }

        // 12h: 10:30 AM / 02:45 PM
        if (preg_match('/^(\d{1,2}):([0-5]\d)\s*(AM|PM)$/i', $value, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            $period = strtoupper($m[3]);

            if ($h < 1 || $h > 12) {
                return null;
            }

            if ($period === 'AM') {
                if ($h === 12) {
                    $h = 0;
                }
            } else {
                if ($h !== 12) {
                    $h += 12;
                }
            }

            return sprintf('%02d:%02d:00', $h, $min);
        }

        return null;
    }

    private function storeAttachment($file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
            $ext = 'bin';
        }
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $path = 'minutes_of_meetings/' . date('Y/m') . '/' . Str::uuid() . '.' . $ext;
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    private function formatRow(minutes_of_meeting $row): array
    {
        $fileUrl = null;
        if ($row->file) {
            $fileUrl = asset('storage/' . ltrim($row->file, '/'));
        }

        $str = $this->formatTimeDisplay($row->str_time);
        $end = $this->formatTimeDisplay($row->end_time);

        return [
            'id' => $row->id,
            'eid' => $row->eid,
            'str_time' => $this->toApiHm($row->str_time),
            'end_time' => $this->toApiHm($row->end_time),
            'str_time_display' => $str,
            'end_time_display' => $end,
            'town' => $row->town,
            'town_id' => $row->town,
            'town_name' => $row->townRelation?->Name,
            'date' => $row->date?->toDateString(),
            'meeting_with' => $row->meeting_with,
            'mom' => $row->mom,
            'file' => $row->file,
            'file_url' => $fileUrl,
            'created_at' => $row->created_at?->toDateTimeString(),
            'updated_at' => $row->updated_at?->toDateTimeString(),
        ];
    }

    private function toApiHm($time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }
        $s = (string) $time;
        if (preg_match('/^(\d{1,2}):(\d{2})/', $s, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return $s;
    }

    private function formatTimeDisplay($time): ?string
    {
        $hm = $this->toApiHm($time);
        if ($hm === null) {
            return null;
        }
        [$h, $min] = array_map('intval', explode(':', $hm));
        $period = $h >= 12 ? 'PM' : 'AM';
        $h12 = $h % 12;
        if ($h12 === 0) {
            $h12 = 12;
        }

        return sprintf('%02d:%02d %s', $h12, $min, $period);
    }
}
