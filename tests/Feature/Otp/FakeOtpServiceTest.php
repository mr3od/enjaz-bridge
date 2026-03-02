<?php

use App\Contracts\OtpService;
use App\Models\OtpCode;

test('otp can be sent and verified', function () {
    /** @var OtpService $service */
    $service = app(OtpService::class);

    $service->send('+967712345678', 'phone_verification');

    expect($service->verify('+967712345678', 'phone_verification', '123456'))->toBeTrue();
});

test('otp can be consumed', function () {
    /** @var OtpService $service */
    $service = app(OtpService::class);

    $service->send('+967712345678', 'password_reset');
    $service->consume('+967712345678', 'password_reset');

    $otp = OtpCode::query()
        ->where('phone', '+967712345678')
        ->where('purpose', 'password_reset')
        ->latest()
        ->first();

    expect($otp)->not->toBeNull();
    expect($otp?->consumed_at)->not->toBeNull();
});

test('otp send is rate limited by cooldown', function () {
    /** @var OtpService $service */
    $service = app(OtpService::class);

    $service->send('+967700000001', 'phone_verification');

    expect($service->canSend('+967700000001', 'phone_verification'))->toBeFalse();
});
