<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\user_has_page;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index (){
        $permissions = Permission::all();
        return response()->json($permissions);
    }

    public function pages()
    {
        $userId = Auth::user()->EID;
        $permissions = user_has_page::select('Page_Id')->where('User_Id', $userId)->get();
        return response()->json($permissions);  
    }

    public function getUser(){
        $user = User::all();
        return response()->json($user);
    }

    public function getRole(){
        $role = Role::all();
        return response()->json($role);
    }
}
