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

//added by firoj
Route::get('/salary-adjustment-type', [SalaryAdjustmentController::class, 'getAdjustmentToInsert']);
Route::get('/salary-adjustment-types', [SalaryAdjustmentController::class, 'getAdjustmentToEdit']);

//ended by firoj

Route::get('/get-employee-loan', [LoanController::class, 'index']);
Route::post('/employee-loan', [LoanController::class, 'create']);
Route::put('/employee-loan/{id}', [LoanController::class, 'update']);
Route::get('/get-loan-repayment/{id}', [LoanController::class, 'getLoanRepayment']);
Route::post('/update-repayment-dates', [LoanController::class, 'updateRepaymentDates']);

//added by firoj
Route::get('/get-loan-amount/{id}', [LoanController::class, 'getLoanAmount']);
Route::post('/loan-repayment', [LoanController::class, 'createRepayment']);
Route::get('/loan-installments/{id}', [LoanController::class, 'repaymentHistory']);
Route::get('/repayments-info', [LoanController::class, 'repaymentInfo']);
Route::get('/delete-loan/{id}', [LoanController::class, 'deleteLoan']);


Route::get('/delete-salary/{id}', [EmployeeSalaryController::class, 'deleteSalary']);


//added by firoj
Route::get('/employee-salary-profile-information/{id}', [EmployeeSalaryController::class, 'salary_profile']);
Route::get('/emp-info/{id}', [EmployeeSalaryController::class, 'getEmployeeInfo']);
//ended by firoj

Route::get('/salary-disbursement', [EmployeeSalaryController::class, 'salaryDisbursement']);
Route::get('/salary-disbursement-bank', [EmployeeSalaryController::class, 'SalaryDisbursementBank']);
Route::get('/salary-disbursement-cash', [EmployeeSalaryController::class, 'SalaryDisbursementCash']);
