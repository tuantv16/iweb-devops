<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/lien-he', [ContactController::class, 'create'])->name('contact.create');
Route::post('/lien-he', [ContactController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.store');
