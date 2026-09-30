<?php

namespace Database\Factories\Messages;

use App\Models\Messages\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Messages\Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id'       => User::factory(),
            'message'         => fake()->sentence(),
        ];
    }
}
