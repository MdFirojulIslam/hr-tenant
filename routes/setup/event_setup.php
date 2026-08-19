<?php

use App\Http\Controllers\Setup\EventSetupController;
use Illuminate\Support\Facades\Route;

Route::prefix('event-setup')->group(function () {
  Route::get('/', [EventSetupController::class, 'index']);
  Route::post('/', [EventSetupController::class, 'store']);
  Route::post('/{id}', [EventSetupController::class, 'update'])->whereNumber('id');
  Route::delete('/{id}', [EventSetupController::class, 'destroy'])->whereNumber('id');
});
