<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Expense\EmplooyeeExpenseController;
use App\Models\emplooyee_expense;
use App\Models\employee_expense_permission;
use App\Support\ExpenseEditPolicy;
use Illuminate\Http\Request;

class MobileExpenseController extends Controller
{
    public function records(Request $request)
    {
        $response = app(EmplooyeeExpenseController::class)->index($request);
        $payload = json_decode($response->getContent(), true);

        if (!is_array($payload)) {
            return $response;
        }

        foreach (['allowance', 'travel'] as $key) {
            if (!isset($payload[$key]) || !is_array($payload[$key])) {
                continue;
            }
            $payload[$key] = array_map(
                fn ($row) => $this->withCanEdit(is_array($row) ? $row : (array) $row),
                $payload[$key]
            );
        }

        return response()->json($payload, $response->getStatusCode());
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

    public function updateAllowance(Request $request, $id)
    {
        $blocked = $this->denyIfLocked($id);
        if ($blocked !== null) {
            return $blocked;
        }

        return app(EmplooyeeExpenseController::class)->otherExpenseUpdate($request, $id);
    }

    public function updateTravel(Request $request, $id)
    {
        $blocked = $this->denyIfLocked($id);
        if ($blocked !== null) {
            return $blocked;
        }

        return app(EmplooyeeExpenseController::class)->travelUpdate($request, $id);
    }

    /**
     * Attach server-calculated can_edit (never trust client clocks).
     */
    private function withCanEdit(array $row): array
    {
        $date = $row['Date'] ?? $row['date'] ?? null;
        $status = $row['Status'] ?? $row['status'] ?? 0;

        if ($date !== null) {
            $row['can_edit'] = ExpenseEditPolicy::canEdit($date, $status);
        } else {
            $row['can_edit'] = false;
        }

        return $row;
    }

    private function denyIfLocked($id)
    {
        $expense = emplooyee_expense::findOrFail($id);

        if (!ExpenseEditPolicy::canEdit($expense->Date, $expense->Status ?? 0)) {
            return response()->json([
                'success' => false,
                'message' => 'This expense can no longer be edited. Editing closes after the 3rd of the next month.',
            ], 403);
        }

        return null;
    }
}
