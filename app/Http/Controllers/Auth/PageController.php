<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\user_has_page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function index()
    {
        $userId = Auth::user()->EID;
        $permissions = user_has_page::select('Page_Id')->where('User_Id', $userId)->get();
        return response()->json($permissions);
    }


    public function getUserPermission($id)
    {
        $data = user_has_page::where('EID', $id)->pluck('Page_Id');

        // Decode the JSON array in each entry and flatten the result into a single array
        $permissions = $data->flatMap(function ($pageIds) {
            return json_decode($pageIds);
        });

        // Return the permissions as a JSON array
        return response()->json($permissions);
    }


    public function store(Request $request)
    {
        $userId = Auth::user()->EID;
        DB::beginTransaction();
        $store = user_has_page::updateOrCreate(
            ['EID' => $request->input('employee_Id')],
            [
                'Page_Id' => json_encode($request->input('permissions')),
                'created_by' => $userId
            ]
        );

        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully inserted'
        ];

        DB::commit();
        return response()->json($response);


        DB::rollback();
        $response = [
            'success'   =>  false,
            'message'   =>  'Error while inserting',
        ];
        return response()->json($response);
    }
}
