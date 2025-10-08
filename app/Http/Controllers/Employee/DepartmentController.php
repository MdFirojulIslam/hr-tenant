<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

use App\Models\department;


class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $department = department::all();

        return response()->json($department);
    }

    public function deptForChart()
    {
        $department = Department::selectRaw('COUNT(officials.id) as employee_count, departments.Name')
            ->join('officials', 'officials.Department_Id', '=', 'departments.id')
            ->groupBy('departments.id', 'departments.Name')
            ->where('officials.Status', 'N')
            ->get();

        $department = $department->map(function ($dept) {
            $dept->Name = collect(explode(' ', $dept->Name))
                ->map(function ($word) {
                    return substr($word, 0, 1);
                })->implode('');
            return $dept;
        });

        return $department;
    }

}
