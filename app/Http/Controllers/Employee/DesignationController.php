<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

use App\Models\designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $designation = designation::all();

        return response()->json($designation);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $designation = DB::table('designations')
            ->select('id', 'Name')
            ->where('Department_Id', $id)
            ->orderBy('Name', 'asc')
            ->get();

        return response()->json($designation);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(designation $designation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, designation $designation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(designation $designation)
    {
        //
    }
}
