<?php

use App\Http\Controllers\Auth\PhonePasswordResetController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'tenant.resolve', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::post('phone/verify', [PhoneVerificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('phone-verification.store');
});

Route::middleware('guest')->group(function () {
    Route::post('phone/forgot-password', [PhonePasswordResetController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('phone-password.send');
    Route::get('phone/reset-password', [PhonePasswordResetController::class, 'show'])
        ->name('phone-password.reset');
    Route::post('phone/reset-password', [PhonePasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('phone-password.update');
});

require __DIR__.'/settings.php';
