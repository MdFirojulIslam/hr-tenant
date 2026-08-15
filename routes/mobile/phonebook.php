<?php

use App\Http\Controllers\Mobile\MobilePhoneBookController;
use Illuminate\Support\Facades\Route;

// Auth-only — no mobile.permission / page ID.
Route::get('/phonebook', [MobilePhoneBookController::class, 'index']);
