<?php

use App\Models\Agency;
use App\Models\User;

test('authenticated user without agency is logged out and redirected', function () {
    $user = User::factory()->create([
        'agency_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('authenticated user with inactive agency is logged out and redirected', function () {
    $agency = Agency::query()->create([
        'name' => 'Inactive Agency',
        'slug' => 'inactive-agency',
        'is_active' => false,
    ]);

    $user = User::factory()->create([
        'agency_id' => $agency->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('authenticated user with active agency can access protected pages', function () {
    $agency = Agency::query()->create([
        'name' => 'Active Agency',
        'slug' => 'active-agency',
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'agency_id' => $agency->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
