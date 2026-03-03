<?php

namespace App\Jobs;

use App\Models\Applicant;
use App\Models\User;
use App\Services\PassportExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessPassportExtraction implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $applicantId,
        public int $userId
    ) {}

    public function handle(PassportExtractionService $passportExtractionService): void
    {
        $applicant = Applicant::query()->find($this->applicantId);
        $user = User::query()->find($this->userId);

        if ($applicant === null || $user === null) {
            return;
        }

        try {
            $passportExtractionService->processQueuedApplicant($applicant, $user);
        } catch (Throwable $exception) {
            $passportExtractionService->markAsFailed($applicant, $exception->getMessage());
        }
    }
}
