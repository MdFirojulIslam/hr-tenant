<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\training_info;
use Illuminate\Support\Facades\Auth;

class TrainingInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $training = training_info::all();

        return response()->json($training);
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
            $traning = training_info::create([
            'EID' => $request->input('eid'),
            'Training_Title' => $request->input('traningTitle'),
            'Organized_By' => $request->input('organizedBy'),
            'Topic_Covered' => $request->input('topicCovered'),
            'Duration' => $request->input('duration'),
            'Year' => $request->input('year'),
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
        $training = training_info::where('EID',$id)->get();;

        return response()->json($training);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $training = training_info::find($id);

        return response()->json($training);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $userId = Auth::user()->EID;
        $training = training_info::findorfail($id);
        $training->update([
            'Training_Title' => $request->input('traningTitle'),
            'Organized_By' => $request->input('organizedBy'),
            'Topic_Covered' => $request->input('topicCovered'),
            'Duration' => $request->input('duration'),
            'Year' => $request->input('year'),
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
    public function destroy(training_info $training_info)
    {
        //
    }
}
