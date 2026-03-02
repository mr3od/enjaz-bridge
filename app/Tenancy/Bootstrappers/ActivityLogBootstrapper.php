<?php

declare(strict_types=1);

namespace App\Tenancy\Bootstrappers;

use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class ActivityLogBootstrapper implements TenancyBootstrapper
{
    /**
     * Lifecycle observer for tenancy bootstrap/revert events.
     * No direct consumer in Activity model fallback.
     */
    private static ?string $tenantId = null;

    public function bootstrap(Tenant $tenant): void
    {
        self::$tenantId = (string) $tenant->getTenantKey();
    }

    public function revert(): void
    {
        self::$tenantId = null;
    }

    public static function currentTenantId(): ?string
    {
        return self::$tenantId;
    }
}
