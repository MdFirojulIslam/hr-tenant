<?php

namespace App\Http\Controllers\Mobile\V1\DOT;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DailyOrderTargetController extends Controller
{
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
     * Today's form context + existing entries (BST / Asia/Dhaka).
     */
    public function today()
    {
        $employeeId = Auth::user()->EID;
        $today = now()->toDateString();

        $towns = DB::table('towns')
            ->join('town_belongs', 'town_belongs.Town_Id', '=', 'towns.id')
            ->select('towns.id as town_id', 'towns.Name as town_name')
            ->where('town_belongs.EID', $employeeId)
            ->orderBy('towns.Name')
            ->get();

        $entries = daily_order_target::with('town:id,Name')
            ->where('employee_id', $employeeId)
            ->whereDate('created_at', $today)
            ->orderBy('id')
            ->get()
            ->map(fn($row) => $this->formatRow($row));

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today,
                'day' => now()->format('l'),
                'timezone' => 'Asia/Dhaka',
                'towns' => $towns,
                'entries' => $entries,
            ],
        ]);
    }

    /**
     * Submit Amount + ECO for each assigned town (create or update today's rows).
     */
    public function submit(Request $request)
    {
        $employeeId = Auth::user()->EID;
        $today = now()->toDateString();

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.town_id' => 'required|integer',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.eco' => 'required|numeric|min:0',
        ]);

        $assignedTownIds = town_belong::where('EID', $employeeId)
            ->pluck('Town_Id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($assignedTownIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No town assigned. Contact admin.',
            ], 422);
        }

        $remarks = $validated['remarks'] ?? null;
        $saved = [];

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $item) {
                $townId = (int) $item['town_id'];

                if (!in_array($townId, $assignedTownIds, true)) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => "Town {$townId} is not assigned to you.",
                    ], 422);
                }

                $existing = daily_order_target::where('employee_id', $employeeId)
                    ->where('town_id', $townId)
                    ->whereDate('created_at', $today)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'amount' => $item['amount'],
                        'eco' => $item['eco'],
                        'remarks' => $remarks,
                        'updated_by' => $employeeId,
                    ]);
                    $existing->load('town:id,Name');
                    $saved[] = $this->formatRow($existing->fresh('town:id,Name'));
                } else {
                    $row = daily_order_target::create([
                        'employee_id' => $employeeId,
                        'town_id' => $townId,
                        'amount' => $item['amount'],
                        'eco' => $item['eco'],
                        'remarks' => $remarks,
                        'created_by' => $employeeId,
                        'updated_by' => $employeeId,
                    ]);
                    $row->load('town:id,Name');
                    $saved[] = $this->formatRow($row);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save daily order target.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daily order target saved successfully.',
            'data' => $saved,
        ]);
    }

    /**
     * Current month history only (BST).
     */
    public function history(Request $request)
    {
        $employeeId = Auth::user()->EID;
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        // Lock history to current calendar month in Asia/Dhaka
        $month = now()->month;
        $year = now()->year;

        $rows = daily_order_target::with('town:id,Name')
            ->where('employee_id', $employeeId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn($row) => $this->formatRow($row));

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'records' => $rows,
            ],
        ]);
    }

    /**
     * Update a single history row — current day only.
     */
    public function update(Request $request, $id)
    {
        $employeeId = Auth::user()->EID;
        $today = now()->toDateString();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'eco' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $row = daily_order_target::where('id', $id)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        }

        if ($row->created_at->toDateString() !== $today) {
            return response()->json([
                'success' => false,
                'message' => 'Only today\'s records can be updated.',
            ], 422);
        }

        $row->update([
            'amount' => $validated['amount'],
            'eco' => $validated['eco'],
            'remarks' => $validated['remarks'] ?? $row->remarks,
            'updated_by' => $employeeId,
        ]);

        $row->load('town:id,Name');

        return response()->json([
            'success' => true,
            'message' => 'Record updated successfully.',
            'data' => $this->formatRow($row),
        ]);
    }

    private function formatRow(daily_order_target $row): array
    {
        $createdAt = $row->created_at;

        return [
            'id' => $row->id,
            'employee_id' => $row->employee_id,
            'town_id' => $row->town_id,
            'town_name' => $row->town?->Name,
            'amount' => (float) $row->amount,
            'eco' => (float) $row->eco,
            'remarks' => $row->remarks,
            'date' => $createdAt?->toDateString(),
            'day' => $createdAt?->format('l'),
            'created_at' => $createdAt?->toDateTimeString(),
            'updated_at' => $row->updated_at?->toDateTimeString(),
            'is_today' => $createdAt?->toDateString() === now()->toDateString(),
        ];
    }
}
