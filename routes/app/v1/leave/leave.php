<?php

use App\Http\Controllers\Mobile\V1\Leave\LeaveController;
use Illuminate\Support\Facades\Route;

Route::prefix('leave')->middleware('mobile.permission:leave')->group(function () {
  Route::get('/types', [LeaveController::class, 'types']);
  Route::get('/dashboard', [LeaveController::class, 'dashboard']);
  Route::get('/balance/{employeeId}', [LeaveController::class, 'balance']);
  Route::get('/history', [LeaveController::class, 'history']);
  Route::get('/holidays', [LeaveController::class, 'holidays']);
  Route::post('/apply', [LeaveController::class, 'apply']);
  // MobileLeaveController
});
