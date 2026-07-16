<?php

use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('extensions', [ExtensionController::class,'index'])->name('extensions.index');
    Route::get('extensions/create', [ExtensionController::class,'create'])->name('extensions.create');
    Route::post('extensions', [ExtensionController::class,'store'])->name('extensions.store');
    Route::get('extensions/{extension}/edit', [ExtensionController::class,'edit'])->name('extensions.edit');
    Route::patch('extensions/{extension}/update', [ExtensionController::class,'update'])->name('extensions.update');
    Route::get('extensions/{extension}', [ExtensionController::class,'show'])->name('extensions.show');
    Route::delete('extensions/{extension}', [ExtensionController::class,'destroy'])->name('extensions.destroy');

    // Route::resource('extensions', ExtensionController::class);
});

require __DIR__.'/auth.php';
