<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            if ($activity->agency_id !== null) {
                return;
            }

            if (tenancy()->initialized && tenancy()->tenant !== null) {
                $activity->agency_id = (string) tenancy()->tenant->getTenantKey();

                return;
            }

            $causerAgencyId = $activity->causer?->getAttribute('agency_id');

            if ($causerAgencyId !== null) {
                $activity->agency_id = (string) $causerAgencyId;

                return;
            }

            $subjectAgencyId = $activity->subject?->getAttribute('agency_id');

            if ($subjectAgencyId !== null) {
                $activity->agency_id = (string) $subjectAgencyId;
            }
        });
    }
}
