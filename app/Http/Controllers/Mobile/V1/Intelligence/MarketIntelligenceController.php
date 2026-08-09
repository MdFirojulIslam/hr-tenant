<?php

namespace App\Http\Controllers\Mobile\V1\Intelligence;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarketIntelligenceController extends Controller
{
    public function brands()
    {
        try {
            $brands = Brand::query()
                ->orderBy('name')
                ->get(['id', 'name', 'thumbnail'])
                ->map(fn($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'thumbnail' => $b->thumbnail,
                ]);

            return response()->json([
                'success' => true,
                'data' => $brands,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load brands. Check artisan_admin database connection.',
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

        $formatted = $rows->map(fn($row) => $this->formatRow($row, $brandNames));

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
            'location' => 'required|string|max:500',
            'location_coordinate' => 'nullable|string|max:100',
            'division' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'tp' => 'required|numeric|min:0',
            'mrp' => 'required|numeric|min:0',
            'product_image' => 'nullable|string',
        ]);

        if (!$this->brandExists((int) $validated['brand_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selected brand was not found.',
            ], 422);
        }

        $imagePath = $this->storeProductImage($validated['product_image'] ?? null);

        $row = market_intelligence::create([
            'employee_id' => $employeeId,
            'brand_id' => (int) $validated['brand_id'],
            'product_name' => $validated['product_name'],
            'size' => $validated['size'] ?? null,
            'outlet_name' => $validated['outlet_name'],
            'location' => $validated['location'],
            'location_coordinate' => $validated['location_coordinate'] ?? null,
            'division' => $validated['division'],
            'district' => $validated['district'],
            'tp' => $validated['tp'],
            'mrp' => $validated['mrp'],
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

        $validated = $request->validate([
            'brand_id' => 'required|integer',
            'product_name' => 'required|string|max:255',
            'size' => 'nullable|string|max:100',
            'outlet_name' => 'required|string|max:255',
            'location' => 'required|string|max:500',
            'location_coordinate' => 'nullable|string|max:100',
            'division' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'tp' => 'required|numeric|min:0',
            'mrp' => 'required|numeric|min:0',
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
            'location' => $validated['location'],
            'location_coordinate' => $validated['location_coordinate'] ?? null,
            'division' => $validated['division'],
            'district' => $validated['district'],
            'tp' => $validated['tp'],
            'mrp' => $validated['mrp'],
            'product_image' => $imagePath,
            'updated_by' => $employeeId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Entry updated successfully.',
            'data' => $this->formatRow($row->fresh(), $this->brandNameMap([$row->brand_id])),
        ]);
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
            'location' => $row->location,
            'location_coordinate' => $row->location_coordinate,
            'division' => $row->division,
            'district' => $row->district,
            'tp' => (float) $row->tp,
            'mrp' => (float) $row->mrp,
            'product_image' => $row->product_image,
            'product_image_url' => $imageUrl,
            'created_at' => $row->created_at?->toDateTimeString(),
            'updated_at' => $row->updated_at?->toDateTimeString(),
        ];
    }
}
