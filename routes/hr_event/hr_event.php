<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HR_Event\HrEventController;

Route::post('/create_event', [HrEventController::class, 'storeEvent']);
Route::get('/show_event', [HrEventController::class, 'showEvent']);
Route::put('/update_event', [HrEventController::class, 'updateEvent']);
