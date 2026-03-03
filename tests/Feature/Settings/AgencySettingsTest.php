<?php

use App\Models\Agency;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('agency settings page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('agency.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/agency')
            ->where('agency.name', $user->agency->name)
            ->where('agency.city', $user->agency->city)
            ->where('agency.plan', $user->agency->plan->value)
        );
});

test('agency information can be updated for the current tenant only', function () {
    $user = User::factory()->create();
    $otherAgency = Agency::factory()->create([
        'name' => 'Other Agency',
        'city' => 'Aden',
    ]);

    $this->actingAs($user)
        ->patch(route('agency.update'), [
            'name' => 'Updated Agency',
            'city' => 'Sanaa',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('agency.edit'));

    expect($user->agency->refresh()->name)->toBe('Updated Agency');
    expect($user->agency->refresh()->city)->toBe('Sanaa');
    expect($otherAgency->refresh()->name)->toBe('Other Agency');
    expect($otherAgency->refresh()->city)->toBe('Aden');
});

test('agency update validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('agency.edit'))
        ->patch(route('agency.update'), [
            'name' => '',
            'city' => str_repeat('a', 300),
        ])
        ->assertSessionHasErrors([
            'name',
            'city',
        ])
        ->assertRedirect(route('agency.edit'));
});
