<?php

namespace Database\Factories\Voyage;

use App\Models\User;
use App\Models\Ville\Ville;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voyage\Trajet>
 */
class TrajetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'depart_id'  => Ville::factory(),
            'arriver_id' => Ville::factory(),
            'distance'   => fake()->numberBetween(50, 800),
        ];
    }
}
