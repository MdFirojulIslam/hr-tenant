<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard-department-list', [DashboardController::class, 'departmentList']);
Route::get('/dashboard-attendance-data', [DashboardController::class, 'getAttendanceData']);
