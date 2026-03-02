<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

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

    public function resolveForActivity(SpatieActivity $activity): ?string
    {
        $contextAgencyId = $this->tenantContext->agencyId();

        if ($contextAgencyId !== null) {
            return $contextAgencyId;
        }

        $causerAgencyId = $this->resolveFromModel($activity->causer);

        if ($causerAgencyId !== null) {
            return $causerAgencyId;
        }

        $subjectAgencyId = $this->resolveFromModel($activity->subject);

        if ($subjectAgencyId !== null) {
            return $subjectAgencyId;
        }

        return Auth::user()?->agency_id;
    }

    private function resolveFromModel(?Model $model): ?string
    {
        if ($model === null) {
            return null;
        }

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

        return null;
    }
}
