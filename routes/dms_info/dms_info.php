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
Route::delete('/outlet-visit-assignment/{employee_id}/{distributor_id}', [DmsDistributionInfoController::class, 'outletVisitSingleOutletDelete']);
Route::get('/outlet-visit-single-week/{employee_id}/{distributor_id}/{week_no}/{day_name}', [DmsDistributionInfoController::class, 'outletVisitSingleWeekDetails']);
Route::post('/outlet-visit-single-week-assignment', [DmsDistributionInfoController::class, 'outletVisitSingleWeekSetup']);


Route::get('/outlet-visit-report', [DmsDistributionInfoController::class, 'outletVisitReport']);

Route::get('/market-intelligence-report', [DmsDistributionInfoController::class, 'marketIntelligenceReport']);
Route::get('/market-intelligence-brands', [DmsDistributionInfoController::class, 'marketIntelligenceBrands']);

Route::get('/meeting-minutes-report', [DmsDistributionInfoController::class, 'meetingMinutesReport']);
Route::get('/meeting-minutes/export', [DmsDistributionInfoController::class, 'exportMeetingMinutes']);
Route::get('/meeting-minutes-towns', [DmsDistributionInfoController::class, 'meetingMinutesTowns']);

Route::get('/random-outlet-visit-report', [DmsDistributionInfoController::class, 'randomOutletVisitReport']);
Route::get('/random-outlet-visit-towns', [DmsDistributionInfoController::class, 'randomOutletVisitTowns']);
