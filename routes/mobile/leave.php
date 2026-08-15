<?php

use App\Http\Controllers\Mobile\MobileLeaveController;
use Illuminate\Support\Facades\Route;

Route::prefix('leave')->middleware('mobile.permission:leave')->group(function () {
    Route::get('/types', [MobileLeaveController::class, 'types']);
    Route::get('/dashboard', [MobileLeaveController::class, 'dashboard']);
    Route::get('/balance/{employeeId}', [MobileLeaveController::class, 'balance']);
    Route::get('/history', [MobileLeaveController::class, 'history']);
    Route::get('/holidays', [MobileLeaveController::class, 'holidays']);
    Route::post('/apply', [MobileLeaveController::class, 'apply']);
});
