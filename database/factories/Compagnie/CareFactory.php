<?php

namespace Database\Factories\Compagnie;

use App\Enums\StatutCare;
use App\Models\Compagnie\Compagnie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compagnie\Care>
 */
class CareFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'immatrculation' => strtoupper(fake()->bothify('##-???-##')),
            'numero'         => fake()->numberBetween(1, 999),
            'number_place'   => fake()->numberBetween(30, 70),
            'statut'         => StatutCare::Disponible,
            'compagnie_id'   => Compagnie::factory(),
        ];
    }
}
