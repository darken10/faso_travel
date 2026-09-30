<?php

namespace Database\Factories\Messages;

use App\Models\Compagnie\Compagnie;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Messages\Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id'           => User::factory(),
            'compagnie_id'        => Compagnie::factory(),
            'status'              => 'active',
            'type'                => 'company',
            'unread_count_client' => 0,
            'unread_count_agent'  => 0,
        ];
    }

    /** Conversation avec l'équipe Liptra : aucune compagnie. */
    public function support(): static
    {
        return $this->state(fn () => ['type' => 'support', 'compagnie_id' => null]);
    }
}
