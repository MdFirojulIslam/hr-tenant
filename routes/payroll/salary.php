<?php

use App\Http\Controllers\payroll\EmployeeSalaryController;
use App\Http\Controllers\payroll\LoanController;
use App\Http\Controllers\payroll\SalaryAdjustmentController;
use App\Http\Controllers\payroll\BonusSheetController;
use Illuminate\Support\Facades\Route;


Route::get('/salary', [EmployeeSalaryController::class, 'index']);
Route::post('/salary-setup', [EmployeeSalaryController::class, 'create']);
Route::put('/salary-update/{id}', [EmployeeSalaryController::class, 'update']);
Route::get('/employee-salary-search', [EmployeeSalaryController::class, 'salarySearch']);

Route::get('department-list/{id}', [EmployeeSalaryController::class, 'departmentList']);
Route::get('adjustment-employees/{dept_id}', [EmployeeSalaryController::class, 'getEmployees']);

Route::get('/generate-salary', [EmployeeSalaryController::class, 'generateSalary']);
Route::get('/salary-sheet', [EmployeeSalaryController::class, 'salarySheet']);
Route::get('/export-salary-sheet', [EmployeeSalaryController::class, 'exportSalarySheet']);

Route::get('/salary-adjustment/{id}', [SalaryAdjustmentController::class, 'index']);
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

Route::get('/salary-disbursement-bank-pdf', [EmployeeSalaryController::class, 'SalaryDisbursementBankPDF']);
Route::get('/salary-disbursement-cash-pdf', [EmployeeSalaryController::class, 'SalaryDisbursementCashPDF']);
Route::get('/salary-disbursement-bank-xlsx', [EmployeeSalaryController::class, 'SalaryDisbursementBankXLSX']);
Route::get('/salary-disbursement-cash-xlsx', [EmployeeSalaryController::class, 'SalaryDisbursementCashXLSX']);
Route::get('/salary-disbursement-bank-word', [EmployeeSalaryController::class, 'SalaryDisbursementBankWORD']);
Route::get('/salary-disbursement-cash-word', [EmployeeSalaryController::class, 'SalaryDisbursementCashWORD']);



Route::get('/payslip/{id?}', [EmployeeSalaryController::class, 'salaryPayslip']);

Route::post('/salary-disbursement-complete', [EmployeeSalaryController::class, 'completeSalaryDisbursement']);

Route::post('/disburse-salary', [EmployeeSalaryController::class, 'salaryDisburse']);


Route::get('/bonus-sheet', [BonusSheetController::class, 'bonusSheet']);
Route::get('/generate-bonus', [BonusSheetController::class, 'generateBonus']);
Route::post('/update-bonus-percentage', [BonusSheetController::class, 'updatePercentage']);


Route::get('/export-bonus-sheet', [BonusSheetController::class, 'exportBonusSheet']);

Route::post('/bonus-disbursement-complete', [BonusSheetController::class, 'completeBonusDisbursement']);

Route::get('/bonus-disbursement', [BonusSheetController::class, 'bonusDisbursement']);

Route::get('/bonus-disbursement-bank-pdf', [BonusSheetController::class, 'BonusDisbursementBankPDF']);
Route::get('/bonus-disbursement-cash-pdf', [BonusSheetController::class, 'BonusDisbursementCashPDF']);
Route::get('/bonus-disbursement-bank-xlsx', [BonusSheetController::class, 'BonusDisbursementBankXLSX']);
Route::get('/bonus-disbursement-cash-xlsx', [BonusSheetController::class, 'BonusDisbursementCashXLSX']);
Route::get('/bonus-disbursement-bank-word', [BonusSheetController::class, 'BonusDisbursementBankWORD']);
Route::get('/bonus-disbursement-cash-word', [BonusSheetController::class, 'BonusDisbursementCashWORD']);

Route::post('/disburse-bonus', [BonusSheetController::class, 'bonusDisburse']);

Route::get('/payslip-print-view/{id}', [EmployeeSalaryController::class, 'printPayslip']);
