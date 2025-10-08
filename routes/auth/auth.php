<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PageController;
use App\Http\Controllers\Auth\AuthController;

use App\Http\Controllers\Auth\NavigationController;
use App\Http\Controllers\Auth\PermissionController;

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
}); 

Route::middleware('auth:sanctum')->group(function () {

// role-permission
Route::get('/permission', [PermissionController::class, 'index']);
Route::get('/get-user', [PermissionController::class, 'getUser']);
Route::get('/get-role', [PermissionController::class, 'getRole']);

// page-permission
Route::post('/set-permission', [PageController::class, 'store']);
Route::get('/get-user-permission/{id}', [PageController::class, 'getUserPermission']);


Route::get('/navigation', [NavigationController::class, "getUserNavigation"]);
Route::get('/getNavigation', [NavigationController::class, "TreeNavigation"]);
Route::get('/getTree', [NavigationController::class, "getSubordinateTree"]);


Route::get('/user', [AuthController::class, "getUser"]);
Route::get('/user/{id}', [AuthController::class, "fetchUser"]);
Route::post('/logout', [AuthController::class, "logout"]);
Route::put('/change-password', [AuthController::class, 'changePassword']);
});