<?php

use App\Http\Controllers\Mobile\MobileAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        require_once __DIR__ . '/auth.php';
        require_once __DIR__ . '/attendance.php';
        require_once __DIR__ . '/leave.php';
        require_once __DIR__ . '/expense.php';
    });
});
