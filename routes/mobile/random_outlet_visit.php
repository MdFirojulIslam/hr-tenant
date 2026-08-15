<?php

use App\Http\Controllers\Mobile\MobileRandomOutletVisitController;
use Illuminate\Support\Facades\Route;

Route::prefix('random-outlet-visit')->group(function () {
    Route::get('/towns', [MobileRandomOutletVisitController::class, 'towns']);
    Route::get('/', [MobileRandomOutletVisitController::class, 'index']);
    Route::post('/', [MobileRandomOutletVisitController::class, 'store']);
});
