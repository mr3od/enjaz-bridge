<?php

use App\Models\Agency;
use App\Tenancy\Bootstrappers\ActivityLogBootstrapper;
use Spatie\Permission\PermissionRegistrar;

afterEach(function (): void {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('permission cache key is scoped on tenancy initialization', function () {
    $agency = Agency::factory()->create();

    tenancy()->initialize($agency);

    expect(app(PermissionRegistrar::class)->cacheKey)
        ->toBe('spatie.permission.cache.tenant.'.$agency->id);
});

test('permission team id is set on tenancy initialization', function () {
    $agency = Agency::factory()->create();

    tenancy()->initialize($agency);

    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())
        ->toBe($agency->id);
});

test('permission cache key is reset on tenancy end', function () {
    $agency = Agency::factory()->create();

    tenancy()->initialize($agency);
    tenancy()->end();

    expect(app(PermissionRegistrar::class)->cacheKey)
        ->toBe('spatie.permission.cache');
});

test('permission team id is reset on tenancy end', function () {
    $agency = Agency::factory()->create();

    tenancy()->initialize($agency);
    tenancy()->end();

    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())
        ->toBeNull();
});

test('activity bootstrapper tracks current tenant id', function () {
    $agency = Agency::factory()->create();

    tenancy()->initialize($agency);

    expect(ActivityLogBootstrapper::currentTenantId())
        ->toBe($agency->id);
});

test('activity bootstrapper clears tenant id when tenancy ends', function () {
    $agency = Agency::factory()->create();

    tenancy()->initialize($agency);
    tenancy()->end();

    expect(ActivityLogBootstrapper::currentTenantId())
        ->toBeNull();
});
