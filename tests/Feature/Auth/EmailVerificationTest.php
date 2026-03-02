<?php

use App\Models\OtpCode;
use App\Models\User;

test('phone verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk();
});

test('phone can be verified using otp code', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    expect(OtpCode::query()
        ->where('phone', $user->phone)
        ->where('purpose', 'phone_verification')
        ->exists())->toBeTrue();

    $response = $this->actingAs($user)->post(route('phone-verification.store'), [
        'code' => '123456',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('phone is not verified with invalid otp code', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'));

    $response = $this->actingAs($user)->post(route('phone-verification.store'), [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors('code');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verified user is redirected to dashboard from verification prompt', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertRedirect(route('dashboard', absolute: false));
});
