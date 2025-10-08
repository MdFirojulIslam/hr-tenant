<?php

use App\Http\Controllers\payroll\EmployeeSalaryController;
use App\Http\Controllers\payroll\LoanController;
use App\Http\Controllers\payroll\SalaryAdjustmentController;
use Illuminate\Support\Facades\Route;

 
Route::get('/salary', [EmployeeSalaryController::class, 'index']);
Route::post('/salary-setup', [EmployeeSalaryController::class, 'create']);
Route::put('/salary-update/{id}', [EmployeeSalaryController::class, 'update']);
Route::get('/employee-salary-search', [EmployeeSalaryController::class, 'salarySearch']);

Route::get('/generate-salary', [EmployeeSalaryController::class, 'generateSalary']);
Route::get('/salary-sheet', [EmployeeSalaryController::class, 'salarySheet']);
Route::get('/export-salary-sheet', [EmployeeSalaryController::class, 'exportSalarySheet']);

Route::get('/salary-adjustment', [SalaryAdjustmentController::class, 'index']);
Route::post('/salary-adjustment', [SalaryAdjustmentController::class, 'create']);
Route::put('/salary-adjustment/{id}', [SalaryAdjustmentController::class, 'update']);

Route::get('/get-employee-loan', [LoanController::class, 'index']);
Route::post('/employee-loan', [LoanController::class, 'create']);
Route::put('/employee-loan/{id}', [LoanController::class, 'update']);
Route::get('/get-loan-repayment/{id}', [LoanController::class, 'getLoanRepayment']);
Route::post('/update-repayment-dates', [LoanController::class, 'updateRepaymentDates']);

//added by firoj
Route::get('/employee-salary-profile-information/{id}',[EmployeeSalaryController::class,'salary_profile']);