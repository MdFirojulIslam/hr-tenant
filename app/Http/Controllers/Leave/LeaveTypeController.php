<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\app_attendance;
use App\Models\attendence;
use App\Models\leave_type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rats\Zkteco\Lib\Helper\Attendance;

class LeaveTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leaveType = leave_type::get();
        return response()->json($leaveType);
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
    public function show(leave_type $leave_type)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(leave_type $leave_type)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, leave_type $leave_type)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(leave_type $leave_type)
    {
        //
    }
}
