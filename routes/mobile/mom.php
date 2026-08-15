<?php

use App\Http\Controllers\Mobile\MobileMomController;
use Illuminate\Support\Facades\Route;

Route::prefix('mom')
    ->middleware('mobile.permission:mom')
    ->group(function () {
        Route::get('/towns', [MobileMomController::class, 'towns']);
        Route::get('/', [MobileMomController::class, 'index']);
        Route::get('/{id}', [MobileMomController::class, 'show'])->whereNumber('id');
        Route::post('/', [MobileMomController::class, 'store']);
    });
