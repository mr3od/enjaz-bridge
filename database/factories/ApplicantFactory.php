<?php

namespace Database\Factories;

use App\Enums\ApplicantStatus;
use App\Enums\EnjazStatus;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Applicant>
 */
class ApplicantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'created_by' => User::factory(),
            'passport_number' => strtoupper(fake()->bothify('??#######')),
            'country_code' => 'YEM',
            'status' => ApplicantStatus::Draft->value,
            'enjaz_status' => EnjazStatus::NotSubmitted->value,
            'extraction_requested_at' => null,
            'extraction_started_at' => null,
            'extraction_finished_at' => null,
            'extraction_error' => null,
        ];
    }
}
