<?php

namespace App\Services\Otp;

use App\Enums\OtpChannelType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orchestration des OTP : génération, stockage (cache), envoi via le canal
 * choisi et vérification. Le canal d'envoi est délégué à OtpChannelManager
 * (pattern Strategy), ce qui rend l'ajout / le changement de canal trivial.
 */
class OtpService
{
    private const TTL_MINUTES = 10;

    public function __construct(private readonly OtpChannelManager $channels) {}

    /**
     * Génère un OTP, le stocke et l'envoie via le canal demandé (ou par défaut).
     *
     * @param  string  $purpose  'verification' | 'password_reset'
     * @return OtpChannelType  Le canal réellement utilisé.
     */
    public function send(User $user, ?OtpChannelType $channelType, string $purpose): OtpChannelType
    {
        $channelType ??= $this->channels->defaultFor($user);
        $channel = $this->channels->get($channelType);

        if (!$channel->isAvailableFor($user)) {
            throw new \RuntimeException("Le canal {$channelType->label()} n'est pas disponible pour ce compte.");
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(
            $this->cacheKey($user->id, $purpose),
            ['otp' => $otp, 'channel' => $channelType->value],
            Carbon::now()->addMinutes(self::TTL_MINUTES),
        );

        // Mode développement : on logue l'OTP au lieu de l'envoyer pour les canaux
        // téléphoniques (SMS/WhatsApp/Telegram) — pratique sans compte Twilio valide.
        // L'e-mail, lui, est toujours réellement envoyé.
        if (config('otp.log_only') && $channelType !== OtpChannelType::Email) {
            Log::info('[OTP] (log_only) code généré', [
                'user_id' => $user->id,
                'purpose' => $purpose,
                'channel' => $channelType->value,
                'otp'     => $otp,
            ]);

            return $channelType;
        }

        $channel->send($user, $otp);

        return $channelType;
    }

    /**
     * Vérifie l'OTP. En cas de succès, renvoie le canal utilisé et purge le cache.
     *
     * @return OtpChannelType|null  Le canal utilisé si valide, sinon null.
     */
    public function verify(User $user, string $otp, string $purpose): ?OtpChannelType
    {
        $stored = Cache::get($this->cacheKey($user->id, $purpose));

        if (!$stored || !hash_equals($stored['otp'], $otp)) {
            return null;
        }

        Cache::forget($this->cacheKey($user->id, $purpose));

        return OtpChannelType::tryFrom($stored['channel']) ?? $this->channels->defaultFor($user);
    }

    /**
     * Liste des canaux disponibles pour l'utilisateur (pour l'écran de choix).
     *
     * @return array<int, array{type: string, label: string, destination: string|null}>
     */
    public function availableChannels(User $user): array
    {
        return array_map(
            fn ($channel) => [
                'type'        => $channel->type()->value,
                'label'       => $channel->type()->label(),
                'destination' => $channel->maskedDestination($user),
            ],
            $this->channels->availableFor($user),
        );
    }

    public function defaultChannel(User $user): OtpChannelType
    {
        return $this->channels->defaultFor($user);
    }

    private function cacheKey(int $userId, string $purpose): string
    {
        return "otp:{$purpose}:user:{$userId}";
    }
}
