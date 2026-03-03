<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shares locale, direction, and ui translations with inertia pages', function (): void {
    config()->set('app.locale', 'ar');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
            ->has('translations.ui.dashboard'));
});
