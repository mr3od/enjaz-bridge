<?php

namespace App\Http\Controllers;

use App\Enums\ApplicantStatus;
use App\Exceptions\QuotaExceededException;
use App\Http\Requests\StorePassportExtractionRequest;
use App\Jobs\ProcessPassportExtraction;
use App\Models\Agency;
use App\Models\Applicant;
use App\Models\Extraction;
use App\Services\PassportExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PassportExtractionController extends Controller
{
    public function index(Request $request): Response
    {
        $agency = $this->currentAgency();
        $this->markStaleProcessingAsFailed();

        $applicants = Applicant::query()
            ->latest()
            ->limit(50)
            ->get();

        return Inertia::render('passport-extractions/index', [
            'applicants' => $applicants->map(fn (Applicant $applicant): array => $this->applicantQueuePayload($applicant))->values()->all(),
            'quota' => $this->quotaPayload($agency),
            'batch_limit' => (int) config('ai.passport.ui.max_batch_size', 10),
            'max_file_kb' => (int) config('ai.passport.ui.max_file_kb', 10_240),
            'flash' => [
                'queued_count' => $request->session()->get('queued_count'),
            ],
        ]);
    }

    public function store(
        StorePassportExtractionRequest $request,
        PassportExtractionService $passportExtractionService
    ): RedirectResponse {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $agency = $this->currentAgency();
        $queuedApplicantIds = [];

        foreach ($request->file('files', []) as $file) {
            if ($file === null) {
                continue;
            }

            $relativePath = $file->store('passport-uploads', 'local');
            $absolutePath = Storage::disk('local')->path($relativePath);

            try {
                $applicant = $passportExtractionService->queueFromUploadedImage($agency, $user, $absolutePath);
            } catch (QuotaExceededException) {
                return to_route('passport-extractions.index')
                    ->withErrors(['files' => 'Monthly extraction quota exceeded.']);
            }

            ProcessPassportExtraction::dispatch($applicant->id, $user->id);
            $queuedApplicantIds[] = $applicant->id;
        }

        return to_route('passport-extractions.index', [
            'ids' => $queuedApplicantIds,
        ])->with('queued_count', count($queuedApplicantIds));
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'string', 'max:26'],
        ]);

        $agency = $this->currentAgency();
        $ids = (array) $validated['ids'];
        $this->markStaleProcessingAsFailed($ids);

        $applicants = Applicant::query()
            ->whereIn('id', $ids)
            ->latest()
            ->get();

        return response()->json([
            'applicants' => $applicants->map(fn (Applicant $applicant): array => $this->applicantQueuePayload($applicant))->values()->all(),
            'quota' => $this->quotaPayload($agency->refresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function applicantQueuePayload(Applicant $applicant): array
    {
        return [
            'id' => $applicant->id,
            'status' => $applicant->status->value,
            'extraction_error' => $applicant->extraction_error,
            'extraction_requested_at' => $applicant->extraction_requested_at?->toISOString(),
            'extraction_started_at' => $applicant->extraction_started_at?->toISOString(),
            'extraction_finished_at' => $applicant->extraction_finished_at?->toISOString(),
            'passport_number' => $applicant->passport_number,
            'surname_en' => $applicant->surname_en,
            'given_names_en' => $applicant->given_names_en,
            'surname_ar' => $applicant->surname_ar,
            'given_names_ar' => $applicant->given_names_ar,
            'latest_extraction' => $this->latestExtractionPayload($applicant),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function quotaPayload(Agency $agency): array
    {
        return [
            'monthly_quota' => $agency->monthly_quota,
            'used_this_month' => $agency->used_this_month,
            'quota_remaining' => $agency->quotaRemaining(),
        ];
    }

    /**
     * @return array{model_used: string, processing_ms: int}|null
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
        ];
    }

    private function currentAgency(): Agency
    {
        $tenant = tenancy()->tenant;

        if (! $tenant instanceof Agency) {
            abort(404);
        }

        return $tenant;
    }

    /**
     * @param  array<int, string>|null  $ids
     */
    private function markStaleProcessingAsFailed(?array $ids = null): void
    {
        $staleMinutes = max(1, (int) config('ai.passport.ui.stale_processing_minutes', 10));

        Applicant::query()
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->where('status', ApplicantStatus::Processing->value)
            ->whereNotNull('extraction_started_at')
            ->whereNull('extraction_finished_at')
            ->where('extraction_started_at', '<=', now()->subMinutes($staleMinutes))
            ->update([
                'status' => ApplicantStatus::Failed->value,
                'extraction_finished_at' => now(),
                'extraction_error' => 'Extraction timed out. Queue worker may be offline; please retry.',
            ]);
    }
}
