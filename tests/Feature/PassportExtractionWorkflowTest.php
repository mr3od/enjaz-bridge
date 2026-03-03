<?php

declare(strict_types=1);

use App\Enums\ApplicantStatus;
use App\Jobs\ProcessPassportExtraction;
use App\Models\Agency;
use App\Models\Applicant;
use App\Models\Extraction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('queues one extraction job per uploaded file', function (): void {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('passport-extractions.store'), [
        'files' => [
            UploadedFile::fake()->image('passport-1.jpg'),
            UploadedFile::fake()->image('passport-2.png'),
        ],
    ]);

    $response->assertRedirect();

    expect(Applicant::query()->count())->toBe(2);

    Applicant::query()->each(function (Applicant $applicant) use ($user): void {
        expect($applicant->agency_id)->toBe($user->agency_id)
            ->and($applicant->created_by)->toBe($user->id)
            ->and($applicant->status)->toBe(ApplicantStatus::Queued)
            ->and($applicant->extraction_requested_at)->not->toBeNull()
            ->and($applicant->getFirstMedia('passport'))->not->toBeNull();
    });

    Queue::assertPushed(ProcessPassportExtraction::class, 2);
});

it('returns tenant scoped status payload', function (): void {
    $agencyA = Agency::factory()->create();
    $agencyB = Agency::factory()->create();

    $userA = User::factory()->create(['agency_id' => $agencyA->id]);
    $userB = User::factory()->create(['agency_id' => $agencyB->id]);

    $applicantA = Applicant::factory()->create([
        'agency_id' => $agencyA->id,
        'created_by' => $userA->id,
        'status' => ApplicantStatus::Queued->value,
    ]);

    $applicantB = Applicant::factory()->create([
        'agency_id' => $agencyB->id,
        'created_by' => $userB->id,
        'status' => ApplicantStatus::Queued->value,
    ]);

    $this->actingAs($userA)
        ->get(route('passport-extractions.status', [
            'ids' => [$applicantA->id, $applicantB->id],
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'applicants')
        ->assertJsonPath('applicants.0.id', $applicantA->id)
        ->assertJsonPath('quota.monthly_quota', $agencyA->monthly_quota);
});

it('marks stale processing applicants as failed during status polling', function (): void {
    config()->set('ai.passport.ui.stale_processing_minutes', 1);

    $user = User::factory()->create();

    $applicant = Applicant::factory()->create([
        'agency_id' => $user->agency_id,
        'created_by' => $user->id,
        'status' => ApplicantStatus::Processing->value,
        'extraction_started_at' => now()->subMinutes(5),
        'extraction_finished_at' => null,
        'extraction_error' => null,
    ]);

    $this->actingAs($user)
        ->get(route('passport-extractions.status', [
            'ids' => [$applicant->id],
        ]))
        ->assertOk()
        ->assertJsonPath('applicants.0.status', ApplicantStatus::Failed->value);

    $applicant->refresh();

    expect($applicant->status)->toBe(ApplicantStatus::Failed)
        ->and($applicant->extraction_error)->toContain('timed out')
        ->and($applicant->extraction_finished_at)->not->toBeNull();
});

it('renders extraction index inertia payload', function (): void {
    $user = User::factory()->create();

    Applicant::factory()->create([
        'agency_id' => $user->agency_id,
        'created_by' => $user->id,
        'status' => ApplicantStatus::Processing->value,
    ]);

    $this->actingAs($user)
        ->get(route('passport-extractions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('passport-extractions/index')
            ->has('applicants', 1)
            ->has('quota.monthly_quota')
            ->has('batch_limit')
            ->has('max_file_kb'));
});

it('updates applicant extracted fields from review form', function (): void {
    $user = User::factory()->create();

    $applicant = Applicant::factory()->create([
        'agency_id' => $user->agency_id,
        'created_by' => $user->id,
        'status' => ApplicantStatus::Extracted->value,
    ]);

    $this->actingAs($user)
        ->patch(route('applicants.update', $applicant), [
            'passport_number' => 'A12345678',
            'country_code' => 'yem',
            'sex' => 'm',
            'surname_en' => 'DOE',
            'given_names_en' => 'JOHN',
        ])
        ->assertRedirect(route('applicants.show', $applicant));

    $applicant->refresh();

    expect($applicant->passport_number)->toBe('A12345678')
        ->and($applicant->country_code)->toBe('YEM')
        ->and($applicant->sex)->toBe('M')
        ->and($applicant->surname_en)->toBe('DOE')
        ->and($applicant->given_names_en)->toBe('JOHN');
});

it('renders applicant review inertia payload with extraction metadata', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();

    $applicant = Applicant::factory()->create([
        'agency_id' => $user->agency_id,
        'created_by' => $user->id,
    ]);

    Extraction::factory()->create([
        'applicant_id' => $applicant->id,
        'agency_id' => $user->agency_id,
        'user_id' => $user->id,
        'model_used' => 'openai-responses/gpt-5-mini',
        'processing_ms' => 1234,
    ]);

    $relativePath = UploadedFile::fake()->image('review-passport.jpg')->store('testing', 'local');
    $absolutePath = Storage::disk('local')->path($relativePath);
    $applicant->addMedia($absolutePath)->toMediaCollection('passport', 'local');

    $passportMedia = $applicant->getFirstMedia('passport');

    expect($passportMedia)->not->toBeNull();
    expect($passportMedia?->getPathRelativeToRoot())->toContain("agencies/{$user->agency_id}/");

    $this->actingAs($user)
        ->get(route('applicants.show', $applicant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('applicants/show')
            ->where('applicant.id', $applicant->id)
            ->where('applicant.passport_image_url', fn (mixed $url) => is_string($url) && $url !== '')
            ->where('latest_extraction.model_used', 'openai-responses/gpt-5-mini')
            ->where('latest_extraction.processing_ms', 1234));
});

it('re-extract endpoint re-queues applicant and dispatches a job', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $applicant = Applicant::factory()->create([
        'agency_id' => $user->agency_id,
        'created_by' => $user->id,
        'status' => ApplicantStatus::Failed->value,
        'extraction_error' => 'previous error',
    ]);

    $this->actingAs($user)
        ->post(route('applicants.re-extract', $applicant))
        ->assertRedirect(route('applicants.show', $applicant));

    $applicant->refresh();

    expect($applicant->status)->toBe(ApplicantStatus::Queued)
        ->and($applicant->extraction_error)->toBeNull()
        ->and($applicant->extraction_requested_at)->not->toBeNull();

    Queue::assertPushed(ProcessPassportExtraction::class, function (ProcessPassportExtraction $job) use ($applicant, $user): bool {
        return $job->applicantId === $applicant->id && $job->userId === $user->id;
    });
});

it('returns a user-facing quota error when monthly quota is exhausted', function (): void {
    Storage::fake('local');
    Queue::fake();

    $agency = Agency::factory()->create([
        'monthly_quota' => 1,
        'used_this_month' => 1,
    ]);

    $user = User::factory()->create([
        'agency_id' => $agency->id,
    ]);

    $this->actingAs($user)
        ->from(route('passport-extractions.index'))
        ->post(route('passport-extractions.store'), [
            'files' => [UploadedFile::fake()->image('quota.jpg')],
        ])
        ->assertRedirect(route('passport-extractions.index'))
        ->assertSessionHasErrors('files');

    expect(Applicant::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});
