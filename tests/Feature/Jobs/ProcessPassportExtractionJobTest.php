<?php

declare(strict_types=1);

use App\Enums\ApplicantStatus;
use App\Jobs\ProcessPassportExtraction;
use App\Models\Agency;
use App\Models\Applicant;
use App\Models\User;
use App\Services\PassportExtractionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('processes queued applicant and marks it extracted', function (): void {
    Storage::fake('local');

    $agency = Agency::factory()->create([
        'monthly_quota' => 10,
        'used_this_month' => 0,
    ]);

    $user = User::factory()->create([
        'agency_id' => $agency->id,
    ]);

    $applicant = Applicant::factory()->create([
        'agency_id' => $agency->id,
        'created_by' => $user->id,
        'status' => ApplicantStatus::Queued->value,
    ]);

    $relativePath = UploadedFile::fake()->image('passport-job.jpg')->store('testing', 'local');
    $absolutePath = Storage::disk('local')->path($relativePath);
    $applicant->addMedia($absolutePath)->toMediaCollection('passport', 'local');

    $job = new ProcessPassportExtraction($applicant->id, $user->id);
    $job->handle(app(PassportExtractionService::class));

    $applicant->refresh();

    expect($applicant->status)->toBe(ApplicantStatus::Extracted)
        ->and($applicant->extraction_error)->toBeNull()
        ->and($applicant->extraction_started_at)->not->toBeNull()
        ->and($applicant->extraction_finished_at)->not->toBeNull();
});

it('marks applicant as failed when extraction job cannot find passport media', function (): void {
    $agency = Agency::factory()->create();

    $user = User::factory()->create([
        'agency_id' => $agency->id,
    ]);

    $applicant = Applicant::factory()->create([
        'agency_id' => $agency->id,
        'created_by' => $user->id,
        'status' => ApplicantStatus::Queued->value,
    ]);

    $job = new ProcessPassportExtraction($applicant->id, $user->id);
    $job->handle(app(PassportExtractionService::class));

    $applicant->refresh();

    expect($applicant->status)->toBe(ApplicantStatus::Failed)
        ->and($applicant->extraction_error)->toContain('No passport image found');
});
