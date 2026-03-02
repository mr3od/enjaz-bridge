<?php

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('password reset otp can be requested', function () {
    $user = User::factory()->create();

    $this->post(route('phone-password.send'), ['phone' => $user->phone])
        ->assertRedirect(route('phone-password.reset', ['phone' => $user->phone]));

    expect(OtpCode::query()
        ->where('phone', $user->phone)
        ->where('purpose', 'password_reset')
        ->exists())->toBeTrue();
});

test('password reset request has uniform response for unknown phone', function () {
    $response = $this->post(route('phone-password.send'), ['phone' => '+967799999999']);

    $response->assertRedirect(route('phone-password.reset', ['phone' => '+967799999999']));

    expect(OtpCode::query()
        ->where('phone', '+967799999999')
        ->where('purpose', 'password_reset')
        ->exists())->toBeFalse();
});

test('password reset cooldown does not return a validation error', function () {
    $user = User::factory()->create();

    $firstResponse = $this->post(route('phone-password.send'), ['phone' => $user->phone]);
    $secondResponse = $this->post(route('phone-password.send'), ['phone' => $user->phone]);

    $firstResponse->assertRedirect(route('phone-password.reset', ['phone' => $user->phone]));
    $secondResponse->assertRedirect(route('phone-password.reset', ['phone' => $user->phone]));
    $secondResponse->assertSessionHasNoErrors();
});

test('reset password screen can be rendered', function () {
    $user = User::factory()->create();

    $this->get(route('phone-password.reset', ['phone' => $user->phone]))
        ->assertOk();
});

test('password can be reset with valid otp', function () {
    $user = User::factory()->create();

    $this->post(route('phone-password.send'), ['phone' => $user->phone]);

    $response = $this->post(route('phone-password.update'), [
        'phone' => $user->phone,
        'code' => '123456',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('password cannot be reset with invalid otp', function () {
    $user = User::factory()->create();

    $this->post(route('phone-password.send'), ['phone' => $user->phone]);

    $response = $this->post(route('phone-password.update'), [
        'phone' => $user->phone,
        'code' => '000000',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('code');
});
