<?php

use App\Http\Controllers\Mobile\MobileAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/me', [MobileAuthController::class, 'me']);
Route::get('/permissions', [MobileAuthController::class, 'permissions']);
Route::post('/logout', [MobileAuthController::class, 'logout']);
Route::post('/verify-password', [MobileAuthController::class, 'verifyPassword']);
Route::post('/change-password', [MobileAuthController::class, 'changePassword']);
