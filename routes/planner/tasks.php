<?php

use App\Http\Controllers\Mail\TasksMailController;
use App\Http\Controllers\Planner\TasksController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Task Notifications / Mailing
|--------------------------------------------------------------------------
| Registered first so the static "notifications" segment can never be
| swallowed by the generic /planner/tasks/{id} routes below.
| These routes inherit the existing auth:sanctum group from routes/api.php,
| no extra middleware is introduced here.
*/

Route::prefix('planner/tasks/notifications')->group(function () {
    Route::post('/task-create', [TasksMailController::class, 'notifyTaskCreate']);
    Route::post('/task-update', [TasksMailController::class, 'notifyTaskUpdate']);
    Route::post('/checklist-create', [TasksMailController::class, 'notifyNewChecklist']);
    Route::post('/task-complete', [TasksMailController::class, 'notifyTaskComplete']);
    Route::post('/updated-performs', [TasksMailController::class, 'notifyUpdatedPerforms']);
});

/*
| Assignee mailing hook. Called by the planner assignee picker
| (resources/js/pages/planner/components/TaskAssigneeModal.vue) right after
| POST /planner/tasks/{id}/assignees succeeded, so only the employees that were
| just added are reported as newly assigned. It inherits the existing
| auth:sanctum group from routes/api.php and adds no middleware of its own.
|
| This creates the notification and immediately sends the task assignment email
| to the assigned employees using their employees.Official_Email addresses.
*/
Route::post('/planner/tasks/mail/{id}/assignees', [TasksMailController::class, 'mailTaskAssignees'])
    ->whereNumber('id');

Route::get('/planner/tasks/employees', [TasksController::class, 'employees']);

/*
| Mail status route. Declared before /planner/tasks/{id} so the literal
| "mail" segment can never be captured by the generic {id} route.
*/
Route::get('planner/tasks/mail/', [TasksMailController::class, 'sendMail']);

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
