<?php

namespace App\Models;

use App\Support\Tenancy\AgencyScopeResolver;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            if ($activity->agency_id !== null) {
                return;
            }

            $activity->agency_id = app(AgencyScopeResolver::class)->resolveForActivity($activity);
        });
    }
}
