<?php

use App\Http\Controllers\Mobile\V1\Expense\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('expense')->middleware('mobile.permission:expense')->group(function () {
  Route::get('/records', [ExpenseController::class, 'records']);
  Route::get('/permissions/{employeeId}', [ExpenseController::class, 'permissions']);
  Route::get('/towns/{employeeId}', [ExpenseController::class, 'towns']);
  Route::post('/submit', [ExpenseController::class, 'submit']);
  // MobileExpenseController
});
