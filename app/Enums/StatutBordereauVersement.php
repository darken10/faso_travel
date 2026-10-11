<?php

namespace App\Enums;

enum StatutBordereauVersement: string
{
    case Emis = 'emis';
    case RecuFinance = 'recu_finance';
    case TransmisComptabilite = 'transmis_comptabilite';
    case Comptabilise = 'comptabilise';

    public function label(): string
    {
        return match ($this) {
            self::Emis => 'Émis',
            self::RecuFinance => 'Reçu par la finance',
            self::TransmisComptabilite => 'Transmis à la comptabilité',
            self::Comptabilise => 'Comptabilisé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Emis => 'amber',
            self::RecuFinance => 'blue',
            self::TransmisComptabilite => 'purple',
            self::Comptabilise => 'green',
        };
    }
}
