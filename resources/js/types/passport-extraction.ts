export type ExtractionStatus =
    | 'queued'
    | 'processing'
    | 'extracted'
    | 'failed'
    | 'draft';

export type ExtractionQueueItem = {
    id: string;
    status: ExtractionStatus;
    extraction_error: string | null;
    extraction_requested_at: string | null;
    extraction_started_at: string | null;
    extraction_finished_at: string | null;
    passport_number: string | null;
    surname_en: string | null;
    given_names_en: string | null;
    surname_ar: string | null;
    given_names_ar: string | null;
    latest_extraction: {
        model_used: string;
        processing_ms: number;
    } | null;
};

export type ExtractionQuotaSummary = {
    monthly_quota: number;
    used_this_month: number;
    quota_remaining: number;
};

export type ApplicantReviewData = {
    id: string;
    status: ExtractionStatus;
    enjaz_status: string;
    passport_number: string | null;
    country_code: string | null;
    mrz_line_1: string | null;
    mrz_line_2: string | null;
    surname_ar: string | null;
    given_names_ar: string | null;
    surname_en: string | null;
    given_names_en: string | null;
    date_of_birth: string | null;
    place_of_birth_ar: string | null;
    place_of_birth_en: string | null;
    sex: string | null;
    date_of_issue: string | null;
    date_of_expiry: string | null;
    profession_ar: string | null;
    profession_en: string | null;
    issuing_authority_ar: string | null;
    issuing_authority_en: string | null;
    extraction_error: string | null;
    extraction_requested_at: string | null;
    extraction_started_at: string | null;
    extraction_finished_at: string | null;
};
