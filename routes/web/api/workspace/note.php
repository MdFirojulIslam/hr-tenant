<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Workspace\NotesController;

Route::get('/notes', [NotesController::class, 'index']);
Route::post('/notes', [NotesController::class, 'store']); 
Route::put('/notes/{id}', [NotesController::class, 'update']); 

Route::delete('/notes/{id}', [NotesController::class, 'destroy']); 
Route::get('/trash-notes', [NotesController::class, 'trashNote']); 
// Route::delete('/restore-notes', [NotesController::class, 'restoreNote']); 
// Route::delete('/delete-notes', [NotesController::class, 'deleteNote']); 

Route::post('/notes/share', [NotesController::class, 'shareNote']);
Route::get('/note-share-ids', [NotesController::class, 'noteShateIds']);

Route::get('/pin-notes', [NotesController::class, 'pinNotes']);