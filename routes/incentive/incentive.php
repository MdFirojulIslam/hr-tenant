<?php

use App\Http\Controllers\Incentive\IncentiveController;
use Illuminate\Support\Facades\Route;

Route::get('/incentive-list', [IncentiveController::class, 'index']);
Route::get('/incentive-detail/{id}', [IncentiveController::class, 'show']);
Route::get('/incentive-employees/{deptIds}', [IncentiveController::class, 'getEmployeesByDepartments']);
Route::post('/incentive-store', [IncentiveController::class, 'store']);
Route::put('/incentive-update/{id}', [IncentiveController::class, 'update']);
Route::get('/incentive-export/{id}/{format}', [IncentiveController::class, 'export']);
