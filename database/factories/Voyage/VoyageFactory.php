<?php

namespace Database\Factories\Voyage;

use App\Models\Compagnie\Compagnie;
use App\Models\User;
use App\Models\Voyage\Classe;
use App\Models\Voyage\Trajet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voyage\Voyage>
 */
class VoyageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trajet_id'    => Trajet::factory(),
            'user_id'      => User::factory(),
            'compagnie_id' => Compagnie::factory(),
            'classe_id'    => Classe::factory(),
            'heure'        => fake()->time('H:i:s'),
            'prix'         => fake()->numberBetween(2000, 15000),
        ];
    }
}
