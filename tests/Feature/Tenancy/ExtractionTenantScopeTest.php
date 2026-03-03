<?php

declare(strict_types=1);

use App\Models\Agency;
use App\Models\Applicant;
use App\Models\Extraction;
use App\Models\User;

afterEach(function (): void {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('extraction queries are scoped to current tenant when tenancy is initialized', function (): void {
    $agencyA = Agency::factory()->create();
    $agencyB = Agency::factory()->create();

    $userA = User::factory()->create(['agency_id' => $agencyA->id]);
    $userB = User::factory()->create(['agency_id' => $agencyB->id]);

    $applicantA = Applicant::factory()->create([
        'agency_id' => $agencyA->id,
        'created_by' => $userA->id,
    ]);

    $applicantB = Applicant::factory()->create([
        'agency_id' => $agencyB->id,
        'created_by' => $userB->id,
    ]);

    $extractionA = Extraction::factory()->create([
        'agency_id' => $agencyA->id,
        'applicant_id' => $applicantA->id,
        'user_id' => $userA->id,
    ]);

    Extraction::factory()->create([
        'agency_id' => $agencyB->id,
        'applicant_id' => $applicantB->id,
        'user_id' => $userB->id,
    ]);

    tenancy()->initialize($agencyA);

    $visibleExtractionIds = Extraction::query()->pluck('id')->all();

    expect($visibleExtractionIds)->toContain($extractionA->id)
        ->and($visibleExtractionIds)->toHaveCount(1);
});
