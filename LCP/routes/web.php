<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemStatsController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/system', [SystemStatsController::class, 'index'])
    ->name('system.stats');

Route::get('/lsystem', \App\Livewire\SystemMonitor::class);

require __DIR__.'/settings.php';
