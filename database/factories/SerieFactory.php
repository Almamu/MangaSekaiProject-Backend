<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Serie>
 */
class SerieFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'chapter_count' => fake()->numberBetween(1, 100),
            'pages_count' => fake()->numberBetween(1, 100),
            'description' => fake()->text(),
            'synced' => fake()->boolean(),
            // cannot use fake()->image because it's an external service that works really slow most of the time
            'image' => null,
            'mime_type' => null,
        ];
    }
}
