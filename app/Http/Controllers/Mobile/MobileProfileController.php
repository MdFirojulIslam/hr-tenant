<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\employee;
use App\Support\AppTimezone;
use Illuminate\Http\Request;

class MobileProfileController extends Controller
{
    /**
     * Authenticated employee's own Profile Panel fields. Never accepts an EID.
     */
    public function show(Request $request)
    {
        $eid = $request->user()->EID;
        if (! $eid) {
            return response()->json(['success' => false, 'message' => 'Employee not linked.'], 403);
        }

        $employee = employee::with([
            'official.designation',
            'official.department',
            'official.supervisor',
            'empImg',
        ])->find($eid);

        if (! $employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $official = $employee->official;
        $supervisor = $official?->supervisor;
        $img = $employee->empImg?->img_url;
        $imageUrl = $img ? url($img) : null;

        $email = $employee->Official_Email ?: $employee->Personal_Email;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $employee->id,
                'full_name' => $employee->Full_Name,
                'employee_id' => $employee->Employee_Id,
                'img_url' => $img,
                'image_url' => $imageUrl,
                'date_of_birth' => $this->dateOnly($employee->DOB ?? $employee->Birth_Date),
                'gender' => $employee->Gender,
                'mobile' => $employee->Contact_No,
                'email' => $email,
                'personal_email' => $employee->Personal_Email,
                'official_email' => $employee->Official_Email,
                'address' => $employee->Present_Address,
                'designation' => $official?->designation?->Name,
                'department' => $official?->department?->Name,
                'joining_date' => $this->dateOnly($official?->DOJ),
                'supervisor_name' => $supervisor?->Full_Name,
                'supervisor_employee_id' => $supervisor?->Employee_Id,
            ],
        ]);
    }

    private function dateOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value, AppTimezone::NAME)->toDateString();
        } catch (\Throwable $e) {
            return is_string($value) ? $value : null;
        }
    }
}
