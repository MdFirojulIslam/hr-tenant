<?php

use App\Http\Controllers\Mobile\MobileVehicleRequisitionController;
use Illuminate\Support\Facades\Route;

Route::prefix('vehicle-requisition')
    ->middleware('mobile.permission:vehicle_requisition')
    ->group(function () {
        Route::get('/vehicles', [MobileVehicleRequisitionController::class, 'vehicles']);
        Route::get('/vehicles/{id}/bookings', [MobileVehicleRequisitionController::class, 'bookings'])->whereNumber('id');
        Route::get('/vehicles/{id}', [MobileVehicleRequisitionController::class, 'vehicle'])->whereNumber('id');
        Route::get('/', [MobileVehicleRequisitionController::class, 'index']);
        Route::post('/', [MobileVehicleRequisitionController::class, 'store']);
        Route::post('/{id}/cancel', [MobileVehicleRequisitionController::class, 'cancel'])->whereNumber('id');
    });
