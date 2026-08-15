<?php

use App\Http\Controllers\Mobile\MobileAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__ . '/auth.php';
        require __DIR__ . '/attendance.php';
        require __DIR__ . '/leave.php';
        require __DIR__ . '/expense.php';
        require __DIR__ . '/itinerary.php';
        require __DIR__ . '/daily_order_target.php';
        require __DIR__ . '/market_intelligence.php';
        require __DIR__ . '/outlet_visited.php';
        require __DIR__ . '/mom.php';
        require __DIR__ . '/phonebook.php';
        require __DIR__ . '/vehicle_requisition.php';
        require __DIR__ . '/task_board.php';
        require __DIR__ . '/events.php';
        require __DIR__ . '/profile.php';
        require __DIR__ . '/notifications.php';
        require __DIR__ . '/random_outlet_visit.php';
        require __DIR__ . '/usage.php';
    });
});
