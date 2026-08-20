<?php

namespace Database\Factories\Compagnie;

use App\Models\Compagnie\Compagnie;
use App\Models\Statut;
use App\Models\User;
use App\Models\Ville\Ville;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compagnie\Gare>
 */
class GareFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'         => 'Gare de '.fake()->unique()->city(),
            // Coordonnées plausibles pour l'Afrique de l'Ouest.
            'lat'          => fake()->latitude(9, 15),
            'lng'          => fake()->longitude(-6, 2),
            'ville_id'     => Ville::factory(),
            'statut_id'    => Statut::query()->value('id') ?? 1,
            'user_id'      => User::factory(),
            'compagnie_id' => Compagnie::factory(),
            'is_default'   => false,
        ];
    }

    /** Gare commune, non rattachée à une compagnie. */
    public function commune(): self
    {
        return $this->state(fn () => ['compagnie_id' => null, 'is_default' => true]);
    }

    /** Gare dépourvue de coordonnées exploitables. */
    public function sansCoordonnees(): self
    {
        return $this->state(fn () => ['lat' => 0, 'lng' => 0]);
    }
}
