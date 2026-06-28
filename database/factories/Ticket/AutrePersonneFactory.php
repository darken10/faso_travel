<?php

namespace Database\Factories\Ticket;

use App\Enums\SexeUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket\AutrePersonne>
 */
class AutrePersonneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name'         => fake()->firstName(),
            'last_name'          => fake()->lastName(),
            'sexe'               => fake()->randomElement(SexeUser::values()),
            'numero'             => fake()->numberBetween(60000000, 79999999),
            'numero_identifiant' => '+226',
            'lien_relation'      => fake()->randomElement(['Ami(e)', 'Parent', 'Collègue']),
        ];
    }
}
