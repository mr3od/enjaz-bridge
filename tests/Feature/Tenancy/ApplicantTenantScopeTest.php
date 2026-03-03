<?php

declare(strict_types=1);

use App\Models\Agency;
use App\Models\Applicant;
use App\Models\User;

afterEach(function (): void {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('applicant queries are scoped to current tenant when tenancy is initialized', function (): void {
    $agencyA = Agency::factory()->create();
    $agencyB = Agency::factory()->create();

    $userA = User::factory()->create(['agency_id' => $agencyA->id]);
    $userB = User::factory()->create(['agency_id' => $agencyB->id]);

    $applicantA = Applicant::factory()->create([
        'agency_id' => $agencyA->id,
        'created_by' => $userA->id,
    ]);

    Applicant::factory()->create([
        'agency_id' => $agencyB->id,
        'created_by' => $userB->id,
    ]);

    tenancy()->initialize($agencyA);

    $visibleApplicantIds = Applicant::query()->pluck('id')->all();

    expect($visibleApplicantIds)->toContain($applicantA->id)
        ->and($visibleApplicantIds)->toHaveCount(1);
});
