<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'logo_path' => 'clients/'.Str::slug($name).'.webp',
            'dark_logo' => false,
            'summary' => null,
            'article' => null,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the client has a story to show.
     */
    public function withStory(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary' => fake()->sentence(12),
            'article' => "## The challenge\n\n".fake()->paragraph()."\n\n## What we built\n\n".fake()->paragraph(),
        ]);
    }

    /**
     * Indicate that the client is hidden from the site.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
