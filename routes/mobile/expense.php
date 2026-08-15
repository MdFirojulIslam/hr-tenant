<?php

use App\Http\Controllers\Mobile\MobileExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('expense')->middleware('mobile.permission:expense')->group(function () {
    Route::get('/records', [MobileExpenseController::class, 'records']);
    Route::get('/permissions/{employeeId}', [MobileExpenseController::class, 'permissions']);
    Route::get('/towns/{employeeId}', [MobileExpenseController::class, 'towns']);
    Route::post('/submit', [MobileExpenseController::class, 'submit']);
    Route::post('/allowance/{id}', [MobileExpenseController::class, 'updateAllowance']);
    Route::post('/travel/{id}', [MobileExpenseController::class, 'updateTravel']);
});
