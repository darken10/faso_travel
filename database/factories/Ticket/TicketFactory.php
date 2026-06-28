<?php

namespace Database\Factories\Ticket;

use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
use App\Models\User;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket\Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'voyage_instance_id' => VoyageInstance::factory(),
            'date'               => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'type'               => TypeTicket::AllerSimple,
            'statut'             => StatutTicket::EnAttente,
            'numero_ticket'      => strtoupper(fake()->unique()->bothify('TK-####??')),
            'numero_chaise'      => fake()->numberBetween(1, 50),
            'code_sms'           => (string) fake()->numberBetween(100000, 999999),
            'code_qr'            => fake()->uuid(),
            'is_my_ticket'       => true,
        ];
    }
}
