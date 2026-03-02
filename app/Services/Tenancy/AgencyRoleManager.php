<?php

namespace App\Services\Tenancy;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AgencyRoleManager
{
    public function __construct(private PermissionRegistrar $permissionRegistrar) {}

    public function assignOwner(User $user, string $agencyId): void
    {
        $originalTeamId = $this->permissionRegistrar->getPermissionsTeamId();

        $this->permissionRegistrar->setPermissionsTeamId($agencyId);
        Role::findOrCreate('owner', 'web');
        $user->assignRole('owner');

        $this->permissionRegistrar->setPermissionsTeamId($originalTeamId);
    }
}
