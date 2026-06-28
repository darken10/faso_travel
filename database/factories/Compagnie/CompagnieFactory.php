<?php

namespace Database\Factories\Compagnie;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compagnie\Compagnie>
 */
class CompagnieFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => fake()->unique()->company(),
            'sigle'       => strtoupper(fake()->unique()->lexify('????')),
            'slogant'     => fake()->catchPhrase(),
            'description' => fake()->sentence(),
        ];
    }
}
