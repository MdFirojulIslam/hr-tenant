<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\NomineeController;
use App\Http\Controllers\Employee\OfficialController;
use App\Http\Controllers\Employee\AcademicInfoController;
use App\Http\Controllers\Employee\ChildController;
use App\Http\Controllers\Employee\DepartmentController;
use App\Http\Controllers\Employee\DesignationController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employee\EmployeeInformationController;
use App\Http\Controllers\Employee\TrainingInfoController;
use App\Http\Controllers\Employee\WorkExperienceController;


//Employee Controller
Route::get('/employee', [EmployeeController::class, 'index']);
Route::post('/employee', [EmployeeController::class, 'store']);
Route::get('/employee/{id}', [EmployeeController::class, 'show']);
Route::get('/employee/{id}/edit', [EmployeeController::class, 'edit']);
Route::put('/employee/{id}', [EmployeeController::class, 'update']);

Route::get('/emp/{id}', [EmployeeController::class, 'getEmployeeFromDept']);
Route::get('/all-emp/{id}', [EmployeeController::class, 'getAllEmployeeFromDept']);
Route::get('empEmail/{id}', [EmployeeController::class, 'getEmail']);
Route::post('/employee/{id}', [EmployeeController::class, 'update']);
Route::get('/allemp', [EmployeeController::class, 'allemp']);
Route::get('/employee-exl', [EmployeeController::class, 'EmployeeExl']);
Route::get('/emp-id', [EmployeeController::class, 'EmployeeId']);
Route::get('/employee-search', [EmployeeController::class, 'search']);
Route::get('/all-employee/{id}', [EmployeeController::class, 'getAllEmployee']);


//Employee Information Controller
Route::get('/supervised-employee/{id}', [EmployeeInformationController::class, 'getSupervisedEmployee']);
Route::get('/current-user', [EmployeeInformationController::class, "currentUser"]);
Route::get('/branch', [EmployeeInformationController::class, 'getBranch']);
Route::get('/blood', [EmployeeInformationController::class, 'getBloodGroup']);
Route::get('/board', [EmployeeInformationController::class, 'getBoard']);
Route::get('/company', [EmployeeInformationController::class, 'getCompany']);
Route::get('/phone', [EmployeeInformationController::class, 'getPhone']);
Route::get('/religion', [EmployeeInformationController::class, 'getReligion']);
Route::get('/scale', [EmployeeInformationController::class, 'getScale']);
Route::get('/education', [EmployeeInformationController::class, 'getEducation']);
Route::get('/education/{id}', [EmployeeInformationController::class, 'fetchDegree']);
Route::get('/empimg/{id}', [EmployeeInformationController::class, 'fetchEmployeeImg']);
Route::get('/get-notification', [EmployeeInformationController::class, 'getNotification']);
Route::get('/user-details', [EmployeeInformationController::class, 'userDetails']);
Route::get('/town', [EmployeeInformationController::class, 'getTown']);
Route::get('/official-info', [EmployeeInformationController::class, 'getOfficialInfo']);
Route::get('/get-supervisor', [EmployeeInformationController::class, 'getSupervisor']);
Route::get('/get-employee/{id}', [EmployeeInformationController::class, 'getEmployee']);

Route::get('/get-hierarchy', [EmployeeInformationController::class, 'getHierarchy']);

//Nominee Controller
Route::post('/nominee', [NomineeController::class, 'store']);
Route::get('/nominee/{id}/edit', [NomineeController::class, 'edit']);
Route::put('/nominee/{id}', [NomineeController::class, 'update']);

//Child Information
Route::post('/child', [ChildController::class, 'store']);
Route::get('/child/{id}', [ChildController::class, 'show']);
Route::get('/child/{id}/edit', [ChildController::class, 'edit']);
Route::put('/child/{id}', [ChildController::class, 'update']);

//academic controller
Route::get('/academic', [AcademicInfoController::class, 'index']);
Route::post('/academic', [AcademicInfoController::class, 'store']);
Route::get('/academic/{id}', [AcademicInfoController::class, 'show']);
Route::get('/academic/{id}/edit', [AcademicInfoController::class, 'edit']);
Route::put('/academic/{id}', [AcademicInfoController::class, 'update']);

//Training controller
Route::get('/training', [TrainingInfoController::class, 'index']);
Route::post('/training', [TrainingInfoController::class, 'store']);
Route::get('/training/{id}', [TrainingInfoController::class, 'show']);
Route::get('/training/{id}/edit', [TrainingInfoController::class, 'edit']);
Route::put('/training/{id}', [TrainingInfoController::class, 'update']);

//Training controller
Route::get('/work', [WorkExperienceController::class, 'index']);
Route::post('/work', [WorkExperienceController::class, 'store']);
Route::get('/work/{id}', [WorkExperienceController::class, 'show']);
Route::get('/work/{id}/edit', [WorkExperienceController::class, 'edit']);
Route::put('/work/{id}', [WorkExperienceController::class, 'update']);

//Official controller
Route::post('/official', [OfficialController::class, 'store']);
Route::get('/official/{id}/edit', [OfficialController::class, 'edit']);
Route::put('/official/{id}', [OfficialController::class, 'update']);
Route::get('/edit-town/{id}', [OfficialController::class, 'editTown']);

//Department controller
Route::get('/department', [DepartmentController::class, 'index']);
Route::get('/designation', [DesignationController::class, 'index']);
Route::get('/designation/{id}', [DesignationController::class, 'show']);
Route::get('/dashboard-count', [DashboardController::class, 'index']);


