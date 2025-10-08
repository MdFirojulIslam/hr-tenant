<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Attendance\AttendenceController;
use App\Http\Controllers\EmailController;



Route::get('/generate-pdf', [AttendenceController::class, 'generatePdf']);
Route::get('/cv-pdf/{id}', [EmployeeController::class, 'generatePdf']);

Route::get('/leave-req-mail/{id}', [EmailController::class, "leaveRequestEmail"]);
Route::get('/leave-status-mail/{id}', [EmailController::class, "leaveStatusEmail"]);
Route::get('/hr-approval/{id}', [EmailController::class, "hrApprovalEmail"]);
Route::post('/user-mail', [EmailController::class, "sendUserEmail"]);
Route::get('/attendance-mail', [EmailController::class, "attendanceEmail"]); 
Route::get('/monthly-attendance', [EmailController::class, "monthlyAttendance"]);

