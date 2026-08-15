<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Itinerary\ItineraryController;

Route::post('/itinerary', [ItineraryController::class, 'store']);
Route::get('/itinerary-options', [ItineraryController::class, 'getOptions']);
Route::get('/itinerary-items', [ItineraryController::class, 'getItems']);
Route::get('/itinerary/{id}', [ItineraryController::class, 'getItemById']);

Route::get('/employee-list', [ItineraryController::class, 'getAllEmployees']);
Route::get('/supervisor-list', [ItineraryController::class, 'getAllSupervisors']);

Route::get('/itinerary-daily-items', [ItineraryController::class, 'getDailyItems']);

Route::get('/itinerary-daily-all-items', [ItineraryController::class, 'getDailyAllItems']);

Route::get('/itinerary-sup/{id}', [ItineraryController::class, 'getSupervisorId']);
Route::put('/remarks-setup/{id}', [ItineraryController::class, 'supervisorRemarksSet']);

Route::put('/itinerary-daily-update', [ItineraryController::class, 'dailyItineraryUpdate']);



Route::put('/itinerary/{id}', [ItineraryController::class, 'update']);

Route::post('/itinerary/submit/{id}', [ItineraryController::class, 'submit']);
Route::post('/itinerary/accept/{id}', [ItineraryController::class, 'accept']);
Route::post('/itinerary/reject/{id}', [ItineraryController::class, 'reject']);

Route::post('/itinerary/upload-excel', [ItineraryController::class, 'uploadExcel']);
