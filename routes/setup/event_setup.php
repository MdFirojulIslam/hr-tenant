<?php

use App\Http\Controllers\Setup\EventSetupController;
use Illuminate\Support\Facades\Route;

Route::prefix('event-setup')->group(function () {
  Route::get('/', [EventSetupController::class, 'index']);
  Route::post('/', [EventSetupController::class, 'store']);
  Route::post('/{id}', [EventSetupController::class, 'update'])->whereNumber('id');
  Route::delete('/{id}', [EventSetupController::class, 'destroy'])->whereNumber('id');
});

// Task Board
Route::prefix('task-board')->group(function () {
  Route::get('/employees', [EventSetupController::class, 'taskBoardEmployees']);
  Route::get('/subordinates', [EventSetupController::class, 'taskBoardEmployees']);
  Route::get('/', [EventSetupController::class, 'taskBoardList']);
  Route::post('/', [EventSetupController::class, 'taskBoardStore']);
  Route::put('/{id}', [EventSetupController::class, 'taskBoardUpdate'])->whereNumber('id');
  Route::delete('/{id}', [EventSetupController::class, 'taskBoardDelete'])->whereNumber('id');
});
