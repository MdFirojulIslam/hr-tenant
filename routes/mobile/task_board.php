<?php

use App\Http\Controllers\Mobile\MobileTaskBoardController;
use Illuminate\Support\Facades\Route;

Route::prefix('task-board')
    ->middleware('mobile.permission:task_board')
    ->group(function () {
        Route::get('/', [MobileTaskBoardController::class, 'index']);
        Route::get('/{id}', [MobileTaskBoardController::class, 'show'])->whereNumber('id');
        Route::put('/{id}', [MobileTaskBoardController::class, 'update'])->whereNumber('id');
        Route::post('/{id}/update', [MobileTaskBoardController::class, 'update'])->whereNumber('id');
    });
