<?php

use App\Http\Controllers\Mobile\MobileProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/profile', [MobileProfileController::class, 'show']);
