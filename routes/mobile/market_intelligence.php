<?php

use App\Http\Controllers\Mobile\MobileMarketIntelligenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('market-intelligence')
    ->middleware('mobile.permission:market_intelligence')
    ->group(function () {
        Route::get('/brands', [MobileMarketIntelligenceController::class, 'brands']);
        Route::get('/history', [MobileMarketIntelligenceController::class, 'history']);
        Route::get('/{id}', [MobileMarketIntelligenceController::class, 'show']);
        Route::post('/', [MobileMarketIntelligenceController::class, 'store']);
        Route::put('/{id}', [MobileMarketIntelligenceController::class, 'update']);
    });
