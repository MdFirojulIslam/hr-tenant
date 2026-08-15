<?php

use App\Http\Controllers\Mobile\MobileItineraryController;
use Illuminate\Support\Facades\Route;

Route::prefix('itinerary')->group(function () {

    // Towns are shared by monthly (47) and daily (48) screens
    Route::get('/towns', [MobileItineraryController::class, 'towns']);

    // Monthly plan — navigation ID 47 (Itinerary)
    Route::middleware('mobile.permission:itinerary')->group(function () {
        Route::get('/monthly', [MobileItineraryController::class, 'monthlyList']);
        Route::get('/monthly/{id}', [MobileItineraryController::class, 'show']);
        Route::post('/monthly', [MobileItineraryController::class, 'store']);
        Route::put('/monthly/{id}', [MobileItineraryController::class, 'update']);
        Route::post('/monthly/{id}/submit', [MobileItineraryController::class, 'submit']);
    });

    // Daily actuals — navigation ID 48 (Daily Itinerary)
    Route::middleware('mobile.permission:itinerary_daily')->group(function () {
        Route::get('/today', [MobileItineraryController::class, 'today']);
        Route::put('/today', [MobileItineraryController::class, 'updateToday']);
    });

    // Supervisor approve / reject — navigation ID 50 (Admin Itinerary List)
    Route::middleware('mobile.permission:itinerary_admin')->group(function () {
        Route::get('/supervisor/monthly', [MobileItineraryController::class, 'supervisorMonthlyList']);
        Route::get('/supervisor/monthly/{id}', [MobileItineraryController::class, 'supervisorShow']);
        Route::post('/supervisor/monthly/{id}/accept', [MobileItineraryController::class, 'accept']);
        Route::post('/supervisor/monthly/{id}/reject', [MobileItineraryController::class, 'reject']);
    });
});
