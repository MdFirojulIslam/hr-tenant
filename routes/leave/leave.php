<?php

use App\Http\Controllers\Leave\LeaveController;
use App\Http\Controllers\Leave\LeaveTypeController;
use Illuminate\Support\Facades\Route;


//Leave controller
Route::get('/leave-type', [LeaveTypeController::class, 'index']); 
Route::get('/get-earned-leave/{id}', [LeaveTypeController::class, 'getEarnedLeave']); 

Route::get('/leave', [LeaveController::class, 'index']); 
Route::post('/leave', [LeaveController::class, 'store']);  
Route::get('/leave/{id}', [LeaveController::class, 'show']); 
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




//Leave Report
// Route::get('/leave-report/{id}', [LeaveController::class, 'leaveReport']);
