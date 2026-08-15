<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\employee;
use App\Models\navigation;
use Illuminate\Http\Request;
use App\Models\user_has_page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NavigationController extends Controller
{


    // Tree building function
    function buildTree(array $elements, $parentId = 0)
    {
        $branch = array();

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }


    public function TreeNavigation(Request $request)
    {
        $nav = navigation::get()->toArray();
        return ["nav" => $nav, "tree" => $this->buildTree($nav)];
    }

    public function getUserNavigation()
    {
        $userId = Auth::user()->EID;
        if (!$userId) {
            return redirect('/login');
        }

        $permissions = user_has_page::where('EID', $userId)->pluck('Page_Id');

        $decodedPermissions = [];
        foreach ($permissions as $permission) {
            $decoded = is_array($permission) ? $permission : json_decode($permission, true);
            if (is_array($decoded)) {
                $decodedPermissions = array_merge($decodedPermissions, $decoded);
            }
        }
        $decodedPermissions = array_values(array_unique(array_map('intval', $decodedPermissions)));

        $mobileOnlyIds = config('mobile.mobile_only_navigation_ids', []);

        $navItems = navigation::get()->toArray();
        $filteredNavItems = array_filter($navItems, function ($item) use ($decodedPermissions, $mobileOnlyIds, $navItems) {
            if (!in_array((int) $item['id'], $decodedPermissions, true)) {
                return false;
            }

            // Hide mobile-only modules from website navigation.
            if (in_array((int) $item['id'], $mobileOnlyIds, true)) {
                return false;
            }

            // Leaf items with no Vue path stay hidden. Parent/folder menus
            // (Security, Setup, Attendance, …) intentionally have path NULL —
            // keep those so children can nest in the sidebar tree.
            $path = $item['path'] ?? null;
            $hasNoPath = $path === null || $path === '' || strtoupper((string) $path) === 'NULL';
            if ($hasNoPath) {
                $isParentFolder = false;
                foreach ($navItems as $candidate) {
                    if ((int) $candidate['parent_id'] === (int) $item['id']) {
                        $isParentFolder = true;
                        break;
                    }
                }
                if (!$isParentFolder) {
                    return false;
                }
            }

            return true;
        });

        $navTree = $this->buildTree($filteredNavItems);


        $dashboard = in_array(1, $decodedPermissions, true);

        return response()->json([
            'dashboard' => $dashboard,
            'tree' => $navTree,
            'permissions' => $decodedPermissions,
        ]);
    }


    public function getSubordinates($userId)
    {
        $subordinates = employee::select('employees.id', 'employees.Full_Name', 'employees.Employee_Id', 'designations.Name as designation', 'departments.Name as department', 'branches.Name as branch')
            ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->leftjoin('branches', 'officials.Job_Location_Id', '=', 'branches.id')
            ->where('officials.Supervisor_Id', '=', $userId)
            ->where('officials.Status', '=', 'N')
            ->orderby('departments.Name', 'asc')
            ->get();

        $allSubordinates = [];

        foreach ($subordinates as $subordinate) {
            $allSubordinates[] = $subordinate;
            $allSubordinates = array_merge($allSubordinates, $this->getSubordinates($subordinate->id));
        }

        return $allSubordinates;
    }
}
