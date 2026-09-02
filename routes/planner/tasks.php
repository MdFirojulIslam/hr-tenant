<?php

use App\Http\Controllers\Planner\TasksController;
use Illuminate\Support\Facades\Route;

Route::post('/task', [TasksController::class, 'createTask']);
Route::get('/task', [TasksController::class, 'showTask']);
Route::put('/task/{id}', [TasksController::class, 'updateTask']);
