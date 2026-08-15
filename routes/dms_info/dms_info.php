<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DMS\DmsDistributionInfoController;


Route::get('/dms-distributors-info', [DmsDistributionInfoController::class, 'getDistributorInfo']);
Route::get('/single-employee-info', [DmsDistributionInfoController::class, 'singleEmployee']);
Route::get('/dms-outlet-codes/{id}', [DmsDistributionInfoController::class, 'getOutletCode']);
Route::post('/outlet-visit-assignment', [DmsDistributionInfoController::class, 'outletVisitSetup']);
Route::get('/outlet-visit-setup-list', [DmsDistributionInfoController::class, 'outletVisitSetupList']);
Route::get('/outlet-visit-setup/{employee_id}/{distributor_id}', [DmsDistributionInfoController::class, 'outletVisitSetupDetails']);
Route::put('/outlet-visit-assignment/{employee_id}/{distributor_id}', [DmsDistributionInfoController::class, 'outletVisitSetupUpdate']);
Route::get('/outlet-visit-single-week/{employee_id}/{distributor_id}/{week_no}/{day_name}', [DmsDistributionInfoController::class, 'outletVisitSingleWeekDetails']);
Route::post('/outlet-visit-single-week-assignment', [DmsDistributionInfoController::class, 'outletVisitSingleWeekSetup']);
