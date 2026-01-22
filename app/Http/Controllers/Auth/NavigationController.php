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
            $decodedPermissions = array_merge($decodedPermissions, json_decode($permission, true));
        }

        $navItems = navigation::get()->toArray();
        $filteredNavItems = array_filter($navItems, function ($item) use ($decodedPermissions) {
            return in_array($item['id'], $decodedPermissions);
        });

        $navTree = $this->buildTree($filteredNavItems);


        $dashboard = in_array(1, $decodedPermissions);

        return response()->json(['dashboard' => $dashboard, 'tree' => $navTree]);
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
