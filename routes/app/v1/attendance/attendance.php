<?php

use App\Http\Controllers\Mobile\V1\Attendance\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('attendance')->group(function () {
  Route::get('/today', [AttendanceController::class, 'today']);
  Route::get('/history', [AttendanceController::class, 'history']);
  Route::get('/towns/{employeeId}', [AttendanceController::class, 'towns']);
  Route::post('/check-in', [AttendanceController::class, 'checkIn']);
  Route::post('/check-out', [AttendanceController::class, 'checkOut']);
  //MobileAttendanceController
});
