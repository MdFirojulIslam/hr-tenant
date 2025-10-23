<?php

use App\Http\Controllers\Attendance\AppAttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Attendance\AttendenceController;


Route::post('/attendence', [AttendenceController::class, 'store']);
Route::post('/attendence/edit', [AttendenceController::class, 'edit']);
Route::get('/attendence', [AttendenceController::class, 'getAttendance']);
Route::get('/location', [AttendenceController::class, 'getTsoLocation']);

Route::get('/emp-attendence', [AttendenceController::class, 'attendenceEmployee']);
Route::get('/fetch-attendence', [AttendenceController::class, 'fetchAttendence']);
Route::get('/fetch-zkt-attendence', [AttendenceController::class, 'fetchZktAttendence']);
Route::get('/fetch-zkt-user', [AttendenceController::class, 'fetchZktUser']);


//App Attendance
Route::post('/app-attendance', [AppAttendanceController::class, 'insertAppAttendance']);
