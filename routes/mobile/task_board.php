<?php

use App\Http\Controllers\Mobile\MobileTaskBoardController;
use Illuminate\Support\Facades\Route;

Route::prefix('task-board')
    ->middleware('mobile.permission:task_board')
    ->group(function () {
        Route::get('/', [MobileTaskBoardController::class, 'index']);
        Route::get('/employees', [MobileTaskBoardController::class, 'employees']);
        Route::get('/{id}', [MobileTaskBoardController::class, 'show'])->whereNumber('id');
        Route::put('/{id}', [MobileTaskBoardController::class, 'update'])->whereNumber('id');
        Route::patch('/{id}', [MobileTaskBoardController::class, 'update'])->whereNumber('id');
        Route::post('/{id}/checklists/{checklistId}', [MobileTaskBoardController::class, 'updateChecklist'])->whereNumber('id')->whereNumber('checklistId');
        Route::post('/{id}/read', [MobileTaskBoardController::class, 'markReadStatus'])->whereNumber('id');
    });
