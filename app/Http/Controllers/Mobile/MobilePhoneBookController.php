<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Internal employee directory for authenticated mobile users.
 * No page / navigation permission — Sanctum auth only.
 */
class MobilePhoneBookController extends Controller
{
    /**
     * List active employees for PhoneBook.
     *
     * Active = officials.Status = 'N' (existing HRIS rule: not separated).
     * One row per employees.id — multiple emp_imgs (or officials) must not duplicate.
     * Image = latest emp_imgs row (MAX(id)) for that EID.
     * Optional query: ?q= name / phone / email / designation / department filter.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        // Collapse multiple emp_imgs rows to a single latest image per EID.
        $latestImgIds = DB::table('emp_imgs')
            ->select('EID', DB::raw('MAX(id) as id'))
            ->groupBy('EID');

        $query = employee::query()
            ->select([
                'employees.id as eid',
                'employees.Full_Name as name',
                'employees.Contact_No as phone',
                'employees.Official_Email as official_email',
                'designations.Name as designation',
                'departments.Name as department',
                'emp_imgs.img_url as image_url',
            ])
            ->join('officials', function ($join) {
                $join->on('officials.EID', '=', 'employees.id')
                    ->where('officials.Status', '=', 'N');
            })
            ->leftJoin('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->leftJoin('departments', 'departments.id', '=', 'officials.Department_Id')
            ->leftJoinSub($latestImgIds, 'latest_imgs', function ($join) {
                $join->on('latest_imgs.EID', '=', 'employees.id');
            })
            ->leftJoin('emp_imgs', 'emp_imgs.id', '=', 'latest_imgs.id')
            // Guard against rare multiple officials.Status = N rows for one employee.
            ->groupBy(
                'employees.id',
                'employees.Full_Name',
                'employees.Contact_No',
                'employees.Official_Email',
                'designations.Name',
                'departments.Name',
                'emp_imgs.img_url'
            )
            ->orderBy('employees.Full_Name', 'asc');

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($inner) use ($like) {
                $inner->where('employees.Full_Name', 'like', $like)
                    ->orWhere('employees.Contact_No', 'like', $like)
                    ->orWhere('employees.Official_Email', 'like', $like)
                    ->orWhere('designations.Name', 'like', $like)
                    ->orWhere('departments.Name', 'like', $like);
            });
        }

        // Unique by employees.id — final guarantee of one PhoneBook card per employee.
        $rows = $query->get()
            ->unique('eid')
            ->values()
            ->map(function ($row) {
                return [
                    'eid' => (int) $row->eid,
                    'name' => $row->name,
                    'phone' => $row->phone,
                    'official_email' => $row->official_email,
                    'designation' => $row->designation,
                    'department' => $row->department,
                    'image_url' => $row->image_url,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'PhoneBook loaded successfully',
            'data' => $rows,
        ]);
    }
}
