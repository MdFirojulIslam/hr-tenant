<?php

use App\Http\Controllers\Workspace\TaskBoardController;
use Illuminate\Support\Facades\Route;

Route::prefix('task-board')->group(function () {
  Route::get('/subordinates', [TaskBoardController::class, 'subordinates']);
  Route::get('/', [TaskBoardController::class, 'index']);
  Route::post('/', [TaskBoardController::class, 'store']);
});
