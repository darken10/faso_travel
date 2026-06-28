<?php

namespace App\Enums;

enum LoyaltyTier: string
{
    case Bronze  = 'Bronze';
    case Argent  = 'Argent';
    case Or      = 'Or';
    case Platine = 'Platine';

    /** Seuil (points cumulés) d'entrée dans le palier. */
    public function seuil(): int
    {
        return match ($this) {
            self::Bronze  => 0,
            self::Argent  => 500,
            self::Or      => 2000,
            self::Platine => 5000,
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::Bronze  => '#CD7F32',
            self::Argent  => '#9CA3AF',
            self::Or      => '#F59E0B',
            self::Platine => '#6366F1',
        };
    }

    /** Palier correspondant à un total de points cumulés. */
    public static function pour(int $lifetimePoints): self
    {
        $tier = self::Bronze;
        foreach (self::cases() as $case) {
            if ($lifetimePoints >= $case->seuil()) {
                $tier = $case;
            }
        }
        return $tier;
    }

    /** Palier suivant (null si déjà au maximum). */
    public function suivant(): ?self
    {
        return match ($this) {
            self::Bronze  => self::Argent,
            self::Argent  => self::Or,
            self::Or      => self::Platine,
            self::Platine => null,
        };
    }
}
