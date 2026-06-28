<?php

namespace Database\Factories\Ville;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ville\Ville>
 */
class VilleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'lat'  => fake()->latitude(9, 15),
            'lng'  => fake()->longitude(-6, 2),
        ];
    }
}
