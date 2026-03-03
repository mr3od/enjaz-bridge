<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Applicant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Extraction>
 */
class ExtractionFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\Extraction $extraction): void {
            if ($extraction->relationLoaded('applicant') && $extraction->applicant !== null) {
                $extraction->agency_id = $extraction->applicant->agency_id;
            }
        })->afterCreating(function (\App\Models\Extraction $extraction): void {
            $applicant = $extraction->applicant;

            if ($applicant !== null && $extraction->agency_id !== $applicant->agency_id) {
                $extraction->update(['agency_id' => $applicant->agency_id]);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'applicant_id' => Applicant::factory(),
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'model_used' => 'baseline-extractor-v1',
            'raw_response' => ['ok' => true],
            'extracted_data' => ['PassportNumber' => 'A12345678'],
            'corrections' => null,
            'processing_ms' => fake()->numberBetween(100, 5000),
        ];
    }
}
