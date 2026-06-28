<?php

namespace Database\Factories\Voyage;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voyage\Classe>
 */
class ClasseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'    => fake()->randomElement(['Standard', 'Confort', 'VIP']),
            'user_id' => User::factory(),
        ];
    }
}
