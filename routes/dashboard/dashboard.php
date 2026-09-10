<?php


use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;


Route::get('/dashboard-department-list', [DashboardController::class, 'departmentList']);
Route::get('/dashboard-attendance-data', [DashboardController::class, 'getAttendanceData']);
Route::get('/dashboard-attendance-data-by-dept', [DashboardController::class, 'departmentFilter']);
Route::get('/dashboard-attendance-table-data', [DashboardController::class, 'getAttendanceTableData']);

Route::get('/daily-standup', [DashboardController::class, 'dailyStandup']);
