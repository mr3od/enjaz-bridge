<?php

declare(strict_types=1);

namespace App\Tenancy\Bootstrappers;

use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class SpatiePermissionsBootstrapper implements TenancyBootstrapper
{
    private const DEFAULT_CACHE_KEY = 'spatie.permission.cache';

    public function __construct(private PermissionRegistrar $registrar) {}

    public function bootstrap(Tenant $tenant): void
    {
        $this->registrar->cacheKey = self::DEFAULT_CACHE_KEY.'.tenant.'.$tenant->getTenantKey();
        $this->registrar->setPermissionsTeamId($tenant->getTenantKey());
        $this->registrar->forgetCachedPermissions();
    }

    public function revert(): void
    {
        $this->registrar->cacheKey = self::DEFAULT_CACHE_KEY;
        $this->registrar->setPermissionsTeamId(null);
        $this->registrar->forgetCachedPermissions();
    }
}
