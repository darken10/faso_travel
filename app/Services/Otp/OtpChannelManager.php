<?php

namespace App\Services\Otp;

use App\Contracts\OtpChannel;
use App\Enums\OtpChannelType;
use App\Models\User;
use App\Services\Otp\Channels\MailOtpChannel;
use App\Services\Otp\Channels\SmsOtpChannel;
use App\Services\Otp\Channels\TelegramOtpChannel;
use App\Services\Otp\Channels\WhatsappOtpChannel;

/**
 * Registre des canaux OTP (Strategy).
 *
 * Pour ajouter un canal : créer une classe qui implémente OtpChannel puis
 * l'ajouter au tableau du constructeur. Aucun autre changement nécessaire.
 */
class OtpChannelManager
{
    /** @var array<string, OtpChannel> */
    private array $channels = [];

    public function __construct(
        MailOtpChannel $mail,
        SmsOtpChannel $sms,
        WhatsappOtpChannel $whatsapp,
        TelegramOtpChannel $telegram,
    ) {
        foreach ([$mail, $sms, $whatsapp, $telegram] as $channel) {
            $this->channels[$channel->type()->value] = $channel;
        }
    }

    public function get(OtpChannelType $type): OtpChannel
    {
        return $this->channels[$type->value]
            ?? throw new \InvalidArgumentException("Canal OTP inconnu : {$type->value}");
    }

    /**
     * Canaux réellement utilisables pour cet utilisateur.
     *
     * @return OtpChannel[]
     */
    public function availableFor(User $user): array
    {
        return array_values(array_filter(
            $this->channels,
            fn (OtpChannel $channel) => $channel->isAvailableFor($user),
        ));
    }

    /**
     * Canal par défaut : e-mail si une adresse est renseignée, sinon SMS,
     * sinon le premier canal disponible.
     */
    public function defaultFor(User $user): OtpChannelType
    {
        if ($this->get(OtpChannelType::Email)->isAvailableFor($user)) {
            return OtpChannelType::Email;
        }

        if ($this->get(OtpChannelType::Sms)->isAvailableFor($user)) {
            return OtpChannelType::Sms;
        }

        $available = $this->availableFor($user);

        if (empty($available)) {
            throw new \RuntimeException('Aucun canal de vérification disponible pour ce compte.');
        }

        return $available[0]->type();
    }
}
