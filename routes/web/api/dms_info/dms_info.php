<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DMS\DmsDistributionInfoController;


Route::get('/dms-distributors-info', [DmsDistributionInfoController::class, 'getDistributorInfo']);
Route::get('/single-employee-info', [DmsDistributionInfoController::class, 'singleEmployee']);
Route::get('/dms-outlet-codes/{id}', [DmsDistributionInfoController::class, 'getOutletCode']);
Route::post('/outlet-visit-assignment', [DmsDistributionInfoController::class, 'outletVisitSetup']);
