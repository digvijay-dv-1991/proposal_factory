<?php

use App\Livewire\OpportunityBoard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('opportunities.index')
        : redirect()->route('login');
})->name('home');

Route::get('/opportunities', OpportunityBoard::class)
    ->middleware(['auth'])
    ->name('opportunities.index');

Route::redirect('dashboard', '/opportunities')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
