<?php

use App\Models\User;
use App\Services\Tenancy\AgencyRoleManager;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

test('role manager restores previous team id even when role assignment fails', function () {
    $registrar = app(PermissionRegistrar::class);
    $originalTeamId = 'original-team-id';
    $registrar->setPermissionsTeamId($originalTeamId);

    $badUser = Mockery::mock(User::class);
    $badUser->shouldReceive('assignRole')
        ->once()
        ->with('owner')
        ->andThrow(new \RuntimeException('force role assignment failure'));

    $manager = app(AgencyRoleManager::class);

    expect(fn () => $manager->assignOwner($badUser, '01JTEDK00CZQ8GD8E4MH8XRHXX'))
        ->toThrow(\RuntimeException::class);

    expect($registrar->getPermissionsTeamId())->toBe($originalTeamId);
});

test('role manager restores previous team id after successful assignment', function () {
    $registrar = app(PermissionRegistrar::class);
    $originalTeamId = 'previous-team-id';
    $registrar->setPermissionsTeamId($originalTeamId);

    $user = User::factory()->create();
    $manager = app(AgencyRoleManager::class);

    $manager->assignOwner($user, $user->agency_id);

    expect($registrar->getPermissionsTeamId())->toBe($originalTeamId);

    $assigned = DB::table(config('permission.table_names.model_has_roles'))
        ->where('model_id', $user->id)
        ->where('agency_id', $user->agency_id)
        ->exists();

    expect($assigned)->toBeTrue();
});
