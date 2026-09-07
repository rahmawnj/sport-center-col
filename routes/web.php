<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoneController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('zones', [ZoneController::class, 'index'])->name('zones.index');
    Route::post('zones', [ZoneController::class, 'store'])->name('zones.store');
    Route::get('zones/{zone}', [ZoneController::class, 'show'])->name('zones.show');
    Route::put('zones/{zone}', [ZoneController::class, 'update'])->name('zones.update');
    Route::delete('zones/{zone}', [ZoneController::class, 'destroy'])->name('zones.destroy');
});

require __DIR__.'/settings.php';
