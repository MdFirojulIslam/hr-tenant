<?php

use App\Http\Controllers\Expense\EmplooyeeExpenseController;
use Illuminate\Support\Facades\Route;



//asset
Route::get('/tada-expense', [EmplooyeeExpenseController::class, "index"]);
Route::post('/tada-expense', [EmplooyeeExpenseController::class, "store"]);
Route::post('/other-expense-update/{id}', [EmplooyeeExpenseController::class, "otherExpenseUpdate"]);
Route::post('/travel-update/{id}', [EmplooyeeExpenseController::class, "travelUpdate"]);
Route::delete('/delete-expense/{id}', [EmplooyeeExpenseController::class, 'deleteExpense']);
Route::get('/approve-expense', [EmplooyeeExpenseController::class, 'approveExpense']);
Route::get('/trashed-expense', [EmplooyeeExpenseController::class, 'trashedExpense']);

Route::get('/fetch-user-town/{id}', [EmplooyeeExpenseController::class, 'fetchUserTown']);
Route::get('/get-absend-date/{id}', [EmplooyeeExpenseController::class, 'getAbsentDates']);
Route::get('/lock-permission/{id}', [EmplooyeeExpenseController::class, 'getLockPermission']);
Route::get('/expense-permission', [EmplooyeeExpenseController::class, 'getExpensePermission']);
Route::post('/update-expense-permission', [EmplooyeeExpenseController::class, 'updateExpensePermission']);


//report
// Route::get('/expense-report', [EmplooyeeExpenseController::class, "getExpenseReport"]);
Route::get('/users-expense', [EmplooyeeExpenseController::class, "getUsersExpense"]);
Route::get('/export-expense-summary', [EmplooyeeExpenseController::class, "exportExpenseSummary"]);