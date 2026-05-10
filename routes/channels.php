<?php

use App\Models\Messages\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Autorisation pour les canaux privés de conversations.
| Seuls les participants à la conversation peuvent s'y abonner.
|
*/

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    // Autoriser le client de la conversation
    if ((string) $user->id === (string) $conversation->client_id) {
        return true;
    }

    // Autoriser les agents de la compagnie concernée
    if ($conversation->compagnie_id && (string) $user->compagnie_id === (string) $conversation->compagnie_id) {
        return true;
    }

    return false;
});
