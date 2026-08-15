<?php

use App\Http\Controllers\Mobile\MobileEventController;
use Illuminate\Support\Facades\Route;

// Auth-only — no page permission. Backend filters by active window + department.
Route::prefix('events')->group(function () {
    Route::get('/', [MobileEventController::class, 'index']);
    Route::get('/{id}', [MobileEventController::class, 'show'])->whereNumber('id');
});
