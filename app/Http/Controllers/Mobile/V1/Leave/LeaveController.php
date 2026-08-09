<?php

namespace App\Http\Controllers\Mobile\V1\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function types()
    {
        $response = app(LeaveTypeController::class)->index();

        return response()->json([
            'success' => true,
            'data' => json_decode($response->getContent(), true),
        ]);
    }

    public function balance($employeeId)
    {
        return app(LeaveController::class)->showUsedLeave($employeeId);
    }

    public function dashboard()
    {
        return app(LeaveController::class)->leaveInformation();
    }

    public function history()
    {
        $userId = Auth::user()->EID;
        $currentYear = now()->year;

        $leaves = leave::select(
            'leaves.id',
            'leaves.from_date',
            'leaves.to_date',
            'leaves.status',
            'leaves.purpose',
            'leaves.attachment_url',
            'leave_types.leave_name'
        )
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->where('leaves.employee_id', $userId)
            ->whereYear('leaves.from_date', $currentYear)
            ->orderByDesc('leaves.id')
            ->get()
            ->map(function ($leave) {
                $start = new \DateTime($leave->from_date);
                $end = new \DateTime($leave->to_date);
                $leave->days = $start->diff($end)->days + 1;

                return $leave;
            });

        return response()->json([
            'success' => true,
            'data' => $leaves,
        ]);
    }

    public function apply(Request $request)
    {
        $request->merge([
            'employee_id' => Auth::user()->EID,
            'status' => $request->input('status', 'Pending'),
        ]);

        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'mimes:jpg,jpeg,png,pdf|max:512',
            ]);
        }

        return app(LeaveController::class)->store($request);
    }

    public function holidays(Request $request)
    {
        $year = $request->query('year', now()->year);

        return response()->json([
            'success' => true,
            'data' => holiday::whereYear('from_date', $year)->orderBy('from_date')->get(),
        ]);
    }
}
