<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\RandomOutletVisit;
use App\Support\AppTimezone;
use App\Support\ReverseGeocoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileRandomOutletVisitController extends Controller
{
    public function towns(Request $request)
    {
        $employeeId = $request->user()->EID;
        $assigned = DB::table('towns')
            ->join('town_belongs', 'town_belongs.Town_Id', '=', 'towns.id')
            ->select('towns.id as town_id', 'towns.Name as town_name')
            ->where('town_belongs.EID', $employeeId)
            ->orderBy('towns.Name')
            ->get();

        if ($assigned->isNotEmpty()) {
            return response()->json(['success' => true, 'data' => $assigned]);
        }

        $all = DB::table('towns')
            ->select('towns.id as town_id', 'towns.Name as town_name')
            ->orderBy('towns.Name')
            ->get();

        return response()->json(['success' => true, 'data' => $all]);
    }

    public function store(Request $request)
    {
        $eid = $request->user()->EID;
        if (! $eid) {
            return response()->json(['success' => false, 'message' => 'Employee not linked.'], 403);
        }

        $validated = $request->validate([
            'town' => 'required|string|max:255',
            'outlet_name' => 'required|string|max:255',
            'image' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $imagePath = $this->storeImage($validated['image']);
        if (! $imagePath) {
            return response()->json(['success' => false, 'message' => 'Invalid image data.'], 422);
        }

        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];
        $locationName = class_exists(ReverseGeocoder::class)
            ? ReverseGeocoder::lookup($lat, $lng)
            : null;

        $row = RandomOutletVisit::create([
            'eid' => $eid,
            'town' => $validated['town'],
            'outlet_name' => $validated['outlet_name'],
            'image_path' => $imagePath,
            'latitude' => $lat,
            'longitude' => $lng,
            'location_name' => $locationName,
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => $eid,
            'updated_by' => $eid,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Random outlet visit saved.',
            'data' => $this->format($row),
        ], 201);
    }

    public function index(Request $request)
    {
        $eid = $request->user()->EID;
        $limit = min((int) $request->query('limit', 50), 100);
        $rows = RandomOutletVisit::query()
            ->where('eid', $eid)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (RandomOutletVisit $row) => $this->format($row));

        return response()->json(['success' => true, 'data' => $rows]);
    }

    private function format(RandomOutletVisit $row): array
    {
        return [
            'id' => $row->id,
            'town' => $row->town,
            'outlet_name' => $row->outlet_name,
            'image_url' => $row->image_path
                ? url(Storage::disk('public')->url($row->image_path))
                : null,
            'location_name' => $row->location_name,
            'remarks' => $row->remarks,
            'created_at' => AppTimezone::toIso8601($row->created_at),
        ];
    }

    private function storeImage(string $dataUri): ?string
    {
        if (! preg_match('/^data:image\/(\w+);base64,/', $dataUri, $matches)) {
            $binary = base64_decode($dataUri, true);
            if ($binary === false) {
                return null;
            }
            $ext = 'jpg';
        } else {
            $ext = strtolower($matches[1]);
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                $ext = 'jpg';
            }
            $binary = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);
            if ($binary === false) {
                return null;
            }
        }

        $path = 'random_outlet_visit/'.AppTimezone::now()->format('Y/m').'/'.Str::uuid().'.'.$ext;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
