<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Asset\AssetController;
use App\Http\Controllers\Asset\EmployeeAssetController;
use App\Http\Controllers\Asset\VehicleController;

//asset
Route::get('/get-asset', [AssetController::class, "getAsset"]);
Route::get('/asset', [AssetController::class, "index"]);
Route::post('/asset', [AssetController::class, "store"]);
Route::get('/asset/{id}', [AssetController::class, "editAsset"]);
Route::put('/asset/{id}', [AssetController::class, "update"]);
Route::get('/device-type', [AssetController::class, "getDeviceTypes"]);
Route::delete('/delete-asset/{id}', [AssetController::class, 'deleteAsset']);
Route::get('/search-asset', [AssetController::class, 'searchAsset']);
Route::get('/get-column-data', [AssetController::class, "getColumnData"]);

Route::get('/allAsset', [EmployeeAssetController::class, "allAsset"]);
Route::post('/employee-asset', [EmployeeAssetController::class, "store"]);
Route::get('/employee-asset', [EmployeeAssetController::class, "index"]);
Route::get('/editEmpAsset/{id}', [EmployeeAssetController::class, "editEmpAsset"]);
Route::put('/empAsset/{id}', [EmployeeAssetController::class, "update"]);
Route::get('/empAssetId', [EmployeeAssetController::class, "getAssetId"]);
Route::get('/available-asset', [EmployeeAssetController::class, "getAvailableAsset"]);



//Vechile routes
Route::post('/add-vehicle', [VehicleController::class, "store"]);
Route::post('/update-vehicle/{id}', [VehicleController::class, "update"]);
Route::get('/get-vehicle', [VehicleController::class, "index"]);
Route::get('/get-vehicle/{id}', [VehicleController::class, "getVehicle"]);

Route::get('/vehicle-requisition', [VehicleController::class, "getVehicleRequisition"]);
Route::post('/vehicle-requisition', [VehicleController::class, "addVehicleRequisition"]);
Route::get('/vehicle-bookings/{id}', [VehicleController::class, 'getVehicleBookings']);
Route::get('/get-user-requisition', [VehicleController::class, 'getUserRequisition']);

Route::get('/vehicle-requisition-approval', [VehicleController::class, 'vehicleRequisitionApproval']);

