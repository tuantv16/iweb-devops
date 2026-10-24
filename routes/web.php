<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TaskDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::view('/notepad', 'notepad')->name('notepad');

Route::get('/tasks', [TaskDashboardController::class, 'index'])->name('tasks');
Route::get('/tasks/daily', [TaskDashboardController::class, 'daily'])->name('tasks.daily');

Route::get('/lien-he', [ContactController::class, 'create'])->name('contact.create');
Route::post('/lien-he', [ContactController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.store');
