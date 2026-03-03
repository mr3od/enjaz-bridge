<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePassportExtractionRequest extends FormRequest
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
        $maxBatchSize = max(1, (int) config('ai.passport.ui.max_batch_size', 10));
        $maxFileKb = max(1, (int) config('ai.passport.ui.max_file_kb', 10_240));

        return [
            'files' => ['required', 'array', "max:{$maxBatchSize}"],
            'files.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', "max:{$maxFileKb}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxBatchSize = max(1, (int) config('ai.passport.ui.max_batch_size', 10));
        $maxFileKb = max(1, (int) config('ai.passport.ui.max_file_kb', 10_240));

        return [
            'files.required' => 'Please upload at least one passport image.',
            'files.array' => 'The uploaded files payload is invalid.',
            'files.max' => "You can upload up to {$maxBatchSize} files at once.",
            'files.*.image' => 'Each file must be a valid image.',
            'files.*.mimes' => 'Accepted formats are JPG, JPEG, PNG, and WEBP.',
            'files.*.max' => "Each file must be smaller than {$maxFileKb} KB.",
        ];
    }
}
