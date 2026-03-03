<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'passport_number' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:3'],
            'mrz_line_1' => ['nullable', 'string', 'max:1000'],
            'mrz_line_2' => ['nullable', 'string', 'max:1000'],
            'surname_ar' => ['nullable', 'string', 'max:255'],
            'given_names_ar' => ['nullable', 'string', 'max:255'],
            'surname_en' => ['nullable', 'string', 'max:255'],
            'given_names_en' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'place_of_birth_ar' => ['nullable', 'string', 'max:255'],
            'place_of_birth_en' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', 'in:M,F,X'],
            'date_of_issue' => ['nullable', 'date'],
            'date_of_expiry' => ['nullable', 'date'],
            'profession_ar' => ['nullable', 'string', 'max:255'],
            'profession_en' => ['nullable', 'string', 'max:255'],
            'issuing_authority_ar' => ['nullable', 'string', 'max:255'],
            'issuing_authority_en' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => $this->normalizeNullableUpper($this->input('country_code')),
            'sex' => $this->normalizeNullableUpper($this->input('sex')),
        ]);
    }

    private function normalizeNullableUpper(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }
}
