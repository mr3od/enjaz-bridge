<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('allows authenticated users to switch locale and updates shared direction', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('locale.update'), [
            'locale' => 'en',
        ])
        ->assertRedirect(route('dashboard'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'en')
            ->where('direction', 'ltr')
            ->has('translations.ui.english'));
});

it('validates locale switch payload', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('locale.update'), [
            'locale' => 'fr',
        ])
        ->assertSessionHasErrors('locale');
});
