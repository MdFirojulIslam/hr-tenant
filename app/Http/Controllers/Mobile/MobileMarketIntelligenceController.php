<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\market_intelligence;
use App\Support\AppTimezone;
use App\Support\MiEditPolicy;
use App\Support\ReverseGeocoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileMarketIntelligenceController extends Controller
{
    /**
     * Active brands from artisan_admin.brands (separate Laravel DB connection).
     * Not the same path as Outlet Visit outlets (those are snapshotted in hr_db
     * or fetched via DMS HTTP API — not via artisan_admin MySQL connection).
     */
    public function brands()
    {
        try {
            if (! array_key_exists('artisan_admin', config('database.connections', []))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laravel has no artisan_admin connection. Merge it into config/database.php and run clear-caches.',
                ], 500);
            }

            $brands = Brand::query()
                ->orderBy('name')
                ->get(['id', 'name', 'thumbnail'])
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'thumbnail' => $b->thumbnail,
                ]);

            return response()->json([
                'success' => true,
                'data' => $brands,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $this->brandsFailureMessage($e),
            ], 500);
        }
    }

    /**
     * History for authenticated employee — last entry first.
     */
    public function history(Request $request)
    {
        $employeeId = Auth::user()->EID;
        $limit = min((int) $request->query('limit', 100), 200);

        $rows = market_intelligence::where('employee_id', $employeeId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $brandIds = $rows->pluck('brand_id')->unique()->values()->all();
        $brandNames = $this->brandNameMap($brandIds);

        $formatted = $rows->map(fn ($row) => $this->formatRow($row, $brandNames));

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    /**
     * Single record (own entries only).
     */
    public function show($id)
    {
        $employeeId = Auth::user()->EID;

        $row = market_intelligence::where('id', $id)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRow($row, $this->brandNameMap([$row->brand_id])),
        ]);
    }

    /**
     * Create a new market intelligence entry.
     */
    public function store(Request $request)
    {
        $employeeId = Auth::user()->EID;

        $validated = $request->validate([
            'brand_id' => 'required|integer',
            'product_name' => 'required|string|max:255',
            'size' => 'nullable|string|max:100',
            'outlet_name' => 'required|string|max:255',
            'outlet_location' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:500',
            'location_coordinate' => 'required|string|max:100',
            'division' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'country_of_origin' => 'nullable|string|max:120',
            'tp' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'product_image' => 'nullable|string',
        ]);

        if (!$this->brandExists((int) $validated['brand_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selected brand was not found.',
            ], 422);
        }

        $imagePath = $this->storeProductImage($validated['product_image'] ?? null);
        $resolved = $this->resolveLocation($validated['location_coordinate'] ?? null);

        $row = market_intelligence::create([
            'employee_id' => $employeeId,
            'brand_id' => (int) $validated['brand_id'],
            'product_name' => $validated['product_name'],
            'size' => $validated['size'] ?? null,
            'outlet_name' => $validated['outlet_name'],
            'outlet_location' => $validated['outlet_location'] ?? null,
            'location' => $resolved,
            'location_coordinate' => $validated['location_coordinate'] ?? null,
            'division' => $validated['division'],
            'district' => $validated['district'],
            'country_of_origin' => $validated['country_of_origin'] ?? null,
            'tp' => $validated['tp'] ?? null,
            'mrp' => $validated['mrp'] ?? null,
            'product_image' => $imagePath,
            'created_by' => $employeeId,
            'updated_by' => $employeeId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Market intelligence entry saved.',
            'data' => $this->formatRow($row, $this->brandNameMap([$row->brand_id])),
        ], 201);
    }

    /**
     * Update an existing entry (own records only).
     */
    public function update(Request $request, $id)
    {
        $employeeId = Auth::user()->EID;

        $row = market_intelligence::where('id', $id)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        }

        if (! MiEditPolicy::canEdit($row->created_at)) {
            return response()->json([
                'success' => false,
                'message' => MiEditPolicy::lockMessage(),
                'data' => $this->formatRow($row, $this->brandNameMap([$row->brand_id])),
            ], 422);
        }

        $validated = $request->validate([
            'brand_id' => 'required|integer',
            'product_name' => 'required|string|max:255',
            'size' => 'nullable|string|max:100',
            'outlet_name' => 'required|string|max:255',
            'outlet_location' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:500',
            'location_coordinate' => 'required|string|max:100',
            'division' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'country_of_origin' => 'nullable|string|max:120',
            'tp' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'product_image' => 'nullable|string',
        ]);

        if (!$this->brandExists((int) $validated['brand_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selected brand was not found.',
            ], 422);
        }

        $imagePath = $row->product_image;
        if (!empty($validated['product_image'])) {
            $newPath = $this->storeProductImage($validated['product_image']);
            if ($newPath) {
                if ($row->product_image) {
                    Storage::disk('public')->delete($row->product_image);
                }
                $imagePath = $newPath;
            }
        }

        $row->update([
            'brand_id' => (int) $validated['brand_id'],
            'product_name' => $validated['product_name'],
            'size' => $validated['size'] ?? null,
            'outlet_name' => $validated['outlet_name'],
            'outlet_location' => $validated['outlet_location'] ?? $row->outlet_location,
            'location' => $this->resolveLocation($validated['location_coordinate'] ?? null),
            'location_coordinate' => $validated['location_coordinate'] ?? null,
            'division' => $validated['division'],
            'district' => $validated['district'],
            'country_of_origin' => $validated['country_of_origin'] ?? $row->country_of_origin,
            'tp' => $validated['tp'] ?? null,
            'mrp' => $validated['mrp'] ?? null,
            'product_image' => $imagePath,
            'updated_by' => $employeeId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Entry updated successfully.',
            'data' => $this->formatRow($row->fresh(), $this->brandNameMap([$row->brand_id])),
        ]);
    }

    private function brandsFailureMessage(\Throwable $e): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, 'Unknown database') || str_contains($msg, "Unknown database 'artisan_admin'")) {
            return 'MySQL database artisan_admin was not found on this server.';
        }

        if (str_contains($msg, 'Access denied')) {
            return 'MySQL user cannot SELECT artisan_admin.brands (grant privilege).';
        }

        if (str_contains($msg, 'deleted_at') || str_contains($msg, 'Unknown column')) {
            return 'artisan_admin.brands schema mismatch (missing column, often deleted_at).';
        }

        if (str_contains($msg, 'not configured')
            || str_contains($msg, 'Database connection [artisan_admin]')
            || str_contains($msg, 'InvalidArgumentException')) {
            return 'Laravel connection artisan_admin not loaded. Clear config cache after merging database.php.';
        }

        if (str_contains($msg, 'Class "App\\Models\\Brand"') || str_contains($msg, 'Brand.php')) {
            return 'Brand model missing on server. Upload app/Models/Brand.php.';
        }

        return 'Unable to load brands from artisan_admin. Check connection + Brand model + clear-caches.';
    }

    private function brandExists(int $brandId): bool
    {
        try {
            return Brand::where('id', $brandId)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param  array<int>|null  $ids
     * @return array<int, string>
     */
    private function brandNameMap(?array $ids = null): array
    {
        try {
            $query = Brand::query();
            if ($ids !== null) {
                $query->whereIn('id', $ids);
            }
            return $query->pluck('name', 'id')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function storeProductImage(?string $dataUri): ?string
    {
        if ($dataUri === null || $dataUri === '') {
            return null;
        }

        if (!Str::startsWith($dataUri, 'data:image')) {
            return null;
        }

        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUri, $matches)) {
            return null;
        }

        $ext = strtolower($matches[1]);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (!in_array($ext, ['jpg', 'png', 'webp', 'gif'], true)) {
            $ext = 'png';
        }

        $base64 = substr($dataUri, strpos($dataUri, ',') + 1);
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return null;
        }

        $path = 'market_intelligence/' . date('Y/m') . '/' . Str::uuid() . '.' . $ext;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function formatRow(market_intelligence $row, array $brandNames = []): array
    {
        $imageUrl = null;
        if ($row->product_image) {
            $imageUrl = asset('storage/' . ltrim($row->product_image, '/'));
        }

        return [
            'id' => $row->id,
            'employee_id' => $row->employee_id,
            'brand_id' => $row->brand_id,
            'brand_name' => $brandNames[$row->brand_id] ?? null,
            'product_name' => $row->product_name,
            'size' => $row->size,
            'outlet_name' => $row->outlet_name,
            'outlet_location' => $row->outlet_location,
            'location' => $row->location,
            'location_coordinate' => $row->location_coordinate,
            'division' => $row->division,
            'district' => $row->district,
            'country_of_origin' => $row->country_of_origin,
            'tp' => $row->tp !== null ? (float) $row->tp : null,
            'mrp' => $row->mrp !== null ? (float) $row->mrp : null,
            'product_image' => $row->product_image,
            'product_image_url' => $imageUrl,
            'created_at' => AppTimezone::toIso8601($row->created_at),
            'updated_at' => AppTimezone::toIso8601($row->updated_at),
            'can_edit' => MiEditPolicy::canEdit($row->created_at),
            'edit_locked_reason' => MiEditPolicy::canEdit($row->created_at)
                ? null
                : MiEditPolicy::lockMessage(),
        ];
    }

    private function resolveLocation(?string $coordinate): string
    {
        if ($coordinate === null || trim($coordinate) === '') {
            return ReverseGeocoder::UNAVAILABLE;
        }

        if (! preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $coordinate, $matches)) {
            return ReverseGeocoder::UNAVAILABLE;
        }

        return ReverseGeocoder::lookup((float) $matches[1], (float) $matches[2]);
    }
}
