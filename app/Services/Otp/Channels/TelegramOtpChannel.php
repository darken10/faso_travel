<?php

namespace App\Services\Otp\Channels;

use App\Enums\OtpChannelType;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Canal Telegram.
 *
 * Telegram ne permet pas d'envoyer un message à un simple numéro : l'utilisateur
 * doit avoir préalablement démarré le bot, ce qui fournit un `telegram_chat_id`.
 * Tant que ce chat_id n'est pas lié au compte (et que le bot n'est pas configuré),
 * le canal se déclare indisponible — il n'est donc jamais proposé à l'utilisateur.
 *
 * La classe respecte le contrat OtpChannel : dès que la liaison est en place
 * (colonne `telegram_chat_id` + TELEGRAM_BOT_TOKEN), l'envoi fonctionne sans
 * modifier le reste du code.
 */
class TelegramOtpChannel extends AbstractOtpChannel
{
    public function type(): OtpChannelType
    {
        return OtpChannelType::Telegram;
    }

    public function isAvailableFor(User $user): bool
    {
        return !empty(config('services.telegram.bot_token'))
            && !empty($user->telegram_chat_id ?? null);
    }

    public function maskedDestination(User $user): ?string
    {
        return 'Telegram';
    }

    public function send(User $user, string $otp): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = $user->telegram_chat_id ?? null;

        if (!$token || !$chatId) {
            throw new \RuntimeException('Canal Telegram non configuré pour cet utilisateur.');
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => "Votre code LIPTRA : {$otp}. Valable 10 minutes.",
        ])->throw();
    }
}
