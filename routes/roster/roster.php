<?php

use App\Http\Controllers\Roster\RosterScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/roster-schedule', [RosterScheduleController::class, 'index']);
Route::post('/roster-setup', [RosterScheduleController::class, 'create']);
Route::put('/roster-setup/{id}', [RosterScheduleController::class, 'update']);
Route::delete('/roster-setup/{id}', [RosterScheduleController::class, 'destroy']);

Route::get('/roster-report-list', [RosterScheduleController::class, 'reportList']);
