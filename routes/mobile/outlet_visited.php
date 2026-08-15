<?php

use App\Http\Controllers\Mobile\MobileOutletVisitedController;
use Illuminate\Support\Facades\Route;

Route::prefix('outlet-visited')
    ->middleware('mobile.permission:outlet_visited')
    ->group(function () {
        Route::get('/tasks', [MobileOutletVisitedController::class, 'tasks']);
        Route::get('/tasks/{id}', [MobileOutletVisitedController::class, 'show']);
        Route::post('/tasks/{id}/check-in', [MobileOutletVisitedController::class, 'checkIn']);
        Route::post('/tasks/{id}/check-out', [MobileOutletVisitedController::class, 'checkOut']);
        Route::post('/tasks/{id}/visit', [MobileOutletVisitedController::class, 'submitVisit']);
        Route::get('/history', [MobileOutletVisitedController::class, 'history']);
    });
