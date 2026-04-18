<?php


use App\Http\Controllers\Roster\RosterScheduleController;
use Illuminate\Support\Facades\Route;


Route::get('/roster-schedule', [RosterScheduleController::class, 'index']);
Route::get('/roster-setup/{id}', [RosterScheduleController::class, 'show']);
Route::post('/roster-setup', [RosterScheduleController::class, 'create']);
Route::put('/roster-setup/{id}', [RosterScheduleController::class, 'update']);
Route::delete('/roster-setup/{id}', [RosterScheduleController::class, 'destroy']);


Route::get('/roster-report-list', [RosterScheduleController::class, 'reportList']);
Route::get('/roster-report-download', [RosterScheduleController::class, 'reportDownload']);

Route::get('/calendar-date-report', [RosterScheduleController::class, 'calendarDateReport']);
