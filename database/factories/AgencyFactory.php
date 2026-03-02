<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agency>
 */
class AgencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'city' => fake()->city(),
            'plan' => 'free',
            'monthly_quota' => 10,
            'used_this_month' => 0,
            'quota_resets_at' => now()->addMonth()->startOfMonth(),
            'is_active' => true,
        ];
    }
}
