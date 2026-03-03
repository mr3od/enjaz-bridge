<?php

use App\Http\Controllers\ApplicantReviewController;
use App\Http\Controllers\Auth\PhonePasswordResetController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PassportExtractionController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'tenant.resolve', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('passport-extractions', [PassportExtractionController::class, 'index'])->name('passport-extractions.index');
    Route::post('passport-extractions', [PassportExtractionController::class, 'store'])->name('passport-extractions.store');
    Route::get('passport-extractions/status', [PassportExtractionController::class, 'status'])->name('passport-extractions.status');
    Route::get('applicants/{applicant}', [ApplicantReviewController::class, 'show'])->name('applicants.show');
    Route::patch('applicants/{applicant}', [ApplicantReviewController::class, 'update'])->name('applicants.update');
    Route::post('applicants/{applicant}/re-extract', [ApplicantReviewController::class, 'reExtract'])->name('applicants.re-extract');
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

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

require __DIR__.'/settings.php';
