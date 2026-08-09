<?php

namespace App\Http\Controllers\Mobile\V1\Expense;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function records(Request $request)
    {
        return app(EmplooyeeExpenseController::class)->index($request);
    }

    public function permissions($employeeId)
    {
        $data = employee_expense_permission::select('Date_Lock', 'Attendance_Lock')
            ->where('EID', $employeeId)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $data ?? [
                'Date_Lock' => 1,
                'Attendance_Lock' => 1,
            ],
        ]);
    }

    public function towns($employeeId)
    {
        $towns = app(EmplooyeeExpenseController::class)->fetchUserTown($employeeId);

        return response()->json([
            'success' => true,
            'data' => json_decode($towns->getContent(), true),
        ]);
    }

    /**
     * Submit expense rows (JSON or multipart/form-data).
     *
     * Multipart field names (same as web):
     * - allowance[i][town_id|date|particular|description|amount]
     * - allowance[i][attachment] (file, max 500KB)
     * - travels[i][town_id|date|from|to|vehicle|amount]
     * - travels[i][attachment] (file, max 500KB)
     */
    public function submit(Request $request)
    {
        $request->validate([
            'allowance' => 'nullable|array',
            'travels' => 'nullable|array',
            'allowance.*.attachment' => 'nullable|file|max:500',
            'travels.*.attachment' => 'nullable|file|max:500',
        ]);

        $allowance = $request->input('allowance', []);
        $travels = $request->input('travels', []);

        if (empty($allowance) && empty($travels)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one allowance or travel entry is required.',
            ], 422);
        }

        // Ensure nested UploadedFile attachments stay on the request arrays
        // (Laravel keeps them when using multipart field names above).
        return app(EmplooyeeExpenseController::class)->store($request);
    }
}
