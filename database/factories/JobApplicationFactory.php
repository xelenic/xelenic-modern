<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => 'laravel-developer',
            'role_title' => 'Laravel Developer',
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'location' => fake()->city(),
            'portfolio_url' => 'https://github.com/'.fake()->userName(),
            'message' => fake()->paragraph(),
            'status' => ApplicationStatus::New,
        ];
    }
}
