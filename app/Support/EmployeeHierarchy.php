<?php

namespace App\Support;

use App\Models\official;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Existing HRIS hierarchy: officials.Supervisor_Id → employees.id (EID).
 * Active employees: officials.Status = 'N'.
 */
class EmployeeHierarchy
{
    /**
     * Super-admin / HR roles used across leave, vehicle, and expense.
     */
    public static function isPrivileged(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roleId = (int) ($user->role_id ?? 0);

        return $roleId === 1 || $roleId === 2;
    }

    /**
     * Recursive subordinate employee IDs (employees.id) under $supervisorEid.
     */
    public static function subordinateIds(int $supervisorEid): array
    {
        $all = [];
        $queue = [$supervisorEid];

        while (!empty($queue)) {
            $current = array_shift($queue);

            $rows = DB::table('officials')
                ->join('employees', 'officials.EID', '=', 'employees.id')
                ->select('employees.id as EID')
                ->where('officials.Supervisor_Id', $current)
                ->where('officials.Status', 'N')
                ->get();

            foreach ($rows as $row) {
                $eid = (int) $row->EID;
                if ($eid && !in_array($eid, $all, true)) {
                    $all[] = $eid;
                    $queue[] = $eid;
                }
            }
        }

        return $all;
    }

    /**
     * Direct reports only: officials.Supervisor_Id = $supervisorEid.
     * Task Assign Panel uses this — not the recursive tree and not role bypass.
     */
    public static function directSubordinateIds(int $supervisorEid): array
    {
        $rows = DB::table('officials')
            ->join('employees', 'officials.EID', '=', 'employees.id')
            ->where('officials.Supervisor_Id', $supervisorEid)
            ->where('officials.Status', 'N')
            ->pluck('employees.id');

        $ids = [];
        foreach ($rows as $id) {
            $eid = (int) $id;
            if ($eid && !in_array($eid, $ids, true)) {
                $ids[] = $eid;
            }
        }

        return $ids;
    }

    public static function isSubordinateOf(int $supervisorEid, int $employeeEid): bool
    {
        return in_array($employeeEid, self::subordinateIds($supervisorEid), true);
    }

    public static function isDirectSubordinateOf(int $supervisorEid, int $employeeEid): bool
    {
        if ($employeeEid <= 0) {
            return false;
        }

        return in_array($employeeEid, self::directSubordinateIds($supervisorEid), true);
    }

    public static function departmentId(?int $employeeEid): ?int
    {
        if (!$employeeEid) {
            return null;
        }

        $deptId = official::where('EID', $employeeEid)->value('Department_Id');

        return $deptId !== null ? (int) $deptId : null;
    }
}
