<?php

namespace App\Services\V2;

use App\Events\MessageSent;
use App\Models\Messages\Conversation;
use App\Models\Messages\Message;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ConversationService
{
    /**
     * Récupère toutes les conversations de l'utilisateur authentifié.
     */
    public function getUserConversations(): Collection
    {
        return Conversation::with(['compagnie:id,name,logo_uri'])
            ->forUser(Auth::id())
            ->whereNull('deleted_at')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Crée ou retrouve une conversation existante.
     * Pour une conversation "support", compagnie_id reste null.
     */
    public function findOrCreate(array $payload): Conversation
    {
        $userId = Auth::id();
        $type   = $payload['type'] ?? 'company';

        if ($type === 'support') {
            // Une seule conversation support active par utilisateur
            return Conversation::firstOrCreate(
                ['client_id' => $userId, 'type' => 'support', 'deleted_at' => null],
                ['status' => 'active', 'type' => 'support']
            );
        }

        $compagnieId = $payload['compagnie_id'];

        // Réutilise la conversation existante si elle n'est pas archivée
        $existing = Conversation::where('client_id', $userId)
            ->where('compagnie_id', $compagnieId)
            ->where('type', 'company')
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        return Conversation::create([
            'client_id'   => $userId,
            'compagnie_id' => $compagnieId,
            'type'        => 'company',
            'status'      => 'active',
        ]);
    }

    /**
     * Récupère les messages d'une conversation (cursor pagination).
     */
    public function getMessages(string $conversationId, ?string $cursor = null, int $perPage = 30): CursorPaginator
    {
        $conversation = $this->findConversationForUser($conversationId);

        return $conversation->messages()
            ->with('sender:id,name,first_name,last_name,profile_photo_path')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    /**
     * Envoie un message et broadcast via Reverb.
     */
    public function sendMessage(string $conversationId, string $text): Message
    {
        $conversation = $this->findConversationForUser($conversationId);

        $message = $conversation->messages()->create([
            'sender_id' => Auth::id(),
            'message'   => $text,
        ]);

        // Mise à jour des métadonnées de la conversation
        $conversation->update([
            'last_message_at'     => now(),
            'last_message'        => mb_substr($text, 0, 100),
            'unread_count_agent'  => $conversation->unread_count_agent + 1,
        ]);

        $message->load('sender:id,name,first_name,last_name,profile_photo_path');

        // Diffusion WebSocket via Reverb
        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    /**
     * Marque une conversation comme lue pour le client.
     */
    public function markAsRead(string $conversationId): void
    {
        $conversation = $this->findConversationForUser($conversationId);
        $conversation->update(['unread_count_client' => 0]);
    }

    /**
     * Archive (soft delete) une conversation.
     */
    public function archive(string $conversationId): void
    {
        $conversation = $this->findConversationForUser($conversationId);
        $conversation->delete();
    }

    private function findConversationForUser(string $conversationId): Conversation
    {
        return Conversation::where('id', $conversationId)
            ->where('client_id', Auth::id())
            ->whereNull('deleted_at')
            ->firstOrFail();
    }
}
