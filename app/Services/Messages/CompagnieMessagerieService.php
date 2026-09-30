<?php

namespace App\Services\Messages;

use App\Events\MessageSent;
use App\Models\Messages\Conversation;
use App\Models\Messages\Message;
use App\Models\User;
use App\Notifications\NouveauMessageNotification;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Messagerie côté compagnie : lire les messages des clients et leur répondre.
 *
 * Toutes les opérations sont bornées à la compagnie donnée. Un identifiant de
 * conversation reçu du navigateur n'est jamais une donnée de confiance : il est
 * systématiquement recherché dans le périmètre de la compagnie.
 */
class CompagnieMessagerieService
{
    /** Longueur maximale d'un message, alignée sur ce que le client mobile accepte d'afficher. */
    public const MAX_LENGTH = 2000;

    /**
     * Conversations des clients avec cette compagnie.
     *
     * Les conversations « support » (avec l'équipe Liptra) n'ont pas de compagnie et
     * ne sont donc jamais visibles ici.
     */
    public function conversations(int $compagnieId): Builder
    {
        return Conversation::query()
            ->where('compagnie_id', $compagnieId)
            ->where('type', 'company');
    }

    public function find(string $conversationId, int $compagnieId): Conversation
    {
        return $this->conversations($compagnieId)->findOrFail($conversationId);
    }

    /** Nombre de conversations qui attendent une lecture, pour le compteur du menu. */
    public function unreadCount(int $compagnieId): int
    {
        return $this->conversations($compagnieId)->where('unread_count_agent', '>', 0)->count();
    }

    public function markRead(Conversation $conversation): void
    {
        if ($conversation->unread_count_agent > 0) {
            $conversation->forceFill(['unread_count_agent' => 0])->save();
        }
    }

    /**
     * Répond à un client.
     *
     * Le message est enregistré en premier et de façon certaine. La diffusion
     * temps réel et la notification push viennent ensuite : si l'une échoue (serveur
     * WebSocket arrêté, file indisponible), la réponse reste enregistrée et visible
     * dans l'application du client à sa prochaine ouverture.
     */
    public function reply(string $conversationId, int $compagnieId, User $agent, string $text): Message
    {
        $text = trim($text);

        if ($text === '' || mb_strlen($text) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException('Message vide ou trop long.');
        }

        $conversation = $this->find($conversationId, $compagnieId);

        $message = DB::transaction(function () use ($conversation, $agent, $text) {
            $message = $conversation->messages()->create([
                'sender_id' => $agent->id,
                'message'   => $text,
            ]);

            // Incrément atomique : deux réponses simultanées de collègues comptent
            // bien pour deux. Lire puis réécrire la valeur en perdrait une.
            Conversation::whereKey($conversation->id)->update([
                'last_message_at'     => now(),
                'last_message'        => mb_substr($text, 0, 100),
                'unread_count_client' => DB::raw('unread_count_client + 1'),
                // Répondre vaut lecture : la conversation est prise en charge.
                'unread_count_agent'  => 0,
            ]);

            return $message;
        });

        $message->load('sender:id,name,first_name,last_name,profile_photo_path');

        $this->diffuser($message);
        $this->notifierLeClient($conversation, $message);

        return $message;
    }

    private function diffuser(Message $message): void
    {
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('[Messagerie] diffusion temps réel échouée : ' . $e->getMessage(), [
                'message_id' => $message->id,
            ]);
        }
    }

    private function notifierLeClient(Conversation $conversation, Message $message): void
    {
        try {
            $conversation->loadMissing(['client', 'compagnie:id,name']);

            $conversation->client?->notify(new NouveauMessageNotification(
                $message,
                $conversation->compagnie?->name ?? 'LIPTRA',
            ));
        } catch (\Throwable $e) {
            Log::warning('[Messagerie] notification push échouée : ' . $e->getMessage(), [
                'message_id' => $message->id,
            ]);
        }
    }
}
