<?php

use App\Http\Controllers\Auth\ComponentPermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/navigation-items', [ComponentPermissionController::class, 'getNavigation']);
