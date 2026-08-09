<?php

use App\Http\Controllers\Outlet_Visit\OutletVisitSetupController;
use illuminate\Support\Facades\Route;

Route::prefix('outlet-visited')->group(function () {
  Route::get('/towns', [OutletVisitSetupController::class, 'towns']);
  Route::get('/employees', [OutletVisitSetupController::class, 'employees']);
  Route::get('/outlets', [OutletVisitSetupController::class, 'outlets']);
  Route::get('/assignments', [OutletVisitSetupController::class, 'assignments']);
  Route::post('/assignments', [OutletVisitSetupController::class, 'saveAssignments']);
});
