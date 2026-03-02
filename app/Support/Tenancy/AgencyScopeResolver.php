<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AgencyScopeResolver
{
    public function __construct(private TenantContext $tenantContext) {}

    public function resolve(?Model $model = null): ?string
    {
        if ($model !== null) {
            $modelAgencyId = $this->resolveFromModel($model);

            if ($modelAgencyId !== null) {
                return $modelAgencyId;
            }
        }

        if ($this->tenantContext->agencyId() !== null) {
            return $this->tenantContext->agencyId();
        }

        return Auth::user()?->agency_id;
    }

    private function resolveFromModel(Model $model): ?string
    {
        $agencyId = $model->getAttribute('agency_id');

        if ($agencyId !== null) {
            return (string) $agencyId;
        }

        if (! method_exists($model, 'agency')) {
            return null;
        }

        if ($model->relationLoaded('agency')) {
            return $model->agency?->id;
        }

        $relatedAgency = $model->agency()->first();

        return $relatedAgency?->id;
    }
}
