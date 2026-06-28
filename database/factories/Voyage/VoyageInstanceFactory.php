<?php

namespace Database\Factories\Voyage;

use App\Enums\StatutVoyageInstance;
use App\Models\Voyage\Voyage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voyage\VoyageInstance>
 */
class VoyageInstanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voyage_id' => Voyage::factory(),
            'date'      => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'heure'     => fake()->time('H:i:s'),
            'nb_place'  => fake()->numberBetween(30, 70),
            'statut'    => StatutVoyageInstance::DISPONIBLE,
            'prix'      => fake()->numberBetween(2000, 15000),
        ];
    }
}
