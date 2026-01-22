<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\navigation;

class ComponentPermissionController extends Controller
{
    public function getNavigation()
    {
        $responses = navigation::select('id', 'name as navigation_name', 'parent_id')->get();


        // "id" => 1
        // "navigation_name" => "Dashboard"
        // "parent_id" => "0"


        $menu = [];
        $submenu = [];

        foreach ($responses as $response) {
            if ($response["parent_id"] == 0) {
            }
        }

        return response()->json($responses);
    }
}
