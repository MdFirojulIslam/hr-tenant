<?php

use App\Http\Controllers\Mobile\MobileAttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('attendance')->middleware('mobile.permission:attendance')->group(function () {
    Route::get('/today', [MobileAttendanceController::class, 'today']);
    Route::get('/history', [MobileAttendanceController::class, 'history']);
    Route::get('/towns/{employeeId}', [MobileAttendanceController::class, 'towns']);
    Route::post('/check-in', [MobileAttendanceController::class, 'checkIn']);
    Route::post('/check-out', [MobileAttendanceController::class, 'checkOut']);
});
