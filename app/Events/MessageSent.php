<?php

namespace App\Events;

use App\Models\Messages\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        $message = $this->message->load('sender');

        return [
            'id'              => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id'       => $message->sender_id,
            'message'         => $message->message,
            'created_at'      => $message->created_at?->toISOString(),
            'sender'          => $message->sender ? [
                'id'                 => $message->sender->id,
                'name'               => $message->sender->name,
                'profile_photo_url'  => $message->sender->profile_photo_url,
            ] : null,
        ];
    }
}
