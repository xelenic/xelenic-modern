<?php

namespace Database\Factories;

use App\Models\GameTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameTemplate>
 */
class GameTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 99999),
            'name' => Str::title($name),
            'description' => fake()->sentence(),
            'kind' => 'custom',
            'version' => '1.0.0',
            'author' => fake()->name(),
            'icon' => '🎮',
            'color' => '#1d4ed8',
            'is_active' => true,
            'sort_order' => 50,
        ];
    }
}
