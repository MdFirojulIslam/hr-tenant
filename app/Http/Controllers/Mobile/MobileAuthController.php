<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\employee;
use App\Models\town_belong;
use App\Models\User;
use App\Models\user_has_page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('name', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username or password is incorrect.',
            ], 401);
        }

        if (isset($user->active) && $user->active != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive.',
            ], 403);
        }

        $pageIds = $this->resolvePageIds($user->EID);
        $modules = $this->resolveModules($pageIds);
        $hasAssignedTowns = $user->EID
            ? town_belong::where('EID', $user->EID)->exists()
            : false;

        $employee = null;
        if ($user->EID) {
            $employee = employee::select(
                'employees.id',
                'employees.Full_Name',
                'employees.Employee_Id',
                'emp_imgs.img_url',
                'departments.Name as department',
                'designations.Name as designation',
                'officials.Supervisor_Id as supervisor_id'
            )
                ->leftJoin('emp_imgs', 'employees.id', '=', 'emp_imgs.EID')
                ->leftJoin('officials', 'officials.EID', '=', 'employees.id')
                ->leftJoin('departments', 'officials.Department_Id', '=', 'departments.id')
                ->leftJoin('designations', 'officials.Designation_Id', '=', 'designations.id')
                ->where('employees.id', $user->EID)
                ->first();
        }

        $user->tokens()->where('name', 'ArtisanHRIS-Mobile')->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged in.',
            'data' => [
                'token' => $user->createToken('ArtisanHRIS-Mobile')->plainTextToken,
                'role_id' => $user->role_id ?? null,
                'permissions' => $pageIds,
                'modules' => $modules,
                'has_assigned_towns' => $hasAssignedTowns,
                'user' => $employee,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $pageIds = $this->resolvePageIds($user->EID);
        $hasAssignedTowns = $user->EID
            ? town_belong::where('EID', $user->EID)->exists()
            : false;

        $employee = employee::select(
            'employees.id',
            'employees.Full_Name',
            'employees.Employee_Id',
            'emp_imgs.img_url',
            'departments.Name as department',
            'designations.Name as designation'
        )
            ->leftJoin('emp_imgs', 'employees.id', '=', 'emp_imgs.EID')
            ->leftJoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftJoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftJoin('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('employees.id', $user->EID)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'role_id' => $user->role_id ?? null,
                'permissions' => $pageIds,
                'modules' => $this->resolveModules($pageIds),
                'has_assigned_towns' => $hasAssignedTowns,
                'user' => $employee,
            ],
        ]);
    }

    public function permissions(Request $request)
    {
        $pageIds = $this->resolvePageIds($request->user()->EID);

        return response()->json([
            'success' => true,
            'data' => [
                'permissions' => $pageIds,
                'modules' => $this->resolveModules($pageIds),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Verify the authenticated user's current password without issuing a new token.
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password verified.',
        ]);
    }

    /**
     * Change password for the authenticated Sanctum user only.
     * Identity comes from the bearer token — never from client user ids.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:4|confirmed',
        ]);

        $user = $request->user();

        if (!$user || !Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Revoke all mobile tokens so other devices must re-authenticate.
        $user->tokens()->where('name', 'ArtisanHRIS-Mobile')->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    private function resolvePageIds(?int $employeeId): array
    {
        if (!$employeeId) {
            return [];
        }

        $permissions = user_has_page::where('EID', $employeeId)->pluck('Page_Id');
        $pageIds = [];

        foreach ($permissions as $permission) {
            $decoded = json_decode($permission, true);
            if (is_array($decoded)) {
                $pageIds = array_merge($pageIds, $decoded);
            }
        }

        return array_values(array_unique(array_map('intval', $pageIds)));
    }

    private function resolveModules(array $pageIds): array
    {
        $modules = [];

        foreach (config('mobile.modules', []) as $key => $module) {
            if (in_array((int) $module['id'], $pageIds, true)) {
                $modules[$key] = $module;
            }
        }

        return $modules;
    }
}
