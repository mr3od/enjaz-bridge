<?php

use App\Models\OtpCode;
use App\Models\User;

test('sends verification notification', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    expect(OtpCode::query()
        ->where('phone', $user->phone)
        ->where('purpose', 'phone_verification')
        ->exists())->toBeTrue();
});

test('does not send verification notification if phone is verified', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));
});
