<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Gate\GateDashboard;
use App\Livewire\Plant\WhoIsInside;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route(
        auth()->user()->role === 'guardia' ? 'gate' : 'plant'
    ))->name('dashboard');

    Route::get('/caseta', GateDashboard::class)->name('gate');
    Route::get('/planta', WhoIsInside::class)->name('plant');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
