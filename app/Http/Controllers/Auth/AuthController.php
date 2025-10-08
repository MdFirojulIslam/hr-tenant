<?php

namespace App\Http\Controllers\Auth;

use Validator;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\user_has_page;
use App\Http\Controllers\Controller;
use App\Models\employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function getUser()
    {
        $user = User::all();
        return response()->json($user);
    }

    public function register(Request $request)
    {
        $request->validate([
            'username'     =>  'required',
            'password'  =>  'required',
            'c_password' =>  'required|same:password',
        ], [
            'username.required' => 'Username must be Unique.',
            'password.required' => 'Password is required.',
            'c_password.same'   => 'The password is confirmation does not match.',
        ]);

        $user = User::where('email', $request->input('email'))
            ->orWhere('EID', $request->input('employee_Id'))
            ->first();

        if ($user) {
            // Update the existing user's details
            $user->name = $request->input('username');
            $user->password = bcrypt($request->input('password'));
            // $user->password = Hash::make($request->password);
            $user->save();

            $message = 'User updated successfully';
        } else {
            // Create a new user if no existing user is found
            $user = User::create([
                'name' => $request->input('username'),
                'email' => $request->input('email'),
                'EID' => $request->input('employee_Id'),
                'password' => bcrypt($request->input('password')),
                // 'password' => Hash::make($request->password),
            ]);

            $message = 'User registered successfully';
        }

        // Generate token and prepare response data
        $success['token']   =   $user->createToken('MyApp')->plainTextToken;
        $success['name']    =   $user->name;

        // Return success response
        $response = [
            'success'       =>  true,
            'data'          =>  $success,
            'message'       =>  $message
        ];
        return response()->json($response, 200);
    }



    public function changePassword(Request $request)
    {
        $request->validate([
            'password'  =>  'required',
            'confirm_password' =>  'required|same:password',
        ], [
            'password.required' => 'The password is required.',
            'confirm_password.required' => 'The confirm password is required.',
            'confirm_password.same' => 'The password does not match.',
        ]);

        $userId = Auth::user()->EID;

        $user = User::select('users.id',  'users.name', 'users.email', 'users.EID', 'users.password')
            ->join('employees', 'users.EID', '=', 'employees.id')->where('employees.id', $userId)
            ->first();

        if (!$user) {
            $response = [
                'success'   => false,
                'message'   => 'User not found with the provided email address',
            ];
            return response()->json($response, 404);
        }

        $user->password = bcrypt($request->input('password'));
        // $user->password = Hash::make($request->password);
        $user->save();

        $success['token']   =   $user->createToken('MyApp')->plainTextToken;
        $success['name']    =   $user->name;
        $response           =   [
            'success'       =>  true,
            'data'          =>  $success,
            'message'       =>  'Password Changes Successfully'
        ];
        return response()->json($response, 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username'  =>  'required',
            'password'  =>  'required'
        ], [
            'username.required' => 'Username is required.',
            'password.required' => 'Password is required.',
        ]);

        if (Auth::attempt(['name' =>  $request->username, 'password'  =>  $request->password])) {
            $user = Auth::user();

            if ($user->active != 1) {
                Auth::logout();
                $response = [
                    'success'   =>  false,
                    'message'   =>  'Your account is inactive.'
                ];
                return response()->json($response, 403);
            }

            $user_id = Auth::user()->EID;
            $role_id = Auth::user()->role_id;

            // Fetch permissions for the user
            $permissions = user_has_page::where('EID', $user_id)->pluck('Page_Id');
            $decodedPermissions = [];
            foreach ($permissions as $permission) {
                $decodedPermissions = array_merge($decodedPermissions, json_decode($permission, true));
            }

            if ($user_id) {
                $user_info = employee::select('employees.id', 'employees.Full_Name', 'emp_imgs.img_url', 'departments.Name as dept_name', 'designations.Name as des_name', 'officials.Supervisor_Id as sup_id')
                    ->leftjoin('emp_imgs', 'employees.id', '=', 'emp_imgs.EID')
                    ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
                    ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
                    ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
                    ->where('employees.id', '=', $user_id)
                    ->first();
            }

            $success['token']   =   $user->createToken('MyApp')->plainTextToken;
            $success['name']    =   $user->name;
            $success['permissions'] = $decodedPermissions;
            $success['user'] = $user_info;
            $success['role_id'] = $role_id;
            $response       =   [
                'success'       =>  true,
                'data'          =>  $success,
                'message'       =>  'Successfully logged In'
            ];
            return response()->json($response, 200);
        } else {
            $response       =   [
                'success'       =>  false,
                'message'       =>  'Email or password is incorrect'
            ];
            return response()->json($response);
        }
    }


    public function logout(Request $request)
    {
        // Handle session-based logout
        Cookie::queue(Cookie::forget('XSRF-TOKEN'));
        Cookie::queue(Cookie::forget('laravel_session'));
        Cookie::queue(Cookie::forget('abnb_session'));
        
        Auth::guard('web')->logout();
    
        // Invalidate the session
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Logged out successfully']);
    }



    public function fetchUser($id)
    {
        $user = User::select('users.name', 'employees.Full_Name', 'users.EID')
            ->join('officials', 'users.EID', '=', 'officials.EID')
            ->join('employees', 'users.EID', '=', 'employees.id')
            ->where('officials.Department_Id', '=', $id)
            ->get();

        return response()->json($user);
    }
}
