<?php

use App\Models\Agency;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

test('role assignment in one agency does not leak to another', function () {
    $agencyA = Agency::query()->create([
        'name' => 'Agency A',
        'slug' => 'agency-a',
    ]);

    $agencyB = Agency::query()->create([
        'name' => 'Agency B',
        'slug' => 'agency-b',
    ]);

    $user = User::factory()->create([
        'agency_id' => $agencyA->id,
    ]);

    Role::findOrCreate('owner', 'web');

    app(PermissionRegistrar::class)->setPermissionsTeamId($agencyA->id);
    $user->assignRole('owner');
    expect($user->hasRole('owner'))->toBeTrue();

    app(PermissionRegistrar::class)->setPermissionsTeamId($agencyB->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('owner'))->toBeFalse();
});
