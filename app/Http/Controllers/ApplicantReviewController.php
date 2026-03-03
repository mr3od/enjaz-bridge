<?php

namespace App\Http\Controllers;

use App\Enums\ApplicantStatus;
use App\Http\Requests\UpdateApplicantRequest;
use App\Jobs\ProcessPassportExtraction;
use App\Models\Applicant;
use App\Models\Extraction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantReviewController extends Controller
{
    public function show(Applicant $applicant): Response
    {
        return Inertia::render('applicants/show', [
            'applicant' => $this->applicantPayload($applicant),
            'latest_extraction' => $this->latestExtractionPayload($applicant),
        ]);
    }

    public function update(UpdateApplicantRequest $request, Applicant $applicant): RedirectResponse
    {
        $applicant->update($request->validated());

        return to_route('applicants.show', $applicant);
    }

    public function reExtract(Request $request, Applicant $applicant): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $applicant->update([
            'status' => ApplicantStatus::Queued,
            'extraction_requested_at' => now(),
            'extraction_started_at' => null,
            'extraction_finished_at' => null,
            'extraction_error' => null,
        ]);

        ProcessPassportExtraction::dispatch($applicant->id, $user->id);

        return to_route('applicants.show', $applicant);
    }

    /**
     * @return array<string, mixed>
     */
    private function applicantPayload(Applicant $applicant): array
    {
        return [
            'id' => $applicant->id,
            'status' => $applicant->status->value,
            'enjaz_status' => $applicant->enjaz_status->value,
            'passport_number' => $applicant->passport_number,
            'country_code' => $applicant->country_code,
            'surname_ar' => $applicant->surname_ar,
            'given_names_ar' => $applicant->given_names_ar,
            'surname_en' => $applicant->surname_en,
            'given_names_en' => $applicant->given_names_en,
            'date_of_birth' => $applicant->date_of_birth?->toDateString(),
            'place_of_birth_ar' => $applicant->place_of_birth_ar,
            'place_of_birth_en' => $applicant->place_of_birth_en,
            'sex' => $applicant->sex,
            'date_of_issue' => $applicant->date_of_issue?->toDateString(),
            'date_of_expiry' => $applicant->date_of_expiry?->toDateString(),
            'profession_ar' => $applicant->profession_ar,
            'profession_en' => $applicant->profession_en,
            'issuing_authority_ar' => $applicant->issuing_authority_ar,
            'issuing_authority_en' => $applicant->issuing_authority_en,
            'extraction_error' => $applicant->extraction_error,
            'extraction_requested_at' => $applicant->extraction_requested_at?->toISOString(),
            'extraction_started_at' => $applicant->extraction_started_at?->toISOString(),
            'extraction_finished_at' => $applicant->extraction_finished_at?->toISOString(),
            'passport_image_url' => $applicant->getFirstMedia('passport')?->getUrl(),
        ];
    }

    /**
     * @return array{model_used: string, processing_ms: int, created_at: string|null}|null
     */
    private function latestExtractionPayload(Applicant $applicant): ?array
    {
        $latestExtraction = $applicant->extractions()->latest()->first();

        if (! $latestExtraction instanceof Extraction) {
            return null;
        }

        return [
            'model_used' => $latestExtraction->model_used,
            'processing_ms' => (int) $latestExtraction->processing_ms,
            'created_at' => $latestExtraction->created_at?->toISOString(),
        ];
    }
}
