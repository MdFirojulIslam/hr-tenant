<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\child;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChildController extends Controller
{
    public function show($id)
    {
        $children = child::where('EID', $id)->get();
        return response()->json($children);
    }

    public function edit($id)
    {
        $child = child::where('EID', $id)->get();

        if (!$child) {
            return response()->json(['message' => 'Child not found'], 404);
        }
        return response()->json($child);
    }

    public function store(Request $request)
    {
        $userId = Auth::user()->EID;
        $store = child::create([
            'EID' => $request->input('eid'),
            'Child_Name' => $request->input('childName'),
            'NID' => $request->input('nid'),
            'Email' => $request->input('email'),
            'Contact_No' => $request->input('contactNo'),
            'DOB' => $request->input('dob'),
            'created_by' => $userId,
            'updated_by' => 0,
        ]);

        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully inserted',
        ];

        return response()->json($response);
    }

    public function update(Request $request, $id)
    {
        $userId = Auth::user()->EID;
        $children = child::findorfail($id);
        if ($children) {
            $children->update([
                'Child_Name' => $request->input('childName'),
                'NID' => $request->input('nid'),
                'Email' => $request->input('email'),
                'Contact_No' => $request->input('contactNo'),
                'DOB' => $request->input('dob'),
                'updated_by' => $userId,
            ]);

            $response = [
                'success' => true,
                'message' => 'Updated Successfully'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Nominee not found'
            ];
        }

        return response()->json($response);
    }
}
