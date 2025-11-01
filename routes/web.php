<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\GoalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Onboarding routes
Route::get('/onboarding/video', [OnboardingController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('onboarding.video');

Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])
    ->middleware(['auth', 'verified'])
    ->name('onboarding.complete');

// Goals routes
Route::get('/goals', [GoalController::class, 'index'])
    ->middleware(['auth', 'verified', 'onboarding.completed'])
    ->name('goals.index');

Route::get('/goals/create', [GoalController::class, 'create'])
    ->middleware(['auth', 'verified', 'onboarding.completed'])
    ->name('goals.create');

Route::post('/goals', [GoalController::class, 'store'])
    ->middleware(['auth', 'verified', 'onboarding.completed'])
    ->name('goals.store');

Route::get('/goals/{goal}', [GoalController::class, 'show'])
    ->middleware(['auth', 'verified', 'onboarding.completed'])
    ->name('goals.show');

Route::patch('/goals/{goal}/complete', [GoalController::class, 'complete'])
    ->middleware(['auth', 'verified', 'onboarding.completed'])
    ->name('goals.complete');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'onboarding.completed'])->name('dashboard');

Route::middleware(['auth', 'onboarding.completed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
