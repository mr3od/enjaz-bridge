<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\PassportExtractor;
use App\Enums\ApplicantStatus;
use App\Enums\EnjazStatus;
use App\Exceptions\NoPassportImageException;
use App\Exceptions\QuotaExceededException;
use App\Models\Agency;
use App\Models\Applicant;
use App\Models\Extraction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class PassportExtractionService
{
    /** @var array<string, string> */
    private const FIELD_MAP = [
        'PassportNumber' => 'passport_number',
        'CountryCode' => 'country_code',
        'MrzLine1' => 'mrz_line_1',
        'MrzLine2' => 'mrz_line_2',
        'SurnameAr' => 'surname_ar',
        'GivenNamesAr' => 'given_names_ar',
        'SurnameEn' => 'surname_en',
        'GivenNamesEn' => 'given_names_en',
        'DateOfBirth' => 'date_of_birth',
        'PlaceOfBirthAr' => 'place_of_birth_ar',
        'PlaceOfBirthEn' => 'place_of_birth_en',
        'Sex' => 'sex',
        'DateOfIssue' => 'date_of_issue',
        'DateOfExpiry' => 'date_of_expiry',
        'ProfessionAr' => 'profession_ar',
        'ProfessionEn' => 'profession_en',
        'IssuingAuthorityAr' => 'issuing_authority_ar',
        'IssuingAuthorityEn' => 'issuing_authority_en',
    ];

    public function __construct(private readonly PassportExtractor $passportExtractor) {}

    /**
     * @throws QuotaExceededException
     */
    public function queueFromUploadedImage(Agency $agency, User $user, string $absoluteImagePath): Applicant
    {
        if (! $agency->hasQuota()) {
            throw new QuotaExceededException('Monthly extraction quota exceeded.');
        }

        $applicant = Applicant::query()->create([
            'agency_id' => $agency->id,
            'created_by' => $user->id,
            'passport_number' => $this->generateQueuedPassportNumber(),
            'status' => ApplicantStatus::Queued,
            'enjaz_status' => EnjazStatus::NotSubmitted,
            'extraction_requested_at' => now(),
            'extraction_started_at' => null,
            'extraction_finished_at' => null,
            'extraction_error' => null,
        ]);

        $applicant->addMedia($absoluteImagePath)->toMediaCollection('passport', 'local');

        return $applicant->fresh();
    }

    /**
     * @throws NoPassportImageException
     * @throws QuotaExceededException
     */
    public function processQueuedApplicant(Applicant $applicant, User $user): Applicant
    {
        $passportMedia = $applicant->getFirstMedia('passport');

        if ($passportMedia === null) {
            throw new NoPassportImageException('No passport image found for applicant.');
        }

        $applicant->update([
            'status' => ApplicantStatus::Processing,
            'extraction_started_at' => now(),
            'extraction_finished_at' => null,
            'extraction_error' => null,
        ]);

        try {
            $result = $this->extractAndMap($passportMedia->getPath());

            if (! $applicant->agency->tryConsumeQuota()) {
                throw new QuotaExceededException('Monthly extraction quota exceeded.');
            }

            $applicant->update(array_merge($result['mapped'], [
                'status' => ApplicantStatus::Extracted,
                'enjaz_status' => EnjazStatus::NotSubmitted,
                'extraction_finished_at' => now(),
                'extraction_error' => null,
            ]));

            $this->createExtraction(
                applicant: $applicant,
                user: $user,
                modelUsed: (string) $result['model'],
                rawResponse: (array) $result['raw'],
                extractedData: (array) $result['extracted'],
                processingMs: (int) $result['processing_ms']
            );

            return $applicant->fresh();
        } catch (Throwable $exception) {
            $this->markAsFailed($applicant, $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * @throws NoPassportImageException
     * @throws QuotaExceededException
     */
    public function reExtractQueued(Applicant $applicant, User $user): Applicant
    {
        $passportMedia = $applicant->getFirstMedia('passport');

        if ($passportMedia === null) {
            throw new NoPassportImageException('No passport image found for applicant.');
        }

        $applicant->update([
            'status' => ApplicantStatus::Processing,
            'extraction_started_at' => now(),
            'extraction_finished_at' => null,
            'extraction_error' => null,
        ]);

        try {
            $result = $this->extractAndMap($passportMedia->getPath());

            if (! $applicant->agency->tryConsumeQuota()) {
                throw new QuotaExceededException('Monthly extraction quota exceeded.');
            }

            $applicant->update(array_merge($result['mapped'], [
                'status' => ApplicantStatus::Extracted,
                'enjaz_status' => EnjazStatus::NotSubmitted,
                'extraction_finished_at' => now(),
                'extraction_error' => null,
            ]));

            $this->createExtraction(
                applicant: $applicant,
                user: $user,
                modelUsed: (string) $result['model'],
                rawResponse: (array) $result['raw'],
                extractedData: (array) $result['extracted'],
                processingMs: (int) $result['processing_ms']
            );

            return $applicant->fresh();
        } catch (Throwable $exception) {
            $this->markAsFailed($applicant, $exception->getMessage());

            throw $exception;
        }
    }

    public function markAsFailed(Applicant $applicant, string $error): Applicant
    {
        $applicant->update([
            'status' => ApplicantStatus::Failed,
            'extraction_finished_at' => now(),
            'extraction_error' => Str::limit($error, 65_535, ''),
        ]);

        return $applicant->fresh();
    }

    /**
     * @throws QuotaExceededException
     */
    public function extractFromPath(Agency $agency, User $user, string $absoluteImagePath): Applicant
    {
        $applicant = $this->queueFromUploadedImage($agency, $user, $absoluteImagePath);

        return $this->processQueuedApplicant($applicant, $user);
    }

    /**
     * @throws NoPassportImageException
     * @throws QuotaExceededException
     */
    public function reExtract(Applicant $applicant, User $user): Applicant
    {
        return $this->reExtractQueued($applicant, $user);
    }

    /**
     * @return array{mapped: array<string, mixed>, raw: array<string, mixed>, extracted: array<string, mixed>, model: string, processing_ms: int}
     */
    private function extractAndMap(string $absoluteImagePath): array
    {
        $startedAt = microtime(true);
        $result = $this->passportExtractor->extractFromImagePath($absoluteImagePath);

        return [
            'mapped' => $this->mapExtractedData((array) $result['extracted']),
            'raw' => (array) $result['raw'],
            'extracted' => (array) $result['extracted'],
            'model' => (string) $result['model'],
            'processing_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ];
    }

    private function generateQueuedPassportNumber(): string
    {
        return 'QUEUED'.strtoupper(Str::random(14));
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function mapExtractedData(array $raw): array
    {
        $mapped = [];

        foreach (self::FIELD_MAP as $sourceField => $targetField) {
            $value = $raw[$sourceField] ?? null;

            if (in_array($targetField, ['date_of_birth', 'date_of_issue', 'date_of_expiry'], true)) {
                $mapped[$targetField] = $this->parseDate($value);

                continue;
            }

            $mapped[$targetField] = $this->clean($value);
        }

        return $mapped;
    }

    private function parseDate(mixed $value): ?string
    {
        $cleaned = $this->clean($value);

        if ($cleaned === null) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'j/n/Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $cleaned);
            } catch (\Throwable) {
                continue;
            }

            if ($parsed !== false) {
                return $parsed->toDateString();
            }
        }

        try {
            if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $cleaned) === 1) {
                return Carbon::parse($cleaned)->toDateString();
            }

            if (preg_match('/^\\d{1,2}\\/\\d{1,2}\\/\\d{4}$/', $cleaned) === 1) {
                return Carbon::createFromFormat('j/n/Y', $cleaned)->toDateString();
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim((string) $value);

        if ($cleaned === '' || in_array(strtoupper($cleaned), ['N/A', 'NULL', 'UNKNOWN'], true)) {
            return null;
        }

        return $cleaned;
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     * @param  array<string, mixed>  $extractedData
     */
    private function createExtraction(
        Applicant $applicant,
        User $user,
        string $modelUsed,
        array $rawResponse,
        array $extractedData,
        int $processingMs
    ): Extraction {
        return Extraction::query()->create([
            'applicant_id' => $applicant->id,
            'agency_id' => $applicant->agency_id,
            'user_id' => $user->id,
            'model_used' => $modelUsed,
            'raw_response' => $rawResponse,
            'extracted_data' => $extractedData,
            'corrections' => null,
            'processing_ms' => $processingMs,
        ]);
    }
}
