<?php

use App\Http\Controllers\Planner\TasksController;
use Illuminate\Support\Facades\Route;

Route::get('/planner/tasks/employees', [TasksController::class, 'employees']);

Route::get('/planner/tasks', [TasksController::class, 'index']);
Route::get('/planner/tasks/{id}', [TasksController::class, 'show']);
Route::post('/planner/tasks', [TasksController::class, 'store']);
Route::patch('/planner/tasks/{id}', [TasksController::class, 'update']);
Route::delete('/planner/tasks/{id}', [TasksController::class, 'destroy']);

Route::post('/planner/tasks/{id}/assignees', [TasksController::class, 'storeAssignees']);
Route::delete('/planner/tasks/{id}/assignees/{userId}', [TasksController::class, 'destroyAssignee']);

Route::post('/planner/tasks/{id}/checklists', [TasksController::class, 'storeChecklist']);
Route::patch('/planner/tasks/{id}/checklists/{checklistId}', [TasksController::class, 'updateChecklist']);
Route::delete('/planner/tasks/{id}/checklists/{checklistId}', [TasksController::class, 'destroyChecklist']);

Route::post('/planner/tasks/{id}/read', [TasksController::class, 'markReadStatus']);
