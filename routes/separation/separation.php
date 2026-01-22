<?php

use App\Http\Controllers\Separation\SeparationController;
use Illuminate\Support\Facades\Route;


Route::get('/separation/{id}', [SeparationController::class, 'index']);
Route::post('separation-request', [SeparationController::class, 'store']);

Route::get('/separation-info/{id}', [SeparationController::class, 'index']);

Route::get('/separations-list', [SeparationController::class, 'separationList']);

// Route::get('separation-req-mail/{id}', [SeparationController::class, 'sendMail']);
