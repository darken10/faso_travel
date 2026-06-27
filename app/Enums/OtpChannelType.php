<?php

namespace App\Enums;

enum OtpChannelType: string
{
    case Email    = 'email';
    case Sms      = 'sms';
    case Whatsapp = 'whatsapp';
    case Telegram = 'telegram';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Email    => 'E-mail',
            self::Sms      => 'SMS',
            self::Whatsapp => 'WhatsApp',
            self::Telegram => 'Telegram',
        };
    }

    /**
     * Colonne de vérification associée au canal.
     * L'email valide l'adresse e-mail ; les autres canaux valident le téléphone.
     */
    public function verifiedColumn(): string
    {
        return $this === self::Email ? 'email_verified_at' : 'phone_verified_at';
    }
}
