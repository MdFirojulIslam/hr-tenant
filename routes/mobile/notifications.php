<?php

use App\Http\Controllers\Mobile\MobileNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->group(function () {
    Route::get('/', [MobileNotificationController::class, 'index']);
    Route::post('/read-all', [MobileNotificationController::class, 'markAllRead']);
    Route::post('/{id}/read', [MobileNotificationController::class, 'markRead'])->whereNumber('id');
});
