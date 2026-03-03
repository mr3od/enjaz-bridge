<?php

declare(strict_types=1);

use App\Ai\Agents\PassportExtractor;
use App\Exceptions\NoPassportImageException;
use App\Exceptions\QuotaExceededException;
use App\Models\Agency;
use App\Models\Applicant;
use App\Models\Extraction;
use App\Models\User;
use App\Services\PassportExtractionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('throws when quota is exceeded on extract', function (): void {
    $agency = Agency::factory()->create([
        'monthly_quota' => 1,
        'used_this_month' => 1,
    ]);
    $user = User::factory()->create(['agency_id' => $agency->id]);

    $service = app(PassportExtractionService::class);

    expect(fn () => $service->extractFromPath($agency, $user, '/tmp/invalid.jpg'))
        ->toThrow(QuotaExceededException::class);
});

it('creates applicant extraction and consumes quota', function (): void {
    Storage::fake('local');

    $agency = Agency::factory()->create([
        'monthly_quota' => 10,
        'used_this_month' => 0,
    ]);
    $user = User::factory()->create(['agency_id' => $agency->id]);

    $relativePath = UploadedFile::fake()->image('passport.jpg')->store('testing', 'local');
    $absolutePath = Storage::disk('local')->path($relativePath);

    $service = app(PassportExtractionService::class);

    $applicant = $service->extractFromPath($agency, $user, $absolutePath);

    expect($applicant->agency_id)->toBe($agency->id)
        ->and($applicant->created_by)->toBe($user->id)
        ->and($applicant->status->value)->toBe('extracted')
        ->and($applicant->getFirstMedia('passport'))->not->toBeNull();

    expect(Extraction::query()->where('applicant_id', $applicant->id)->count())->toBe(1);

    $agency->refresh();
    expect($agency->used_this_month)->toBe(1);
});

it('re-extract updates applicant and creates extraction history', function (): void {
    Storage::fake('local');

    $agency = Agency::factory()->create([
        'monthly_quota' => 10,
        'used_this_month' => 0,
    ]);
    $user = User::factory()->create(['agency_id' => $agency->id]);

    $applicant = Applicant::factory()->create([
        'agency_id' => $agency->id,
        'created_by' => $user->id,
        'status' => 'draft',
    ]);

    $relativePath = UploadedFile::fake()->image('passport-2.jpg')->store('testing', 'local');
    $absolutePath = Storage::disk('local')->path($relativePath);
    $applicant->addMedia($absolutePath)->toMediaCollection('passport', 'local');

    $service = app(PassportExtractionService::class);

    $service->reExtract($applicant, $user);
    $service->reExtract($applicant, $user);

    $applicant->refresh();
    expect($applicant->status->value)->toBe('extracted');
    expect(Extraction::query()->where('applicant_id', $applicant->id)->count())->toBe(2);

    $agency->refresh();
    expect($agency->used_this_month)->toBe(2);
});

it('throws when re-extract has no passport media', function (): void {
    $agency = Agency::factory()->create([
        'monthly_quota' => 10,
        'used_this_month' => 0,
    ]);
    $user = User::factory()->create(['agency_id' => $agency->id]);

    $applicant = Applicant::factory()->create([
        'agency_id' => $agency->id,
        'created_by' => $user->id,
        'status' => 'queued',
    ]);

    $service = app(PassportExtractionService::class);

    expect(fn () => $service->reExtract($applicant, $user))
        ->toThrow(NoPassportImageException::class);

    $applicant->refresh();
    expect($applicant->status->value)->toBe('queued');
});

it('marks applicant as failed when quota consumption loses race after extraction', function (): void {
    Storage::fake('local');

    $agency = Agency::factory()->create([
        'monthly_quota' => 1,
        'used_this_month' => 1,
    ]);
    $user = User::factory()->create(['agency_id' => $agency->id]);

    $applicant = Applicant::factory()->create([
        'agency_id' => $agency->id,
        'created_by' => $user->id,
        'status' => 'queued',
    ]);

    $relativePath = UploadedFile::fake()->image('passport-race.jpg')->store('testing', 'local');
    $absolutePath = Storage::disk('local')->path($relativePath);
    $applicant->addMedia($absolutePath)->toMediaCollection('passport', 'local');

    $service = app(PassportExtractionService::class);

    expect(fn () => $service->processQueuedApplicant($applicant, $user))
        ->toThrow(QuotaExceededException::class);

    $applicant->refresh();

    expect($applicant->status->value)->toBe('failed')
        ->and($applicant->extraction_error)->toContain('quota');
    expect(Extraction::query()->where('applicant_id', $applicant->id)->count())->toBe(0);
});

it('maps extractor output from mocked extractor', function (): void {
    Storage::fake('local');

    $agency = Agency::factory()->create();
    $user = User::factory()->create(['agency_id' => $agency->id]);

    $mockExtractor = Mockery::mock(PassportExtractor::class);
    $mockExtractor->shouldReceive('extractFromImagePath')->once()->andReturn([
        'raw' => ['ok' => true],
        'model' => 'mocked-model',
        'extracted' => [
            'PassportNumber' => 'A12345678',
            'CountryCode' => 'YEM',
            'MrzLine1' => 'P<YEMDOE<<JOHN<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<',
            'MrzLine2' => 'A12345678<0YEM9002015M3001012<<<<<<<<<<<<<<08',
            'DateOfBirth' => '01/02/1990',
            'DateOfIssue' => '1999-01-01',
            'DateOfExpiry' => 'N/A',
        ],
    ]);

    $service = new PassportExtractionService($mockExtractor);

    $relativePath = UploadedFile::fake()->image('passport-3.jpg')->store('testing', 'local');
    $absolutePath = Storage::disk('local')->path($relativePath);

    $applicant = $service->extractFromPath($agency, $user, $absolutePath);

    expect($applicant->passport_number)->toBe('A12345678')
        ->and($applicant->country_code)->toBe('YEM')
        ->and($applicant->mrz_line_1)->toBe('P<YEMDOE<<JOHN<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<')
        ->and($applicant->mrz_line_2)->toBe('A12345678<0YEM9002015M3001012<<<<<<<<<<<<<<08')
        ->and($applicant->date_of_birth?->toDateString())->toBe('1990-02-01')
        ->and($applicant->date_of_issue?->toDateString())->toBe('1999-01-01')
        ->and($applicant->date_of_expiry)->toBeNull();
});
