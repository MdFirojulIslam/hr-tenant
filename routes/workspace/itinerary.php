<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Itinerary\ItineraryController;

Route::post('/itinerary', [ItineraryController::class, 'store']);
Route::get('/itinerary-options', [ItineraryController::class, 'getOptions']);
Route::get('/itinerary-items', [ItineraryController::class, 'getItems']);
Route::get('/itinerary/{id}', [ItineraryController::class, 'getItemById']);

Route::put('/itinerary/{id}', [ItineraryController::class, 'update']);

Route::post('/itinerary/submit/{id}', [ItineraryController::class, 'submit']);
Route::post('/itinerary/accept/{id}', [ItineraryController::class, 'accept']);
Route::post('/itinerary/reject/{id}', [ItineraryController::class, 'reject']);
