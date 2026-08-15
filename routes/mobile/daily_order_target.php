<?php

use App\Http\Controllers\Mobile\MobileDailyOrderTargetController;
use Illuminate\Support\Facades\Route;

Route::prefix('daily-order-target')
    ->middleware('mobile.permission:daily_order_target')
    ->group(function () {
        Route::get('/towns', [MobileDailyOrderTargetController::class, 'towns']);
        Route::get('/today', [MobileDailyOrderTargetController::class, 'today']);
        Route::post('/submit', [MobileDailyOrderTargetController::class, 'submit']);
        Route::get('/history', [MobileDailyOrderTargetController::class, 'history']);
        Route::put('/{id}', [MobileDailyOrderTargetController::class, 'update']);
    });
