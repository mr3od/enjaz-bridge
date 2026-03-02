<?php

use App\Models\Agency;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'phone' => '+967712345678',
        'agency_name' => 'Alpha Agency',
        'agency_city' => 'Sanaa',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('phone', '+967712345678')->first();
    expect($user)->not->toBeNull();
    expect($user?->agency_id)->not->toBeNull();

    $agency = Agency::query()->find($user?->agency_id);
    expect($agency)->not->toBeNull();
    expect($agency?->name)->toBe('Alpha Agency');
    expect($agency?->city)->toBe('Sanaa');
    expect($agency?->plan->value)->toBe('free');
    expect($agency?->monthly_quota)->toBe(10);
    expect($agency?->used_this_month)->toBe(0);
    expect($agency?->is_active)->toBeTrue();
});

test('duplicate agency names generate unique slugs', function () {
    $first = $this->post(route('register.store'), [
        'name' => 'User One',
        'phone' => '+967712345670',
        'agency_name' => 'Acme Agency',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $first->assertRedirect(route('dashboard', absolute: false));
    auth()->logout();

    $second = $this->post(route('register.store'), [
        'name' => 'User Two',
        'phone' => '+967712345671',
        'agency_name' => 'Acme Agency',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $second->assertRedirect(route('dashboard', absolute: false));

    $slugs = Agency::query()
        ->where('name', 'Acme Agency')
        ->pluck('slug')
        ->all();

    expect($slugs)->toHaveCount(2);
    expect(array_unique($slugs))->toHaveCount(2);
});

test('agency creation is rolled back if user creation fails', function () {
    User::creating(function () {
        throw new RuntimeException('force user creation failure');
    });

    $this->post(route('register.store'), [
        'name' => 'Rollback User',
        'phone' => '+967712345672',
        'agency_name' => 'Rollback Agency',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(Agency::query()->where('name', 'Rollback Agency')->exists())->toBeFalse();

    User::flushEventListeners();
});
