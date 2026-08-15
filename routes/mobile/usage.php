<?php

use App\Http\Controllers\Mobile\MobileUsageController;
use Illuminate\Support\Facades\Route;

Route::prefix('usage')->group(function () {
    Route::post('/sessions', [MobileUsageController::class, 'storeSession']);
});
