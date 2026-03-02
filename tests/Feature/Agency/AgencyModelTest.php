<?php

use App\Enums\Plan;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('agency casts and helpers work', function () {
    $agency = Agency::query()->create([
        'name' => 'Alpha Agency',
        'slug' => 'alpha-agency',
        'plan' => Plan::Free->value,
        'monthly_quota' => 10,
        'used_this_month' => 4,
        'is_active' => true,
    ]);

    expect($agency->plan)->toBe(Plan::Free);
    expect($agency->is_active)->toBeTrue();
    expect($agency->quotaRemaining())->toBe(6);
});

test('agency has many users relation', function () {
    $agency = Agency::query()->create([
        'name' => 'Beta Agency',
        'slug' => 'beta-agency',
    ]);

    $user = User::factory()->create([
        'agency_id' => $agency->id,
    ]);

    expect($agency->users->pluck('id'))->toContain($user->id);
});
