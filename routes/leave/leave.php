<?php

use App\Http\Controllers\Leave\LeaveController;
use App\Http\Controllers\Leave\LeaveTypeController;
use Illuminate\Support\Facades\Route;


//Leave controller
Route::get('/leave-type', [LeaveTypeController::class, 'index']);
Route::get('/leave', [LeaveController::class, 'index']);
Route::post('/leave', [LeaveController::class, 'store']);
Route::get('/personal-leave-details/{id}', [LeaveController::class, 'showUsedLeave']);
Route::put('/leave/{id}', [LeaveController::class, 'update']);
Route::delete('/soft-delete-leave/{id}', [LeaveController::class, 'softDeleteLeave']);
Route::get('/leave-summery/{id}', [LeaveController::class, 'leaveSummery']);
Route::get('/all-leave', [LeaveController::class, 'allLeave']);
Route::get('/supervisor-approval', [LeaveController::class, 'supervisorApproval']);
Route::get('/leave-information', [LeaveController::class, 'leaveInformation']);

Route::get('/holiday', [LeaveController::class, 'fetchHoliday']);
Route::post('/holiday', [LeaveController::class, 'insertHoliday']);
Route::put('/holiday/{id}', [LeaveController::class, 'updateHoliday']);
Route::delete('/delete-holiday/{id}', [LeaveController::class, 'deleteHoliday']);


//updated by firoj
Route::put('/leave-approval', [LeaveController::class, 'supervisedApproval']);

Route::put('/hr-approved', [LeaveController::class, 'hrApproval']);

Route::get('department-list-leave/{id}', [LeaveController::class, 'departmentList']);



//Leave Report
// Route::get('/leave-report/{id}', [LeaveController::class, 'leaveReport']);
