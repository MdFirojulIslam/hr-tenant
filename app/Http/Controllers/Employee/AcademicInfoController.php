<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\academic_info;
use Illuminate\Support\Facades\Auth;

class AcademicInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $academic = academic_info::with('education', 'scale', 'board', 'education.degree')->get();

        return response()->json($academic);
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
        $userId = Auth::user()->EID;
        $academic = academic_info::create([
            'EID' => $request->input('eid'),
            'Level_of_Education_Id' => $request->input('degreeId'),
            'Board_Id' => $request->input('boardId'),
            'Group' => $request->input('major'),
            'Institute_Name' => $request->input('institute'),
            'Scale_Id' => $request->input('scaleId'),
            'Result' => $request->input('result'),
            'Year_of_Passing' => $request->input('yop'),
            'Acheivement' => $request->input('acheivement'),
            'Remarks' => $request->input('remarks'),
            'created_by' => $userId,
            'updated_by' => 0,
        ]);
        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully inserted',
        ];

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $academic = academic_info::with('education', 'scale', 'board', 'education.degree')
        ->where('EID',$id)
        ->get();

        return response()->json($academic);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $academic = academic_info::with('education', 'scale', 'board', 'education.degree')->find($id);

        return response()->json($academic);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
    $userId = Auth::user()->EID;
        $academic = academic_info::findorfail($id);
        $academic->update([
            'Level_of_Education_Id' => $request->input('degreeId'),
            'Board_Id' => $request->input('boardId'),
            'Group' => $request->input('major'),
            'Institute_Name' => $request->input('institute'),
            'Scale_Id' => $request->input('scaleId'),
            'Result' => $request->input('result'),
            'Year_of_Passing' => $request->input('yop'),
            'Acheivement' => $request->input('acheivement'),
            'Remarks' => $request->input('remarks'),
            'updated_by' => $userId,
        ]);
        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully Updated',
        ];

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(academic_info $academic_info)
    {
        //
    }
}
