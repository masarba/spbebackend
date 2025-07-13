<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KamusKerentanan>
 */
class KamusKerentananFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'severity' => fake()->sentence(),
            'desc' => fake()->sentence(),
            'impact' => fake()->sentence(),
            'recommendation' => fake()->sentence(),
        ];
    }
}
